<?php

namespace FreePBX\modules\Sccp_manager\sccpManTraits;

trait helperFunctions {
    private function convertCsvToArray($stringToConvert = "") {
        // Take a csv string form of net/mask or ip/port and convert to an array
        // sub arrays are separated by ";"
        $outputArr = array();
        if (empty($stringToConvert)) {
            return $outputArr;
        }
        foreach (explode(";", $stringToConvert) as $value) {
            //internal is always the first setting if present
            if ($value == 'internal') {
                $outputArr[] = array('internal' => 'on');
                continue;
            }
            // Now handle rest of value types
            $subArr = explode("/", $value);
            if (count($subArr) === 2) {
                // Have net/mask
                $outputArr[] = array('net' => $subArr[0], 'mask' => $subArr[1]);
            } else {
                // have ip:port
                $subArr = explode(":", $value);
                $outputArr[] = array('ip' => $subArr[0], 'port' => $subArr[1]);
            }
        }
        return $outputArr;
    }

    private function convertArrayToCsv(array $arrayToConvert) {
        // About to save to db so need to convert to string
        // Take an array form of net mask or ip port and convert to a csv
        // sub arrays are separated by ";"
        if (empty($arrayToConvert)) {
            return '';
        }
        $output = array();
        // Internal is always element 0, nets and ips start at element 1.
        if ((isset($arrayToConvert[1]['net'])) || (isset($arrayToConvert[0]['internal']))) {
            // Have net masks
            foreach ($arrayToConvert as $netValue) {
                if (isset($netValue['internal'])) {
                    $output[] = 'internal';
                    continue;
                }
                if (empty($netValue['net'])) {
                    // If network not set, user error, has added empty row so delete
                    continue;
                }
                // If the mask has not been set, set to this subnet
                $netValue['mask'] = (empty($netValue['mask'])) ? "255.255.255.0" : $netValue['mask'];
                $output[] = implode('/', $netValue);
            }
        } else {
            // Have ip addresses
            foreach ($arrayToConvert as $ipArr) {
                if (isset($ipArr['internal'])) {
                    // should not be set for an ip address
                    continue;
                }
                if (empty($ipArr['ip'])) {
                    // If ip not set, user error, has added empty row so delete
                    continue;
                }
                $ipArr['port'] = (empty($ipArr['port'])) ? "2000" : $ipArr['port'];
                $output[] = implode(':', $ipArr);
            }
        }
        return implode(';', $output);
    }

    private function getIpInformation($type = '') {
        $interfaces = array();
        $result = array();
        switch ($type) {
            case 'ip4':
                exec("/sbin/ip -4 -o addr", $result, $ret);
                break;
            case 'ip6':
                exec("/sbin/ip -6 -o addr", $result, $ret);
                break;
            default:
                exec("/sbin/ip -o addr", $result, $ret);
                break;
        }
        if (!is_array($result)) {
            return $interfaces;
        }
        foreach ($result as $line) {
            $vals = preg_split("/\s+/", $line);
            if (!isset($vals[1], $vals[2], $vals[3])) {
                continue;
            }
            if ($vals[3] == "mtu") {
                continue;
            }
            if ($vals[2] != "inet" && $vals[2] != "inet6") {
                continue;
            }
            if (preg_match("/(.+?)(?:@.+)?:$/", $vals[1], $res)) {
                continue;
            }
            $ret = preg_match("/(\d*+.\d*+.\d*+.\d*+)[\/(\d*+)]*/", $vals[3], $ip);
            $interfaces[$vals[1] . ':' . $vals[2]] = array('name' => $vals[1], 'type' => $vals[2], 'ip' => (empty($ip[1]) ? '' : $ip[1]));
        }
        return $interfaces;
    }

    private function before($thing, $inthat) {
        if ($inthat === null || $inthat === '') {
            return '';
        }
        $pos = strpos($inthat, $thing);
        return ($pos !== false) ? substr($inthat, 0, $pos) : $inthat;
    }

    private function array_key_exists_recursive($key, $arr) {
        if (array_key_exists($key, $arr)) {
            return true;
        }
        foreach ($arr as $currentKey => $value) {
            if (is_array($value)) {
                return $this->array_key_exists_recursive($key, $value);
            }
        }
        return false;
    }

