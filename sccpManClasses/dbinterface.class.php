<?php

/**
 *
 * Core Comsnd Interface
 *
 *
 */

namespace FreePBX\modules\Sccp_manager;

class dbinterface
{
    private $val_null = 'NONE'; /// REPLACE to null Field
    /** @var bool|null */
    private $hasSccpDeviceConfigView = null;
    /** @var array<string, array<string, bool>> */
    private $knownTableColumns = array();
    /** @var array<int, string> */
    private static $allowedDefaultTables = array(
        'sccpdevice',
        'sccpline',
        'sccpuser',
        'sccpdevmodel',
        'sccpsettings',
        'sccpbuttonconfig',
    );

    /** Integer columns per table for MariaDB strict mode (empty string -> 0 or NULL) */
    private static $integerColumns = array(
        'sccpdevice' => array('profileid', 'keepalive'),
        'sccpdevmodel' => array('dns', 'buttons', 'enabled'),
        'sccpuser' => array(),
    );

    /** @var object|null Parent Sccp_manager instance */
    public $paren_class = null;
    /** @var \PDO|object */
    public $db;

    public function __construct($parent_class = null)
    {
        $this->paren_class = $parent_class;
        $this->db = \FreePBX::Database();
    }

    private function canUseSccpDeviceConfigView()
    {
        if ($this->hasSccpDeviceConfigView !== null) {
            return $this->hasSccpDeviceConfigView;
        }
        try {
            $checkStmt = $this->db->prepare("SELECT 1 FROM sccpdeviceconfig LIMIT 1");
            $checkStmt->execute();
            $this->hasSccpDeviceConfigView = true;
        } catch (\PDOException $e) {
            $this->hasSccpDeviceConfigView = false;
        }
        return $this->hasSccpDeviceConfigView;
    }