    private function strpos_array($haystack, $needles) {
        if (is_array($needles)) {
            foreach ($needles as $str) {
                if (is_array($str)) {
                    $pos = $this->strpos_array($haystack, $str);
                } else {
                    $pos = strpos($haystack, $str);
                }
                if ($pos !== FALSE) {
                    return $pos;
                }
            }
        } else {
            return strpos($haystack, $needles);
        }
        return FALSE;
    }
    private function getTableDefaults($table, $trim_underscore = true) {
        $def_val = array();
        // TODO: This is ugly and overkill - needs to be cleaned up in dbinterface
        if ($table == 'sccpsettings') {
            // sccpsettings has a different structure and already have values in $sccpvalues
            return $this->sccpvalues;
        }
        $sccpTableDesc = $this->dbinterface->getSccpDeviceTableData("get_columns_{$table}");
        foreach ($sccpTableDesc as $key => $data) {
            // function has 2 roles: return actual table keys (trim_underscore = false)
            // return sanitised keys to add defaults (trim_underscore = true)
            if ($trim_underscore) {
                // Remove any leading (or trailing but should be none) underscore
                // These are only used to hide fields from chan-sccp for compatibility
                $key = trim($key,'_');
            }
            $def_val[$key] = array("keyword" => $key, "data" => $data['Default'], "seq" => "99");
        }
        return $def_val;
    }

    private function getTableEnums($table, $trim_underscore = true) {
        $enumFields = array();
        $sccpTableDesc = $this->dbinterface->getSccpDeviceTableData("get_columns_{$table}");
        foreach ($sccpTableDesc as $key => $data) {
            // function has 2 roles: return actual table keys (trim_underscore = false)
            // return sanitised keys to add defaults (trim_underscore = true)
            if ($trim_underscore) {
                // Remove any leading (or trailing but should be none) underscore
                // These are only used to hide fields from chan-sccp for compatibility
                $key = trim($key,'_');
            }
            $typeArray = explode('(', $data['Type']);
            if ($typeArray[0] == 'enum') {
                $enumOptions = explode(',', trim($typeArray[1],')'));
                $enumFields[$key] = $enumOptions;
            }
        }
        return $enumFields;
    }

    private function findAllFiles($searchDir, $file_mask = array(), $mode = 'full') {
        $result = array();
        if (!is_dir($searchDir)) {
            return $result;
        }
        foreach (array_diff(scandir($searchDir),array('.', '..')) as $value) {
            if (is_file("$searchDir/$value")) {
                $extFound = '';
                $foundFile = true;
                if (!empty($file_mask)) {
                    $foundFile = false;
                    foreach ($file_mask as $k) {
                        if (strpos($value, $k) !== false) {
                            $foundFile = true;
                            $extFound = $k;
                            break;
                        }
                    }
                }
                if ($foundFile) {
                    switch ($mode) {
                        case 'fileonly':
                            $result[] = $value;
                            break;
                        case 'fileBaseName':
                            $result[] = basename("/$value", $extFound);
                            break;
                        case 'dirFileBaseName':
                            $result[] = $searchDir . "/" . basename("/$value", $extFound);
                            break;
                        default:
                            $result[] = "$searchDir/$value";
                            break;
                    }
                }
                continue;
            }
            // Now iterate over sub directories
            $sub_find = $this->findAllFiles("$searchDir/$value", $file_mask, $mode);
            if (!empty($sub_find)) {
                foreach ($sub_find as $sub_value) {
                    $result[] = $sub_value;
                }
            }
        }
        return $result;
    }

    function is_assoc($array) {
        foreach (array_keys($array) as $k => $v) {
            if ($k !== $v)
              return true;
            }
        return false;
    }

    function tftpReadTestFile($remoteFileName, $host = "127.0.0.1")
    {
        // https://datatracker.ietf.org/doc/html/rfc1350
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        // Set timeout so that do not hang if no data received.
        socket_set_option($socket,SOL_SOCKET, SO_RCVTIMEO, array('sec'=>1, 'usec'=>0));

        if ($socket) {
            $port = 69;   // Initial TFTP port. Changed in received packet.

            // create the RRQ request packet
            $packet = chr(0) . chr(1) . $remoteFileName . chr(0) . 'netascii' . chr(0);
            // UDP is connectionless, so we just send it.
            socket_sendto($socket, $packet, strlen($packet), MSG_EOR, $host, $port);

            $buffer = null;
            $port = "";
            $ret = "";
            // MSG_WAITALL is blocking but socket has timeout set to 1 sec.
            $numbytes = socket_recvfrom($socket, $buffer, 84, MSG_WAITALL, $host, $port);

            if ($numbytes < 2) {
                // socket has timed out before data received.
                return false;
            }
            // unpack the returned buffer and discard the first two bytes.
            $pkt = unpack("nopcode/nblockno/a*data", $buffer);

            // send ack and close socket.
            $packet = chr(4) . chr($pkt["blockno"]);
            socket_sendto($socket, $packet, strlen($packet), MSG_EOR, $host, $port);

            socket_close($socket);

            if ($pkt["opcode"] == 3 && $numbytes) {
                return $pkt["data"];
            }
        }
        return false;
    }

    public function initialiseConfInit(){
        $read_config = \FreePBX::LoadConfig()->getConfig('sccp.conf');
        $sccp_conf_init = array();
        $sccp_conf_init['general'] = isset($read_config['general']) ? $read_config['general'] : array();
        foreach (is_array($read_config) ? $read_config : array() as $key => $value) {
            if (isset($read_config[$key]['type'])) { // copy soft key
                if ($read_config[$key]['type'] == 'softkeyset') {
                    $sccp_conf_init[$key] = $read_config[$key];
                }
            }
        }
        return $sccp_conf_init;
    }


    public function checkTftpMapping(){
        exec('in.tftpd -V', $tftpInfo);
        $info['TFTP Server'] = array('Version' => 'Not Found', 'about' => 'Mapping not available');

        if (isset($tftpInfo[0])) {
            $tftpInfo = explode(',',$tftpInfo[0]);
            $info['TFTP Server'] = array('Version' => $tftpInfo[0], 'about' => 'Mapping not available');
            $tftpInfo[1] = trim($tftpInfo[1]);
            $this->sccpvalues['tftp_rewrite']['data'] = 'off';
            if ($tftpInfo[1] == 'with remap') {
                $info['TFTP Server'] = array('Version' => $tftpInfo[0], 'about' => $tftpInfo[1]);

                $remoteFileName = ".sccp_manager_remap_probe_sentinel_temp".mt_rand(0, 9999999).".tlzz";
                $remoteFileContent = "# This is a test file created by Sccp_Manager. It can be deleted without impact";
                $testFtpDir = ($this->sccpvalues['tftp_path']['data'] ?? '/tftpboot') . '/settings';

                // write a sentinel to a tftp subdirectory to see if mapping is working

                if (is_dir($testFtpDir) && is_writable($testFtpDir)) {
                    $tempFile = "{$testFtpDir}/{$remoteFileName}";
                    file_put_contents($tempFile, $remoteFileContent);
                    // try to pull the written file through tftp.
                    // this way we can determine if mapping is active and using sccp_manager maps
                    if ($remoteFileContent == $this->tftpReadTestFile($remoteFileName)) {
                        //found the file and contents are correct
                        $this->sccpvalues['tftp_rewrite']['data'] = 'pro';
                    } else {
                        // Did not find sentinel so mapping not available
                        $this->sccpvalues['tftp_rewrite']['data'] = 'off';
                    }
                    unlink($tempFile);
                }
                return true;
            }
        }
        return false;
    }
    // helper function to save xml with proper indentation
    public function saveXml($xml, $filename) {
       $dom = new \DOMDocument("1.0");
       $dom->preserveWhiteSpace = false;
       $dom->formatOutput = true;
       $dom->loadXML($xml->asXML());
       $dom->save($filename);
    }

    /**
     * Convert github.com raw URL to raw.githubusercontent.com to avoid redirect issues (empty 0-byte downloads).
     * @param string $url e.g. https://github.com/dkgroot/provision_sccp/raw/master/tftpboot/firmware/7975/file.loads
     * @return string e.g. https://raw.githubusercontent.com/dkgroot/provision_sccp/master/tftpboot/firmware/7975/file.loads
     */
    private function normalizeGitHubRawUrl(string $url): string {
        if (preg_match('#^https?://github\.com/([^/]+)/([^/]+)/raw/([^/]+)/(.*)$#', $url, $m)) {
            return 'https://raw.githubusercontent.com/' . $m[1] . '/' . $m[2] . '/' . $m[3] . '/' . $m[4];
        }
        return $url;
    }