    private function isSafeIdentifier($name)
    {
        return (bool) (is_string($name) && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name));
    }

    private function tableHasColumn($table, $field)
    {
        if (!$this->isSafeIdentifier($table) || !$this->isSafeIdentifier($field)) {
            return false;
        }
        if (!isset($this->knownTableColumns[$table])) {
            try {
                $stmt = $this->db->prepare("DESCRIBE `{$table}`");
                $stmt->execute();
                $columns = array();
                foreach ((array) $stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                    if (!empty($row['Field'])) {
                        $columns[$row['Field']] = true;
                    }
                }
                $this->knownTableColumns[$table] = $columns;
            } catch (\PDOException $e) {
                return false;
            }
        }
        return !empty($this->knownTableColumns[$table][$field]);
    }

    private function getSccpDeviceFieldList($requestedFields)
    {
        if (empty($requestedFields)) {
            return 'name, name as mac, type, button, addon, description';
        }
        switch ((string) $requestedFields) {
            case 'all':
                return '*';
            case 'sip_ext':
                return 'button as sip_lines, description as description, addon';
            default:
                // Disallow arbitrary SQL field fragments from caller input.
                return 'name, name as mac, type, button, addon, description';
        }
    }

    public function info()
    {
        $Ver = '17.0.1.1';    // This should be updated
        return array('Version' => $Ver,
            'about' => 'Data access interface ver: ' . $Ver);
    }

    /*
     * Core Access Function
     */
    public function get_db_SccpTableByID($dataid, $data = array(), $indexField = '')
    {
        $result = array();
        $raw = $this->getSccpDeviceTableData($dataid, $data);
        if (empty($raw) || empty($indexField)) {
            return $raw;
        }
        foreach ($raw as $value) {
            $id = $value[$indexField];
            $result[$id] = $value;
        }
        return $result;
    }

    public function getSccpDeviceTableData(string $dataid, $data = array())
    {
        // $stmt is a single row fetch, $stmts is a fetchAll while stmtU is fetchAll UNIQUE
        $stmt = '';
        $stmts = '';
        $stmtU = '';
        $phoneGridTable = null; // for fallback to sccpdevice when view fails

        switch ($dataid) {
            case 'extGrid':
                // only called by getExtensionGrid from hardware.extension.php view
                $stmts = $this->db->prepare("SELECT sccpline.name, sccpline.label, sccpbuttonconfig.ref AS mac, '-|-' AS line_status
                              FROM sccpline LEFT JOIN sccpbuttonconfig
                              ON sccpline.name = TRIM(TRAILING '!silent' FROM sccpbuttonconfig.name) ORDER BY sccpline.name");
                break;
            case 'SccpExtension':
                if (empty($data['name'] ?? '')) {
                    $stmtU = $this->db->prepare('SELECT name, sccpline.* FROM sccpline ORDER BY name');
                } else {
                    $stmts = $this->db->prepare('SELECT * FROM sccpline WHERE name = :name');
                    $stmts->bindValue(':name', $data['name'] ?? '', \PDO::PARAM_STR);
                }
                break;
            case 'phoneGrid':
                // Prefer sccpdeviceconfig view; fall back to sccpdevice if view fails (missing, broken, or SQL mode)
                $phoneGridType = $data['type'] ?? 'sccp';
                $tableToUse = $this->canUseSccpDeviceConfigView() ? 'sccpdeviceconfig' : 'sccpdevice';
                $phoneGridTable = $tableToUse;
                if ($phoneGridType === 'cisco-sip') {
                    $stmts = $this->db->prepare("SELECT name, type, '' as button, addon, description, 'not connected' AS status, '- -' AS address, 'N' AS new_hw FROM {$tableToUse} WHERE type LIKE '%-sip' ORDER BY name");
                } else {
                    $stmts = $this->db->prepare("SELECT name, type, '' as button, addon, description, 'not connected' AS status, '- -' AS address, 'N' AS new_hw FROM {$tableToUse} WHERE type NOT LIKE '%-sip' ORDER BY name");
                }
                break;
            case 'SccpDevice':
                $fld = $this->getSccpDeviceFieldList($data['fields'] ?? '');
                if (!empty($data['name'])) {      //either filter by name or by type
                    // Check if sccpdeviceconfig view exists
                    $tableToUse = $this->canUseSccpDeviceConfigView() ? 'sccpdeviceconfig' : 'sccpdevice';
                    if ($tableToUse === 'sccpdevice') {
                        $fld = str_replace('button', "'' as button", $fld);
                    } 
                    $stmt = $this->db->prepare('SELECT ' . $fld . ' FROM ' . $tableToUse . ' WHERE name = :name  ORDER BY name');
                    $stmt->bindValue(':name', $data['name'] ?? '', \PDO::PARAM_STR);
                } elseif (!empty($data['type'] ?? '')) {
                    // Check if sccpdeviceconfig view exists
                    $tableToUse = $this->canUseSccpDeviceConfigView() ? 'sccpdeviceconfig' : 'sccpdevice';
                    if ($tableToUse === 'sccpdevice') {
                        // Adjust field list for base table
                        $fld = str_replace('button', "'' as button", $fld);
                    } 
                    
                    switch ($data['type'] ?? '') {
                        case "cisco-sip":
                            $stmts = $this->db->prepare("SELECT {$fld} FROM {$tableToUse} WHERE type LIKE '%-sip' ORDER BY name");
                            break;
                        case "cisco":      // Fall through to default intentionally
                        default:
                            $stmts = $this->db->prepare("SELECT {$fld} FROM {$tableToUse} WHERE type not LIKE '%-sip' ORDER BY name");
                            break;
                    }
                } else {      //no filter and no name provided - return all
                    // Check if sccpdeviceconfig view exists
                    $tableToUse = $this->canUseSccpDeviceConfigView() ? 'sccpdeviceconfig' : 'sccpdevice';
                    if ($tableToUse === 'sccpdevice') {
                        $fld = str_replace('button', "'' as button", $fld);
                    } 
                    $stmts = $this->db->prepare("SELECT  {$fld}  FROM {$tableToUse} ORDER BY name");
                }
                break;
            case 'get_columns_sccpdevice':
                $stmtU = $this->db->prepare('DESCRIBE sccpdevice');
                break;
            case 'get_columns_sccpuser':
                $stmts = $this->db->prepare('DESCRIBE sccpuser');
                break;
            case 'get_columns_sccpline':
                $stmtU = $this->db->prepare('DESCRIBE sccpline');
                break;
            case 'get_sccpdevice_byid':
                $stmt = $this->db->prepare('SELECT t1.*, types.dns,  types.buttons, types.loadimage, types.loadinformationid, types.nametemplate as nametemplate,
                        addon.buttons as addon_buttons FROM sccpdevice AS t1
                        LEFT JOIN sccpdevmodel as types ON t1.type=types.model
                        LEFT JOIN sccpdevmodel as addon ON t1.addon=addon.model WHERE name = :name');
                $stmt->bindValue(':name', $data['id'] ?? '', \PDO::PARAM_STR);
                break;
            case 'get_sccpuser':
                $stmt = $this->db->prepare('SELECT * FROM sccpuser WHERE name = :name');
                $stmt->bindValue(':name', $data['id'] ?? '', \PDO::PARAM_STR);
                break;
            case 'getAssignedExtensions':
                // all extensions that are designed as default lines
                $stmtU = $this->db->prepare("SELECT DISTINCT name, name FROM sccpbuttonconfig WHERE buttontype = 'line' AND instance =1");
                break;
            case 'getDefaultLine':
                $stmt = $this->db->prepare("SELECT name FROM sccpbuttonconfig WHERE ref = :ref and instance =1 and buttontype = 'line'");
                $stmt->bindValue(':ref', $data['id'] ?? '', \PDO::PARAM_STR);
                break;
            case 'get_sccpdevice_buttons':
                $sql = '';
                if (!empty($data['buttontype'])) {
                    $sql .= 'buttontype = :buttontype';
                }
                if (!empty($data['id'])) {
                    $sql .= (empty($sql)) ? 'ref = :ref' : ' and ref = :ref';
                }
                if (!empty($sql)) {
                    $stmts = $this->db->prepare("SELECT * FROM sccpbuttonconfig WHERE {$sql} ORDER BY instance");
                    // Now bind labels - only bind label if it exists or bind will create exception.
                    // can only bind once have prepared, so need to test again.
                    if (!empty($data['buttontype'] ?? '')) {
                        $stmts->bindValue(':buttontype', $data['buttontype'] ?? '', \PDO::PARAM_STR);
                    }
                    if (!empty($data['id'] ?? '')) {
                        $stmts->bindValue(':ref', $data['id'] ?? '', \PDO::PARAM_STR);
                    }
                } else {
                    $raw_settings = array();
                }
                break;
                // No default case so will give exception of $raw_settings undefined if the
                // dataid is not in the switch.
        }
        try {
            if (!empty($stmt)) {
                $stmt->execute();
                $raw_settings = $stmt->fetch(\PDO::FETCH_ASSOC);
            } elseif (!empty($stmts)) {
                $stmts->execute();
                $raw_settings = $stmts->fetchAll(\PDO::FETCH_ASSOC);
            } elseif (!empty($stmtU)) {
                //returns an assoc array indexed on first field
                $stmtU->execute();
                $raw_settings = $stmtU->fetchAll(\PDO::FETCH_ASSOC|\PDO::FETCH_UNIQUE);
            }
        } catch (\PDOException $e) {
            // If phoneGrid failed on view (e.g. SQL mode / view definition), retry with base table
            if ($dataid === 'phoneGrid' && $phoneGridTable === 'sccpdeviceconfig') {
                try {
                    $gridType = $data['type'] ?? 'sccp';
                    if ($gridType === 'cisco-sip') {
                        $stmts = $this->db->prepare("SELECT name, type, '' as button, addon, description, 'not connected' AS status, '- -' AS address, 'N' AS new_hw FROM sccpdevice WHERE type LIKE '%-sip' ORDER BY name");
                    } else {
                        $stmts = $this->db->prepare("SELECT name, type, '' as button, addon, description, 'not connected' AS status, '- -' AS address, 'N' AS new_hw FROM sccpdevice WHERE type NOT LIKE '%-sip' ORDER BY name");
                    }
                    $stmts->execute();
                    $raw_settings = $stmts->fetchAll(\PDO::FETCH_ASSOC);
                } catch (\PDOException $e2) {
                    error_log("Database error in getSccpDeviceTableData: " . $e2->getMessage());
                    $raw_settings = array();
                }
            } else {
                error_log("Database error in getSccpDeviceTableData: " . $e->getMessage());
                $raw_settings = array();
            }
        }
        if (!isset($raw_settings)) {
            $raw_settings = array();
        }
        if (!is_array($raw_settings)) {
            $raw_settings = array();
        }
        return $raw_settings;
    }

    public function get_db_SccpSetting()
    {
        try {
            $stmt = $this->db->prepare('SELECT keyword, sccpsettings.* FROM sccpsettings ORDER BY type, seq');
            $stmt->execute();
            $settingsFromDb = $stmt->fetchAll(\PDO::FETCH_ASSOC|\PDO::FETCH_UNIQUE);
            
            // Ensure we return an array for PHP 8.3 compatibility
            if (!is_array($settingsFromDb)) {
                $settingsFromDb = array();
            }
            
            return $settingsFromDb;
        } catch (\PDOException $e) {
            error_log("Database error in get_db_SccpSetting: " . $e->getMessage());
            return array();
        }
    }

    public function get_db_sysvalues()
    {
        try {
            $stmt = $this->db->prepare('SHOW VARIABLES LIKE \'%group_concat%\'');
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return is_array($result) ? $result : array();
        } catch (\PDOException $e) {
            error_log("Database error in get_db_sysvalues: " . $e->getMessage());
            return array();
        }
    }

    /*
     *      Get Sccp Device Model information
     */

    function getDb_model_info($get = 'all', $format_list = 'all', $filter = array())
    {
        $stmt = null;
        $sel_inf = '*, 0 as validate';
        if ($format_list === 'model') {
            $sel_inf = "model, vendor, dns, buttons, '-;-' as validate";
        }
        switch ($get) {
            case 'byciscoid':
                if (!empty($filter)) {
                    $model = isset($filter['model']) ? (is_array($filter['model']) ? (string)($filter['model'][0] ?? $filter['model']['model_id'] ?? '') : (string)$filter['model']) : '';
                    if ($model !== '') {
                        if (strpos($model, 'loadInformation') === false) {
                            $model = 'loadInformation' . $model;
                        }
                        $stmt = $this->db->prepare("SELECT {$sel_inf} FROM sccpdevmodel WHERE (loadinformationid = :model ) ORDER BY model");
                        $stmt->bindValue(':model', $model, \PDO::PARAM_STR);
                    } else {
                        $stmt = $this->db->prepare("SELECT {$sel_inf} FROM sccpdevmodel ORDER BY model");
                    }
                    break;
                }
                break;
            case 'byid':
                if (!empty($filter)) {
                    $model = isset($filter['model']) ? (is_array($filter['model']) ? (string)($filter['model']['data'] ?? $filter['model']['model_id'] ?? $filter['model'][0] ?? '') : (string)$filter['model']) : '';
                    if ($model !== '') {
                        $stmt = $this->db->prepare("SELECT  {$sel_inf} FROM sccpdevmodel WHERE model = :model ORDER BY model");
                        $stmt->bindValue(':model', $model, \PDO::PARAM_STR);
                    } else {
                        $stmt = $this->db->prepare("SELECT {$sel_inf} FROM sccpdevmodel ORDER BY model");
                    }
                    break;
                }
                break;
            case 'extension':
                $stmt = $this->db->prepare("SELECT {$sel_inf} FROM sccpdevmodel WHERE (dns = 0) and (enabled = 1) ORDER BY model");
                break;
            case 'enabled':
                //$stmt = $db->prepare('SELECT ' . {$sel_inf} . ' FROM sccpdevmodel WHERE enabled = 1 ORDER BY model'); //previously this fell through to phones.
                //break;  // above includes expansion modules but was not original behaviour so commented out. Falls through to phones.
            case 'phones':
                $stmt = $this->db->prepare("SELECT {$sel_inf} FROM sccpdevmodel WHERE (dns != 0) and (enabled = 1) ORDER BY model");
                break;
            case 'ciscophones':
                $stmt = $this->db->prepare("SELECT {$sel_inf} FROM sccpdevmodel WHERE (dns > 0) and (enabled = 1) AND vendor NOT LIKE '%-sip' ORDER BY model");
                break;
            case 'sipphones':
                $stmt = $this->db->prepare("SELECT {$sel_inf} FROM sccpdevmodel WHERE (dns > 0) and (enabled = 1) AND vendor LIKE '%-sip' ORDER BY model");
                break;
            case 'all':     // Fall through to default
            default:
                $stmt = $this->db->prepare("SELECT {$sel_inf} FROM sccpdevmodel ORDER BY model");
                break;
        }
        if (!$stmt instanceof \PDOStatement) {
            return array();
        }
        
        try {
            $stmt->execute();
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            return is_array($result) ? $result : array();
        } catch (\PDOException $e) {
            error_log("Database error in getDb_model_info: " . $e->getMessage());
            return array();
        }
    }

    function write($table_name = "", $save_value = array(), $mode = 'update', $key_fld = "", $hwid = "")
    {
        // mode clear  - Empty table before update
        // mode update - update / replace record
        $result = false;
        switch ($table_name) {
            case 'sccpsettings':
                if ($mode == 'replace') {  // Change mode name to be more transparent
                    $this->db->prepare('TRUNCATE sccpsettings')->execute();
                    $stmt = $this->db->prepare('INSERT INTO sccpsettings (keyword, data, seq, type, systemdefault) VALUES (:keyword,:data,:seq,:type,:systemdefault)');
                } else {
                    $stmt = $this->db->prepare('REPLACE INTO sccpsettings (keyword, seq, type, data, systemdefault) VALUES (:keyword,:seq,:type,:data,:systemdefault)');
                }
                foreach ($save_value as $key => $dataArr) {
                    if (empty($dataArr)) {
                            continue;
                    }
                    $stmt->bindValue(':keyword', $dataArr['keyword'] ?? '', \PDO::PARAM_STR);
                    $stmt->bindValue(':data', $dataArr['data'] ?? '', \PDO::PARAM_STR);
                    $stmt->bindValue(':seq', $dataArr['seq'] ?? 0, \PDO::PARAM_INT);
                    $stmt->bindValue(':type', $dataArr['type'] ?? 0, \PDO::PARAM_INT);
                    $stmt->bindValue(':systemdefault', $dataArr['systemdefault'] ?? '', \PDO::PARAM_STR);
                    $result = $stmt->execute();
                }
                break;
            case 'sccpdevmodel':    // Fall through to next intentionally
            case 'sccpdevice':      // Fall through to next intentionally
            case 'sccpuser':
                $sql_key = "";
                $sql_var = "";
                $int_cols = isset(self::$integerColumns[$table_name]) ? self::$integerColumns[$table_name] : array();
                foreach ($save_value as $key_v => $data) {
                    if (!empty($sql_var)) {
                        $sql_var .= ', ';
                    }
                    if ($data === $this->val_null) {
                        $sql_var .= $key_v . ' = NULL';
                    } elseif (in_array($key_v, $int_cols, true)) {
                        if ($data === '' || $data === null) {
                            $sql_var .= $key_v . ' = 0';
                        } else {
                            $sql_var .= $key_v . ' = ' . (int) $data;
                        }
                    } else {
                        $sql_var .= $key_v . " = " . $this->db->quote((string) $data);
                    }
                    if ($key_v === $key_fld) {
                        $sql_key = in_array($key_v, $int_cols, true)
                            ? $key_v . ' = ' . (int) $data
                            : $key_v . " = " . $this->db->quote((string) $data);
                    }
                }
                if (!empty($sql_var)) {
                    switch ($mode) {
                        case 'delete':
                            $stmt = $this->db->prepare("DELETE FROM {$table_name} WHERE {$sql_key}");
                            break;
                        case 'update':
                            $stmt = $this->db->prepare("UPDATE {$table_name} SET {$sql_var} WHERE {$sql_key}");
                            break;
                        case 'replace':
                            $stmt = $this->db->prepare("REPLACE INTO {$table_name} SET {$sql_var}");
                            break;
                    }
                    $result = $stmt->execute();
                }
                break;
            case 'sccpbuttons':
                $allowed_reftype = array('sccpdevice', 'sccpuser', 'sipdevice');
                switch ($mode) {
                    case 'delete':
                        $sql = 'DELETE FROM sccpbuttonconfig WHERE ref = :hwid';
                        $stmt = $this->db->prepare($sql);
                        $stmt->bindValue(':hwid', $hwid, \PDO::PARAM_STR);
                        $result = $stmt->execute();
                        break;
                    case 'replace':
                        foreach ($save_value as $button_array) {
                            $reftype = (string)($button_array['reftype'] ?? '');
                            if (!in_array($reftype, $allowed_reftype, true)) {
                                $reftype = 'sccpdevice';
                            }
                            $stmt = $this->db->prepare('UPDATE sccpbuttonconfig SET name =:name WHERE  ref = :ref AND reftype =:reftype AND instance = :instance  AND buttontype = :buttontype AND options = :options');
                            $stmt->bindValue(':ref', $button_array['ref'] ?? '', \PDO::PARAM_STR);
                            $stmt->bindValue(':reftype', $reftype, \PDO::PARAM_STR);
                            $stmt->bindValue(':instance', (int)($button_array['instance'] ?? 0), \PDO::PARAM_INT);
                            $stmt->bindValue(':buttontype', $button_array['buttontype'] ?? '', \PDO::PARAM_STR);
                            $stmt->bindValue(':name', $button_array['name'] ?? '', \PDO::PARAM_STR);
                            $stmt->bindValue(':options', $button_array['options'] ?? '', \PDO::PARAM_STR);
                            $result= $stmt->execute();
                        }
                        break;
                    case 'add':
                        try {
                            foreach ($save_value as $button_array) {
                                $reftype = (string)($button_array['reftype'] ?? '');
                                if (!in_array($reftype, $allowed_reftype, true)) {
                                    $reftype = 'sccpdevice';
                                }
                                $stmt = $this->db->prepare(
                                    "INSERT INTO sccpbuttonconfig SET ref = :ref, reftype = :reftype, instance = :instance, buttontype = :buttontype, name = :name, options = :options " .
                                    "ON DUPLICATE KEY UPDATE name = VALUES(name), options = VALUES(options)"
                                );
                                $stmt->bindValue(':ref', $button_array['ref'] ?? '', \PDO::PARAM_STR);
                                $stmt->bindValue(':reftype', $reftype, \PDO::PARAM_STR);
                                $stmt->bindValue(':instance', (int)($button_array['instance'] ?? 0), \PDO::PARAM_INT);
                                $stmt->bindValue(':buttontype', $button_array['buttontype'] ?? '', \PDO::PARAM_STR);
                                $stmt->bindValue(':name', $button_array['name'] ?? '', \PDO::PARAM_STR);
                                $stmt->bindValue(':options', $button_array['options'] ?? '', \PDO::PARAM_STR);
                                $result = $stmt->execute();
                            }
                        } catch (\PDOException $e) {
                            $msg = $e->getMessage();
                            if ($e->getCode() === '45000' || strpos($msg, 'line does not exist in sccpline') !== false) {
                                $lineName = isset($button_array['name']) ? trim(explode('!', (string)$button_array['name'])[0]) : '';
                                throw new \RuntimeException(
                                    _('Cannot assign line to button: the line does not exist in SCCP Lines. Add the extension in SCCP Lines first.') .
                                    ($lineName !== '' ? ' (' . $lineName . ')' : ''),
                                    0,
                                    $e
                                );
                            }
                            throw $e;
                        }
                        break;
                    case 'clear':
                        // Clear is equivalent of delete + insert. Mode is used in order to activate trigger.
                        $this->write('sccpbuttons', '', $mode = 'delete', '', $hwid);
                        $this->write('sccpbuttons', $save_value, $mode = 'add', '', $hwid);
                        break;
                    // No default case - must be specific in request.
                }
        }
        return $result;
    }
    //******** Get SIP settings *******
    public function getSipTableData(string $dataid, $line = '') {
        $line = (string) ($line ?? '');
        $tech = array();
        
        try {
            switch ($dataid) {
            case "DeviceById":
                $stmt = $this->db->prepare("SELECT keyword,data FROM sip WHERE id = :id");
                $stmt->bindValue(':id', $line, \PDO::PARAM_STR);
                $stmt->execute();
                $tech = $stmt->fetchAll(\PDO::FETCH_COLUMN | \PDO::FETCH_GROUP);
                    
                    // Ensure we have an array and cast values for PHP 8.3
                    if (is_array($tech)) {
                        foreach ($tech as &$value) {
                            $value = is_array($value) && isset($value[0]) ? (string) $value[0] : '';
                        }
                    } else {
                    $tech = array();
                }

                return $tech;
            case "extensionList":
                $stmt = $this->db->prepare("SELECT id as name, data as label  FROM sip WHERE keyword = 'callerid' order by name");
                $stmt->execute();
                $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                return is_array($result) ? $result : array();
            default:
                return array();
        }
        } catch (\PDOException $e) {
            error_log("Database error in getSipTableData: " . $e->getMessage());
            return array();
        }
    }

    /*
     *  Maybe Replace by SccpTables ??!
     *
     */
    public function dump_sccp_tables($data_path, $database, $user, $pass)
    {
        $filename = $data_path.'/sccp_backup_'.date('G_a_m_d_y').'.sql';
        $cmd = 'mysqldump ' . escapeshellarg((string) $database)
             . ' --password=' . escapeshellarg((string) $pass)
             . ' --user=' . escapeshellarg((string) $user)
             . ' --single-transaction >' . escapeshellarg((string) $filename);
        $result = exec($cmd, $output);
        return $filename;
    }

    public function updateTableDefaults($table, $field, $value) {
        $table = (string) $table;
        $field = (string) $field;
        if (!in_array($table, self::$allowedDefaultTables, true)) {
            throw new \InvalidArgumentException('Unsupported table for default update');
        }
        if (!$this->isSafeIdentifier($table) || !$this->isSafeIdentifier($field)) {
            throw new \InvalidArgumentException('Unsafe SQL identifier');
        }
        if (!$this->tableHasColumn($table, $field)) {
            throw new \InvalidArgumentException('Unknown table column');
        }
        $defaultValue = $this->db->quote((string) $value);
        $stmt = $this->db->prepare("ALTER TABLE `{$table}` ALTER COLUMN `{$field}` SET DEFAULT {$defaultValue}");
        $stmt->execute();
    }

/*
 *  Check Table structure
 */
    public function validate()
    {
        $result = 0;
        $check_fields = [
                        '431' => ['private'=> "enum('on','off')"],
                        '433' => ['directed_pickup'=>'']
                        ];
        $stmt = $this->db->prepare('DESCRIBE sccpdevice');
        $stmt->execute();
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $value) {
            $id_result[$value['Field']] = $value['Type'];
        }
        foreach ($check_fields as $key => $value) {
            if (!empty(array_intersect_assoc($value, $id_result))) {
                  $result = $key;
            } else {
                // no match but maybe checking against an empty string so just need to check key does not exist
                foreach ($value as $skey => $svalue) {
                    if (empty($svalue) && (!isset($id_result[$skey]))) {
                        $result = $key;
                    }
                }
            }
        }

        return $result;
    }

    public function getNamedGroup($callGroup) {
        $callGroup = (string) $callGroup;
        if (!$this->isSafeIdentifier($callGroup) || !$this->tableHasColumn('sccpline', $callGroup)) {
            return array();
        }
        $sql = "SELECT {$callGroup} FROM sccpline GROUP BY {$callGroup}";
        $sth = $this->db->prepare($sql);
        $result = array();
        $tech = array();
        try {
            $sth->execute();
            $result = $sth->fetchAll();
            foreach($result as $val) {
               $tech[$callGroup][] = $val[0];
            }
        } catch(\Exception $e) {}
    return $tech;
    }
}