    /**
     * Download a URL to a file. Tries original chan-sccp method (file_get_contents on github raw URL) first, then cURL/raw.githubusercontent.com.
     * @param string $url Full URL (e.g. https://github.com/dkgroot/provision_sccp/raw/master/...)
     * @param string $destPath Absolute path to save file
     * @return bool true on success, false on failure
     */
    public function fetchUrlToFile(string $url, string $destPath): bool {
        // 1) Original chan-sccp/sccp_manager method: file_get_contents (follows redirect to raw content)
        $content = @file_get_contents($url, false, $this->getHttpStreamContext());
        if ($content !== false) {
            if (file_put_contents($destPath, $content) !== false) {
                if (filesize($destPath) === 0 && preg_match('/\.(loads|sbn|bin|zup|sbin|SBN|LOADS)$/i', $destPath)) {
                    @unlink($destPath);
                    return false;
                }
                return true;
            }
        }

        // 2) Direct raw.githubusercontent.com + cURL (no redirect)
        $url = $this->normalizeGitHubRawUrl($url);
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return false;
            }
            $fp = @fopen($destPath, 'wb');
            if ($fp === false) {
                curl_close($ch);
                return false;
            }
            curl_setopt_array($ch, [
                CURLOPT_FILE => $fp,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; FreePBX-sccp_manager/1.0; +https://github.com/timspb/sccp_manager)',
                CURLOPT_HTTPHEADER => ['Accept: application/octet-stream'],
            ]);
            $ok = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            fclose($fp);
            if (!$ok || $code < 200 || $code >= 300) {
                if ($ok && file_exists($destPath)) {
                    @unlink($destPath);
                }
                if ($err !== '') {
                    error_log('sccp_manager fetchUrlToFile: ' . $err . ' [URL: ' . $url . ']');
                }
                return false;
            }
            // Reject 0-byte firmware/config files (redirect or LFS often yields empty file)
            if (filesize($destPath) === 0 && preg_match('/\.(loads|sbn|bin|zup|sbin|SBN|LOADS)$/i', $destPath)) {
                @unlink($destPath);
                error_log('sccp_manager fetchUrlToFile: downloaded file is 0 bytes [URL: ' . $url . ']');
                return false;
            }
            return true;
        }
        $ctx = stream_context_create([
            'http' => [
                'follow_location' => 1,
                'timeout' => 60,
                'user_agent' => 'Mozilla/5.0 (compatible; FreePBX-sccp_manager/1.0; +https://github.com/timspb/sccp_manager)',
            ],
            'ssl' => ['verify_peer' => true],
        ]);
        $content = @file_get_contents($url, false, $ctx);
        if ($content === false) {
            return false;
        }
        if (file_put_contents($destPath, $content) === false) {
            return false;
        }
        // Reject 0-byte firmware files in fallback path too
        if (strlen($content) === 0 && preg_match('/\.(loads|sbn|bin|zup|sbin|SBN|LOADS)$/i', $destPath)) {
            @unlink($destPath);
            return false;
        }
        return true;
    }

    /**
     * Default stream context for HTTP (follow redirects, timeout). Same as original chan-sccp/sccp_manager.
     */
    private function getHttpStreamContext() {
        return stream_context_create([
            'http' => [
                'follow_location' => 1,
                'timeout' => 60,
            ],
            'ssl' => ['verify_peer' => true],
        ]);
    }

    /**
     * Ensure masterFilesStructure.xml exists and is valid XML in tftp root.
     * Tries: (1) original chan-sccp method file_get_contents(github raw URL), (2) fetchUrlToFile (cURL/raw.githubusercontent.com), (3) bundled contrib/masterFilesStructure.xml.
     */
    public function getFileListFromProvisioner(string $tftpRootPath): bool {
        $tftpRootPath = rtrim($tftpRootPath, '/\\');
        $provisionerUrl = 'https://github.com/dkgroot/provision_sccp/raw/master/';
        $url = $provisionerUrl . 'tools/tftpbootFiles.xml';
        $dest = $tftpRootPath . '/masterFilesStructure.xml';
        $bundled = dirname(__DIR__) . '/contrib/masterFilesStructure.xml';

        if (!is_dir($tftpRootPath)) {
            return false;
        }
        if (!is_writable($tftpRootPath)) {
            error_log('SCCP Manager: TFTP root is not writable: ' . $tftpRootPath . ' — fix: sudo chown -R www-data:www-data ' . $tftpRootPath . ' (or the user that runs the web server)');
            return false;
        }

        // 1) Original method: file_get_contents + file_put_contents (as in chan-sccp/sccp_manager)
        $content = @file_get_contents($url, false, $this->getHttpStreamContext());
        if ($content !== false && $content !== '' && file_put_contents($dest, $content) !== false && @simplexml_load_file($dest) !== false) {
            return true;
        }
        if (file_exists($dest)) {
            @unlink($dest);
        }

        // 2) cURL / raw.githubusercontent.com
        $ok = $this->fetchUrlToFile($url, $dest);
        if ($ok && @simplexml_load_file($dest) !== false) {
            return true;
        }
        if ($ok) {
            @unlink($dest);
        }

        // 3) Bundled XML
        if (is_readable($bundled) && copy($bundled, $dest)) {
            if (@simplexml_load_file($dest) !== false) {
                return true;
            }
            @unlink($dest);
        }
        return false;
    }

    public function getChanSccpSettings() {
        // This is a utility function for debug only, and is not used by core code
        foreach (array('general','line', 'device') as $section) {
            $sysConfig = $this->aminterface->getSCCPConfigMetaData($section);
            dbug($sysConfig);
        }
        unset($sysConfig);
    }

    public function createDefaultSccpConfig(array $sccpvalues, string $asteriskPath, $conf_init) {
        global $cnf_wr;
        // Make sccp.conf data
        // [general] section
        // TODO: Need to review sccpsettings seq numbering, as will speed this up, and remove the need for $permittedSettings.
        $cnf_wr = \FreePBX::WriteConfig();
        //clear old general settings, and initiate with allow/disallow and permit/deny keys in correct order
        $conf_init['general'] = array();
        $conf_init['general']['disallow'] = 'all';
        $conf_init['general']['allow'] = '';
        $conf_init['general']['deny'] = '0.0.0.0/0.0.0.0';
        $conf_init['general']['permit'] = '0.0.0.0/0.0.0.0';
        // permitted chan-sccp settings array
        $permittedSettings = array(
                          'debug', 'servername', 'keepalive', 'context', 'dateformat', 'bindaddr', 'port', 'secbindaddr', 'secport', 'disallow', 'allow', 'deny', 'permit',
                          'localnet', 'externip', 'externrefresh', 'firstdigittimeout', 'digittimeout', 'digittimeoutchar', 'recorddigittimeoutchar', 'simulate_enbloc',
                          'ringtype', 'autoanswer_ring_time', 'autoanswer_tone', 'remotehangup_tone', 'transfer', 'transfer_tone', 'transfer_on_hangup', 'dnd_tone',
                          'callwaiting_tone', 'callwaiting_interval', 'musicclass', 'language', 'callevents', 'accountcode', 'sccp_tos', 'sccp_cos', 'audio_tos',
                          'audio_cos', 'video_tos', 'video_cos', 'echocancel', 'silencesuppression', 'earlyrtp', 'dndFeature', 'private', 'mwilamp', 'mwioncall',
                          'cfwdall', 'cfwdbusy', 'cfwdnoanswer', 'cfwdnoanswer_timeout', 'nat', 'directrtp', 'allowoverlap', 'pickup_modeanswer',
                          'callhistory_answered_elsewhere', 'amaflags', 'callanswerorder', 'devicetable', 'linetable', 'meetmeopts', 'jbenable', 'jbforce',
                          'jblog', 'jbmaxsize', 'jbresyncthreshold', 'jbimpl', 'hotline_enabled', 'hotline_extension', 'hotline_context', 'hotline_label', 'fallback',
                          'backoff_time', 'server_priority');

        foreach ($sccpvalues as $key => $value) {
            if (!in_array($key, $permittedSettings, true)) {
                continue;
            }
            if ($value['seq'] == 0) {
                switch ($key) {
                    case "allow":
                    case "disallow":
                    case "deny":
                    case "localnet":
                    case "permit":
                        $conf_init['general'][$key] = explode(';', $value['data']);
                        break;
                    case "devlang":
                        /*
                        $lang_data = $this->extconfigs->getExtConfig('sccp_lang', $value['data']);
                        if (!empty($lang_data)) {
                            // TODO:  will always get here, but lang_data['codepage'] will be empty as not a valid key
                            $this->sccp_conf_init['general']['phonecodepage'] = $lang_data['codepage'];
                        }
                        break;
                        */
                    case "netlang": // Remove Key
                    case "tftp_path":
                    case "sccp_compatible":    // This is equal to SccpDBmodel
                        break;
                    default:
                        if (!empty($value['data'])) {
                            $conf_init['general'][$key] = $value['data'];
                        }
                }
            }
        }
        //
        // ----- It is a very bad idea to add an external configuration file "sccp_custom.conf" !!!!
        // This will complicate solving problems caused by unexpected solutions from users.
        //
        if (file_exists($asteriskPath . "/sccp_custom.conf")) {
            $conf_init['HEADER'] = array(
                ";                                                                                ;",
                ";  It is a very bad idea to add an external configuration file !!!!              ;",
                ";  This will complicate solving problems caused by unexpected solutions          ;",
                ";  from users.                                                                   ;",
                ";--------------------------------------------------------------------------------;",
                "#include sccp_custom.conf"
            );
        }
        $cnf_wr->WriteConfig('sccp.conf', $conf_init);
    }

    public function initVarfromXml() {
        if ((array) $this->xml_data) {
            foreach ($this->xml_data->xpath('//page_group') as $item) {
                foreach ($item->children() as $child) {
                    $seq = 0;
                    if (!empty($child['seq'])) {
                        $seq = (string) $child['seq'];
                    }
                    if ($seq < 99) {
                        if ($child['type'] == 'IE') {
                            foreach ($child->xpath('input') as $value) {
                                $tp = 0;
                                if (empty($value->value)) {
                                    $datav = (string) $value->default;
                                } else {
                                    $datav = (string) $value->value;
                                }
                                if (strtolower($value->type) == 'number') {
                                    $tp = 1;
                                }
                                if (empty($this->sccpvalues[(string) $value->name])) {
                                    $this->sccpvalues[(string) $value->name] = array('keyword' => (string) $value->name, 'data' => $datav, 'type' => $tp, 'seq' => $seq, 'systemdefault' => '');
                                }
                            }
                        }
                        if ($child['type'] == 'IS' || $child['type'] == 'IED') {
                            if (empty($child->value)) {
                                $datav = (string) $child->default;
                            } else {
                                $datav = (string) $child->value;
                            }
                            if (empty($this->sccpvalues[(string) $child->name])) {
                                $this->sccpvalues[(string) $child->name] = array('keyword' => (string) $child->name, 'data' => $datav, 'type' => '2', 'seq' => $seq, 'systemdefault' => '');
                            }
                        }
                        if (in_array($child['type'], array('SLD', 'SLS', 'SLT', 'SLNA', 'SLDA', 'SL', 'SLM', 'SLZ', 'SLTZN', 'SLA'))) {
                            if (empty($child->value)) {
                                $datav = (string) $child->default;
                            } else {
                                $datav = (string) $child->value;
                            }
                            if (empty($this->sccpvalues[(string) $child->name])) {
                                $this->sccpvalues[(string) $child->name] = array('keyword' => (string) $child->name, 'data' => $datav, 'type' => '2', 'seq' => $seq, 'systemdefault' => '');
                            }
                        }
                    }
                }
            }
        }
    }

    public function getSipConfig() {
        // Only called from sccp_manager class when saving SIP device
        $result = array();

        $tmp_binds = \FreePBX::Sipsettings()->getBinds();
        $if_list = $this->getIpInformation('ip4');
        if (!is_array($tmp_binds)) {
            // FreePBX has no sip bindings.
            die_freepbx(_("SIP server configuration error ! No SIP protocols enabled"));
        }
        foreach ($tmp_binds as $fpbx_protocol => $fpbx_bind) {
            foreach ($fpbx_bind as $protocol_ip => $protocol_port_arr) {
                if (empty($protocol_port_arr)) {
                    continue;
                }
                if (($protocol_ip == '0.0.0.0') || ($protocol_ip == '[::]')) {
                    foreach ($if_list as $if_type => $if_data) {
                        if ($if_data['ip'] == "127.0.0.1") {
                            continue;
                        }
                        if (empty($result[$fpbx_protocol][$if_data['ip']])) {
                            $result[$fpbx_protocol][$if_data['ip']]= $protocol_port_arr;
                        } else {
                            $result[$fpbx_protocol][$if_data['ip']]= array_merge($result[$fpbx_protocol][$if_data['ip']],$protocol_port_arr);
                        }
                        $result[$fpbx_protocol][$if_data['ip']]['ip']=$if_data['ip'];
                    }
                } else {
                    $result[$fpbx_protocol][$protocol_ip]=$protocol_port_arr;
                    $result[$fpbx_protocol][$protocol_ip]['ip']=$protocol_ip;
                }
            }
        }
        if (empty($result)) {
            die_freepbx(_("SIP server configuration error ! No SIP protocols enabled"));
        }
        return $result;
    }
}
?>
