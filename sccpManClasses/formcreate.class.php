<?php

namespace FreePBX\modules\Sccp_manager;

class formcreate
{
    use \FreePBX\modules\Sccp_manager\sccpManTraits\helperFunctions;

    /** Escape for HTML attribute/text (XSS prevention). */
    private static function h($s) {
        return htmlspecialchars(self::safeStr($s), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Convert any value to string (avoids "Array to string conversion" for arrays/SimpleXML). */
    private static function safeStr($v) {
        if ($v === null || $v === '') {
            return '';
        }
        if (is_array($v)) {
            return implode(' ', $v);
        }
        if (is_object($v)) {
            if (method_exists($v, '__toString')) {
                return (string)$v;
            }
            return implode('', (array)$v);
        }
        return (string)$v;
    }

    /** @var string */
    public $buttonDefLabel = 'chan-sccp';
    /** @var string */
    public $buttonHelpLabel = 'site';

    public function __construct($parent_class = null) {
        $this->buttonDefLabel = 'chan-sccp';
        $this->buttonHelpLabel = 'site';
    }

    function addElementIE ($child, $fvalues, $sccp_defaults, $npref) {
        $res_input = '';
        $res_name = '';
        if ($npref == 'sccp_hw_') {
            $this->buttonDefLabel = 'site';
            $this->buttonHelpLabel = 'device';
        }
        $usingSysDefaults = true;
        // Normalise SimpleXML to string to avoid "Array to string conversion"
        $child->help = self::safeStr($child->help ?? '');
        $child->meta_help = self::safeStr($child->meta_help ?? '');
        $child->class = self::safeStr($child->class ?? '');
        $child->nameseparator = self::safeStr($child->nameseparator ?? '');
        // if there are multiple inputs, take the first for res_id and shortId
        $shortId = self::safeStr($child->input[0]->name ?? '');
        $res_id = $npref . $shortId;
        if (!empty($metainfo[$shortId] ?? null)) {
            $helpStr = self::safeStr($child->help);
            if (self::safeStr($child->meta_help) === '1' || $helpStr === 'Help!') {
                $h = $metainfo[$shortId] ?? $helpStr;
                $child->help = self::safeStr($h);
            }
        }

        // --- Add Hidden option
        $res_sec_class = $child->class !== '' ? $child->class : '';
        if (trim($child->nameseparator) === '') {
            $child->nameseparator = ' / ';
        }

        ?>
        <div class="element-container">
            <div class="row">
                <div class="form-group <?php echo $res_sec_class; ?>">
                    <div class="col-md-3">
                        <label class="control-label" for="<?php echo $res_id; ?>"><?php echo _($child->label);?></label>
                        <i class="fa fa-question-circle fpbx-help-icon" data-for="<?php echo $res_id; ?>"></i>
                    </div>
        <?php
                    if (!empty($sccp_defaults[$shortId]['systemdefault'] ?? '')) {
                        // There is a system default, so add button to customise or reset
                        //-- Start include of defaults button --
                        echo "<div class=col-md-3>";
                    }

        // Can have multiple inputs for a field which are displayed with a separator
        $i = 0;
        foreach ($child->xpath('input') as $value) {
            $res_n = self::safeStr($value->name ?? '');
            $res_name = $npref . $res_n;
            $fval = $fvalues[$res_n] ?? array();
            $raw = $fval['data'] ?? '';
            $fval_data = self::safeStr($raw);
            $value->value = $fval_data;
            if ($fval_data !== '') {
                if (self::safeStr($sccp_defaults[$res_n]['systemdefault'] ?? '') !== $fval_data) {
                    $usingSysDefaults = false;
                }
            }
            $value->type = self::safeStr($value->type ?? '') ?: 'text';
            $value->class = self::safeStr($value->class ?? '') ?: 'form-control';
            if ($i > 0) {
                echo self::safeStr($child->nameseparator);
            }
            // Output current value
            if ($fval_data === '') {
                echo self::h($res_n) . " has not been set";
            } else {
                echo self::h($fval_data);
            }
            $i++;
        }
        if (!empty($sccp_defaults[$shortId]['systemdefault'] ?? '')) {

        ?>
                    </div>
                    <div class="col-md-4">
                      <span class="radioset">
                        <input type="checkbox"
                            <?php
                            echo " data-for={$res_id} data-type=text id=usedefault_{$res_id} ";
                            if ($usingSysDefaults) {
                                // Setting a site specific value
                                echo "class=sccp-edit :checked ";
                            } else {
                                // reverting to chan-sccp default values
                                echo "class=sccp-restore data-default=" . ($sccp_defaults[$res_n]['systemdefault'] ?? '') . " ";
                            }
                            ?>
                        >
                        <label
                            <?php
                            echo "for=usedefault_{$res_id} >";
                            echo ($usingSysDefaults) ? _("Customise") : sprintf(_("Use %s defaults"), $this->buttonDefLabel);
                            ?>
                        </label>

                      </span>
                    </div>
                </div>
            </div>
            <div class="row" id="edit_<?php echo $res_id; ?>" style="display: none">
                <div class="form-group <?php echo $res_sec_class; ?>">
                    <div class="col-md-3">
                        <i><?php echo sprintf(_("Enter new %s value for %s"), $this->buttonHelpLabel, $shortId); ?></i>
                    </div>

                    <!-- Finish include of defaults button -->
                    <?php
                    // Close the conditional include of the defaults button opened at line ~47
                  }
                    ?>

                    <div class="col-md-9">
                        <?php
                        $i=0;
                        // Can have multiple inputs for a field displayed with a separator
                        foreach ($child->xpath('input') as $value) {
                                $res_n =  (string)$value->name;
                                $res_name = $npref . $res_n;
                            if (empty($res_id)) {
                                $res_id = $res_name;
                            }
                            if (!empty(($fvalues[$res_n] ?? [])['data'] ?? null)) {
                                $value->value = ($fvalues[$res_n] ?? [])['data'] ?? '';
                            }
                            // Default to chan-sccp defaults, not xml defaults if reverting to defaults or empty
                            if ((empty($value->value)) || ($usingSysDefaults)) {
                                $value->value = $sccp_defaults[$res_n]['systemdefault'] ?? '';
                            }
                            if (empty($value->type)) {
                                $value->type = 'text';
                            }
                            if (empty($value->class)) {
                                $value->class = 'form-control';
                            }
                            if ($i > 0) {
                                echo $child->nameseparator;
                            }
                            echo '<input type="' . self::h($value->type) . '" class="' . self::h($value->class) . '" id="' . self::h($res_id) . '" name="' . self::h($res_name) . '" value="' . self::h($value->value) . '"';
                            if (isset($value->options)) {
                                foreach ($value->options ->attributes() as $optkey => $optval) {
                                    echo  ' '.$optkey.'="'.$optval.'"';
                                }
                            }
                            if (!empty($value->min)) {
                                echo  ' min="'.$value->min.'"';
                            }
                            if (!empty($value->max)) {
                                echo  ' max="'.$value->max.'"';
                            }
                            echo  '>';
                            $i ++;
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <span id="<?php echo $res_id;?>-help" class="help-block fpbx-help-block"><?php echo _($child->help);?></span>
                </div>
            </div>
        </div>
        <?php
    }

    function addElementIED($child, $fvalues, $sccp_defaults,$npref, $napref) {
        $child->help = self::safeStr($child->help ?? '');
        $child->meta_help = self::safeStr($child->meta_help ?? '');
        $res_input = '';
        $res_value = '';
        $opt_at = array();
        $res_n = self::safeStr($child->name ?? '');

        if (!empty($metainfo[$res_n] ?? null)) {
            $helpStr = self::safeStr($child->help);
            if (self::safeStr($child->meta_help) === '1' || $helpStr === 'Help!') {
                $h = $metainfo[$res_n] ?? $helpStr;
                $child->help = self::safeStr($h);
            }
        }
    //        $res_value
        $lnhtm = '';
        $res_id = $napref.$child->name;
        //$i = 0;
        $max_row = 255;
        if (!empty($child->max_row)) {
            $max_row = $child->max_row;
        }

        // fvalues are current settings - the encoding depends on where the data is
        // coming from: IED fields in sccpsettings are json, elsewhere they are ; delimited.
        $fval = $fvalues[$res_n] ?? array();
        if (!empty($fval['data'] ?? null)) {
            $res_value = $this->convertCsvToArray($fval['data']);
        }

        if ($res_n == 'srst_ip') {
            $res_value = $this->convertCsvToArray(($sccp_defaults[$res_n] ?? [])['data'] ?? '');
        }
        if (empty($res_value)) {
            $res_value = array((string) $child->default);
        }

        ?>
    <div class="element-container">
            <div class="row">
                <div class="col-md-12">
                    <div class="row">
                        <div class="form-group">
                            <div class="col-md-3">
                                <label class="control-label" for="<?php echo $res_id; ?>"><?php echo _($child->label);?></label>
                                <i class="fa fa-question-circle fpbx-help-icon" data-for="<?php echo $res_id; ?>"></i>
                            </div>

                            <div class="col-md-9">
                            <?php
                            if (!empty($child->cbutton)) {
                                echo '<div class="form-group form-inline">';
                                foreach ($child->xpath('cbutton') as $value) {
                                    $res_n = $res_id.'[0]['.$value['field'].']';
                                    // res_vf sets the state of the checkbox internal. This is always
                                    // the first array element in $res_value if set
                                    $res_vf = false;
                                    if ($value['value']=='NONE' && empty($res_value)) {
                                        $res_vf = true;
                                    }
                                    if ((isset($res_value[0]['internal'])) || (isset($res_value[0]) && $res_value[0] == 'internal')) {
                                        $res_vf = true;
                                        // Remove the value from $res_value so that do not add empty row for internal
                                        array_shift($res_value);
                                        // If now have an empty array, add a new empty element
                                        if (!is_array($res_value) || count($res_value) == 0) {
                                            // although handle also ip, internal is never set for those arrays
                                            $res_value[0] = array('net'=>"", 'mask' =>"");
                                        }
                                    }
                                    $opt_hide ='';
                                    $opt_class="button-checkbox";
                                    if (!empty($value->option_hide)) {
                                        $opt_class .= " sccp_button_hide";
                                        $opt_hide = ' data-vhide="'.$value->option_hide.'" data-btn="checkbox" data-clhide="'.(string)($value->option_hide['class'] ?? '').'" ';
                                    }
                                    if (!empty($child->option_show)) {
                                        if (empty($opt_hide)) {
                                            $opt_hide =' class="sccp_button_hide" ';
                                        }
                                        $opt_hide .= ' data-vshow="'.$child->option_show.'" data-clshow="'.(string)($child->option_show['class'] ?? '').'" ';
                                    }

                                    if (!empty($value->option_disabled)) {
                                        $opt_class .= " sccp_button_disabled";
                                        $opt_hide = ' data-vhide="'.$value->option_disabled.'" data-btn="checkbox" data-clhide="'.(string)($value->option_disabled['class'] ?? '').'" ';
                                    }

                                    if (!empty($value->class)) {
                                        $opt_class .= " ".(string)$value->class;
                                    }

                                    echo '<span class="'.$opt_class.'"'.$opt_hide.'><button type="button" class="btn '.(($res_vf) ? 'active':"").'" data-color="primary">';
                                    echo '<i class="state-icon '. (($res_vf)?'glyphicon glyphicon-check"':'glyphicon glyphicon-uncheck'). '"></i> ';
                                    echo $value.'</button><input type="checkbox" name="'. $res_n.'" class="hidden" '. (($res_vf)?'checked="checked"':'') .'/></span>';
                                }
                                echo '</div>';
                            }
                            $opt_class = "col-sm-7 ".$res_id."-gr";
                            if (!empty($child->class)) {
                                $opt_class .= " ".self::safeStr($child->class);
                            }
                            echo '<div class = "'.$opt_class.'">';
                            $i=1;
                            foreach ($res_value as $addrArr) {
                                ?>
                                <div class = "<?php echo $res_id;?> form-group form-inline" data-nextid=<?php echo $i;?> id= <?php echo $res_id . $i;?>>
                                <?php
                                foreach ($child->xpath('input') as $value) {
                                    $field_id = (string)$value['field'];
                                    $res_n = $res_id.'['.$i.']['.$field_id.']';
                                    
                                    // Initialize array to prevent undefined key errors
                                    if (!isset($opt_at[$field_id])) {
                                        $opt_at[$field_id] = array();
                                    }
                                    
                                    if (!empty($value->class)) {
                                        $opt_at[$field_id]['class']='form-control ' .(string)$value->class;
                                    } else {
                                        $opt_at[$field_id]['class'] = 'form-control';
                                    }

                                    $defValue = (isset($addrArr[$field_id])) ? $addrArr[$field_id] : "";
                                    echo '<input type="text" name="'. self::h($res_n) .'" class="'. self::h($opt_at[$field_id]['class']) .'" value="'. self::h($defValue) .'"';


                                    if (isset($value->options)) {
                                        if (!isset($opt_at[$field_id]['options'])) {
                                            $opt_at[$field_id]['options'] = array();
                                        }
                                        foreach ($value->options ->attributes() as $optkey => $optval) {
                                            $opt_at[$field_id]['options'][$optkey]=(string)$optval;
                                            $opt_at[$field_id]['nameseparator'] = (null !== (string)$value['nameseparator']) ? (string)$value['nameseparator'] : '';
                                            echo  ' '.$optkey.'="'.$optval.'"';
                                        }
                                    }
                                    echo '> '.(string)$value['nameseparator'].' ';
                                }

                                if (!empty($child->add_pluss)) {
                                    if (is_array($res_value) && $i <= count($res_value)) {
                                        echo '<button type="button" class="btn btn-danger btn-lg input-js-remove" id="'.$res_id.$i.'-btn-del" data-id="'.$res_id.$i.'"><i class="fa fa-minus pull-right"></i></button>';
                                    }
                                    // only add plus button to the last row
                                    if (is_array($res_value) && $i == count($res_value)) {
                                        echo '<button type="button" class="btn btn-primary btn-lg input-js-add" id="'.$res_id.$i.'-btn-add" data-id="'.$res_id.'" data-row="'.$i.'" data-for="'.$res_id.'" data-max="'.$max_row.'"data-json="'.bin2hex(json_encode($opt_at)).'"><i class="fa fa-plus pull-right"></i></button>';
                                    }
                                }
                                echo '</div>';
                                $i++;
                            }
                            ?>
                                </div>
                            <?php
                            if (!empty($child->addbutton)) {
                                echo '<div class = "col-sm-5 '.$res_id.'-gr">';
                                echo '<input type="button" id="'.$res_id.'-btn" data-id="'.$res_id.'" data-for="'.$res_id.'" data-max="'.$max_row.'"data-json="'.bin2hex(json_encode($opt_at)).'" class="input-js-add" value="'._($child->addbutton).'" />';
                                echo '</div>';
                            }
                            ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row"><div class="col-md-12">
                <span id="<?php echo $res_id;?>-help" class="help-block fpbx-help-block"><?php echo _($child->help);?></span>
            </div></div>
    </div>
        <?php
    }

    function addElementIS($child, $fvalues, $sccp_defaults,$npref, $disabledButtons) {
        $child->help = self::safeStr($child->help ?? '');
        $child->meta_help = self::safeStr($child->meta_help ?? '');
      if ($npref == 'sccp_hw_') {
          $this->buttonDefLabel = 'site';
          $this->buttonHelpLabel = 'device';
      }
        $res_n = self::safeStr($child->name ?? '');
        $res_id = $npref.$res_n;
        $res_ext = str_replace($npref,'',$res_n);
        $usingSysDefaults = true;
        if (!empty($metainfo[$res_n] ?? null)) {
            $helpStr = self::safeStr($child->help);
            if (self::safeStr($child->meta_help) === '1' || $helpStr === 'Help!') {
                $h = $metainfo[$res_n] ?? $helpStr;
                $child->help = self::safeStr($h);
            }
        }

        // --- Add Hidden option
        $res_sec_class ='';
        if (!empty($child ->class)) {
            $res_sec_class = (string)$child ->class;
        }
        ?>
        <div class="element-container">
            <div class="row">
                <div class="form-group <?php echo $res_sec_class;?>">
                    <div class="col-md-3 radioset">
                        <label class="control-label" for="<?php echo $res_id; ?>"><?php echo _($child->label)?></label>
                        <i class="fa fa-question-circle fpbx-help-icon" data-for="<?php echo $res_id; ?>"></i>
                    </div>

                    <?php
                    $res_v = '';
                    // set res_v according to precedence Default here, value here, supplied value

                    if (!empty($child->default)) {
                        $res_v = (string)$child->default;
                    }
                    if (!empty($child->value)) {
                         $res_v = (string)$child->value;
                    }
                    $fval = $fvalues[$res_n] ?? array();
                    if (!empty($fval['data'] ?? null)) {
                        if (($fval['data'] ?? '') != '') {
                            $res_v = (string)$fval['data'];
                        }
                    }
                    if (($sccp_defaults[$res_n]['systemdefault'] ?? '') != $res_v) {
                        $usingSysDefaults = false;
                    }
                    if (!empty($sccp_defaults[$res_n]['systemdefault'] ?? '')) {
                    // There is a system default, so add button to customise or reset
                    // the closing } is after the code to include the button at line ~438

                    //-- Start include of defaults button --
                    echo "<div class='col-md-3'>";
                    // Output current value
                    echo $res_v;
                    ?>
                    </div>
                    <div class="col-md-4">
                      <span class="radioset">
                        <input type="checkbox"
                            <?php
                            echo " data-for={$res_id} data-type=radio id=usedefault_{$res_id} ";
                            if ($usingSysDefaults) {
                                // Setting a site specific value
                                echo " class=sccp-edit :checked ";
                            } else {
                                // reverting to chan-sccp default values
                                echo " data-default=" . ($sccp_defaults[$res_n]['systemdefault'] ?? '') . " class=sccp-restore ";
                            }
                            ?>
                        >
                        <label
                            <?php
                            echo "for=usedefault_{$res_id} >";
                            echo ($usingSysDefaults) ? _("Customise") : sprintf(_("Use %s defaults"), $this->buttonDefLabel);
                            ?>
                        </label>
                      </span>
                    </div>
                </div>
            </div>
        <!--    <div class="row" id="edit_<?php echo $res_id; ?>" style="display: none"> -->
            <div class="row" id="edit_<?php echo $res_id; ?>" style="display: none">
                <div class="form-group <?php echo $res_id; ?>">
                    <div class="col-md-3">
                        <i><?php echo "Choose new {$this->buttonHelpLabel} value for {$res_n}"; ?></i>
                    </div>
                    <!-- Finish include of defaults button -->
                    <?php
                    // Close the conditional include of the defaults button opened at line ~385
                    }
                    ?>

                    <div class="col-md-9 radioset " data-hide="on">

                      <?php
                        $i = 0;
                        $opt_hide = '';

                        if ($usingSysDefaults) {
                            $res_v = $sccp_defaults[$res_n]['systemdefault'] ?? '';
                        }
                        if (!empty($child->option_hide)) {
                            $opt_hide = ' class="sccp_button_hide" data-vhide="'.$child->option_hide.'" data-clhide="'.$child->option_hide['class'].'" ';
                        }
                        if (!empty($child->option_show)) {
                            if (empty($opt_hide)) {
                                $opt_hide =' class="sccp_button_hide" ';
                            }
                            $opt_hide .= ' data-vshow="'.$child->option_show.'" data-clshow="'.(string)($child->option_show['class'] ?? '').'" ';
                        }
                        foreach ($child->xpath('button') as $value) {
                            $opt_disabled = '';
                            if (in_array($value, $disabledButtons )) {
                                $opt_disabled = 'disabled';
                            }
                            $val_check = strtolower((string)(isset($value['value']) ? $value['value'] : $value));
                            if ($val_check == strtolower($res_v)) {
                                $val_check = "checked";
                            } else {
                                if ($val_check == '' || $val_check == 'none' ) {
                                   if (strtolower($res_v) == 'none' || $res_v == '' )  {
                                      $val_check = "checked";
                                   } else {$val_check = "";}
                                } else {$val_check = "";}
                            }
                            $optVal = (string)(isset($value['value']) ? $value['value'] : $value);
                            echo "<input type=\"radio\" name=\"" . self::h($res_id) . "\" id=\"" . self::h($res_id . '_' . $i) . "\" value=\"" . self::h($optVal) . "\" {$val_check} {$opt_hide} {$opt_disabled}>";
                            echo "<label for=\"" . self::h($res_id . '_' . $i) . "\">" . self::h($value) . "</label>";
                            $i++;
                        }
                        ?>
                        </div>
                    </div>
                </div>
            <div class="row"><div class="col-md-12">
                    <span id="<?php echo $res_id;?>-help" class="help-block fpbx-help-block"><?php echo _($child->help);?></span>
            </div></div>
        </div>

        <?php
    }

    function addElementSL($child, $fvalues, $sccp_defaults,$npref, $installedLangs) {
        $child->help = self::safeStr($child->help ?? '');
        $child->meta_help = self::safeStr($child->meta_help ?? '');
        $res_n = self::safeStr($child->name ?? '');
        $res_id = $npref.$res_n;
        $child->value ='';
        // $select_opt is an associative array for these types.
        if (!empty($metainfo[$res_n] ?? null)) {
            $helpStr = self::safeStr($child->help);
            if (self::safeStr($child->meta_help) === '1' || $helpStr === 'Help!') {
                $h = $metainfo[$res_n] ?? $helpStr;
                $child->help = self::safeStr($h);
            }
        }
        $child->class = self::safeStr($child->class ?? '') ?: 'form-control';
        switch ($child['type']) {
            case 'SLS':
                $syslangs = array();
                if (\FreePBX::Modules()->checkStatus("soundlang")) {
                   $syslangs = \FreePBX::Soundlang()->getLanguages();
                }
                $select_opt= $syslangs;
                break;
            case 'SLTD':
                // Device Language: show all available from XML so user can choose any language (incl. not yet downloaded)
                $select_opt = array('xx' => 'No language packs found');
                $avail = (array)($installedLangs['languages']['available'] ?? []);
                $have = (array)($installedLangs['languages']['have'] ?? []);
                if (!empty($avail) || !empty($have)) {
                    $select_opt = array();
                    foreach (array_merge($avail, array_diff($have, $avail)) as $v) {
                        $select_opt[(string)$v] = (string)$v;
                    }
                }
                break;
            case 'SLTN':
                // Network Country: show all available from XML so user can choose any country
                $select_opt = array('xx' => 'No country packs found');
                $avail = (array)($installedLangs['countries']['available'] ?? []);
                $have = (array)($installedLangs['countries']['have'] ?? []);
                if (!empty($avail) || !empty($have)) {
                    $select_opt = array();
                    foreach (array_merge($avail, array_diff($have, $avail)) as $v) {
                        $select_opt[(string)$v] = (string)$v;
                    }
                }
                break;
            case 'SLZ':
                $timeZoneOffsetList = array('-12' => 'GMT -12', '-11' => 'GMT -11', '-10' => 'GMT -10', '-09' => 'GMT -9',
                                   '-08' => 'GMT -8',  '-07' => 'GMT -7',  '-06' => 'GMT -6', '-05' => 'GMT -5',
                                   '-04' => 'GMT -4',  '-03' => 'GMT -3',  '-02' => 'GMT -2', '-01' => 'GMT -1',
                                   '00'  => 'GMT', '01' => 'GMT +1',  '02'  => 'GMT +2', '03'  => 'GMT +3',
                                   '04'  => 'GMT +4',   '05' => 'GMT +5',  '06'  => 'GMT +6', '07'  => 'GMT +7',
                                   '08'  => 'GMT +8',   '09' => 'GMT +9',  '10'  => 'GMT +10', '11'=> 'GMT +11', '12' => 'GMT +12');
                $select_opt= $timeZoneOffsetList;
                break;
            case 'SLA':
            $select_opt = array();
                $fval = $fvalues[$res_n] ?? array();
                if (!empty($fval['data'] ?? null)) {
                    $res_value = explode(';', $fval['data']);
                }
                if (empty($res_value)) {
                    $res_value = array((string) $child->default);
                }
                foreach ($res_value as $key) {
                    $select_opt[$key]= $key;
                }
                break;
            case 'SLM':
                if (function_exists('music_list')) {
                    $moh_list = music_list();
                }
                if (!is_array($moh_list)) {
                    $moh_list = array('default');
                }
                $select_opt= $moh_list;
                break;
            case 'SLD':
                $day_format = array("D.M.Y", "D.M.YA", "Y.M.D", "YA.M.D", "M-D-Y", "M-D-YA", "D-M-Y", "D-M-YA", "Y-M-D", "YA-M-D", "M/D/Y", "M/D/YA",
                   "D/M/Y", "D/M/YA", "Y/M/D", "YA/M/D", "M/D/Y", "M/D/YA");
                $select_opt= $day_format;
                break;
            case 'SLK':
                $softKeyList = array();
                $softKeyList = \FreePBX::Sccp_manager()->aminterface->sccp_list_keysets();
                $select_opt= $softKeyList;
                break;
            case 'SLP':
                $dialplan_list = array();
                foreach (\FreePBX::Sccp_manager()->getDialPlanList() as $tmpkey) {
                    $tmp_id = $tmpkey['id'] ?? '';
                    $dialplan_list[$tmp_id] = $tmp_id;
                }
                $select_opt= $dialplan_list;
                break;
            case 'SL':
                $select_opt = array();
                break;
        }
        $fval = $fvalues[$res_n] ?? array();
        if (!empty($fval['data'] ?? null)) {
            $child->value = self::safeStr($fval['data'] ?? '');
        }
        if (empty($child->value)) {
            if (!empty($child->default)) {
                $child->value = $child->default;
            }
        }
        ?>
        <div class="element-container">
            <div class="row">
                <div class="form-group">
                    <div class="col-md-3">
                        <label class="control-label" for="<?php echo $res_id; ?>"><?php echo _($child->label);?></label>
                        <i class="fa fa-question-circle fpbx-help-icon" data-for="<?php echo $res_id; ?>"></i>
                    </div>
                    <div class="col-md-9">
                        <div class = "lnet form-group form-inline" data-nextid=1>
                            <?php
                            echo  '<select name="'.$res_id.'" class="'. $child->class . '" id="' . $res_id . '">';
                            foreach ($select_opt as $key => $val) {
                                if (is_array($val)) {
                                    $opt_key = $val['id'] ?? $key;
                                    $opt_val = $val['val'] ?? $key;
                                    // Avoid using array as label (e.g. SLK softkeyset: show keyset name, not full key list)
                                    if (is_array($opt_val)) {
                                        $opt_val = $opt_key;
                                    }
                                } else if (\FreePBX::Sccp_manager()->is_assoc($select_opt)){
                                    $opt_key = $key;
                                    $opt_val = $val;
                                } else {
                                    $opt_key = $val;
                                    $opt_val = $val;
                                }
                                echo '<option value="' . self::h($opt_key) . '"';
                                if ((string)$opt_key === (string)$child->value) {
                                    echo ' selected="selected"';
                                }
                                echo '>' . self::h($opt_val) . '</option>';
                            }
                            ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                <span id="<?php echo $res_id;?>-help" class="help-block fpbx-help-block"><?php echo _($child->help);?></span>
                </div>
            </div>
        </div>
        <?php
    }

    function addElementSLNA($child, $fvalues, $sccp_defaults,$npref, $installedLangs) {
        $child->help = self::safeStr($child->help ?? '');
        $child->meta_help = self::safeStr($child->meta_help ?? '');
        global $amp_conf;
        $res_n = self::safeStr($child->name ?? '');
        $res_id = $npref.$res_n;
        $child->value ='';
        $selectArray = array();
        // $select_opt is an associative array for these types.
        if (!empty($metainfo[$res_n] ?? null)) {
            $helpStr = self::safeStr($child->help);
            if (self::safeStr($child->meta_help) === '1' || $helpStr === 'Help!') {
                $h = $metainfo[$res_n] ?? $helpStr;
                $child->help = self::safeStr($h);
            }
        }

        $child->class = self::safeStr($child->class ?? '') ?: 'form-control';
        switch ($child['type']) {
            case 'SLDA':
                $select_opt = array('xx' => 'No language packs found');
                if (!empty($installedLangs['languages']['have'] ?? [])) {
                    $select_opt = $installedLangs['languages']['have'] ?? [];
                }
                $selectArray = $installedLangs['languages']['available'] ?? [];
                $requestType = 'locale';
                break;

            case 'SLNA':
                $select_opt = array('xx' => 'No country packs found');
                if (!empty($installedLangs['countries']['have'] ?? [])) {
                    $select_opt = $installedLangs['countries']['have'] ?? [];
                }
                $selectArray = $installedLangs['countries']['available'] ?? [];
                $requestType = 'country';
              break;
        }

        $fval = $fvalues[$res_n] ?? array();
        if (!empty($fval['data'] ?? null)) {
            $child->value = self::safeStr($fval['data'] ?? '');
        }
        if (empty($child->value)) {
            if (!empty($child->default)) {
                $child->value = $child->default;
            }
        }

        ?>
        <div class="element-container">
            <div class="row">
                <div class="form-group">
                    <?php
                    include(($amp_conf['AMPWEBROOT'] ?? '') . '/admin/modules/sccp_manager/views/getFileModal.html');
                    ?>

                    <div class="col-md-3">
                        <label class="control-label" for="<?php echo $res_id; ?>"><?php echo _($child->label);?></label>
                        <i class="fa fa-question-circle fpbx-help-icon" data-for="<?php echo $res_id; ?>"></i>
                    </div>
                    <div class="col-md-3">
                        <div class = "lnet form-group form-inline" data-nextid=1>
                            <?php
                            echo  '<select name="'.$res_id.'" class="'. $child->class . '" id="' . $res_id . '">';
                            foreach ($select_opt as $key => $val) {
                                    $opt_key = $key;
                                    $opt_val = $val;
                                echo '<option value="' . self::h($opt_val) . '"';
                                if ($opt_val == $child->value) {
                                    echo ' selected="selected"';
                                }
                                echo '>' . self::h($opt_val) . '</option>';
                            }
                            ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                      <button type="button" class="btn btn-primary btn-lg" id="<?php echo $requestType;?>" data-toggle="modal" data-target=".get_ext_file_<?php echo $requestType;?>"><i class="fa fa-bolt"></i> <?php echo _("Get $requestType from Provisioner");?>
                      </button>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                <span id="<?php echo $res_id;?>-help" class="help-block fpbx-help-block"><?php echo _($child->help);?></span>
                </div>
            </div>
        </div>

        <?php

    }

    function addElementSD($child, $fvalues, $sccp_defaults,$npref) {
        $child->help = self::safeStr($child->help ?? '');
        $child->meta_help = self::safeStr($child->meta_help ?? '');
        /* Input element Select SDM - Model List; SDMS - Sip model List; SDE - Extension List */
        $res_n = self::safeStr($child->name ?? '');
        $res_id = $npref.$res_n;
        $child->class = self::safeStr($child->class ?? '') ?: 'form-control';

        if (!empty($metainfo[$res_n] ?? null)) {
            $helpStr = self::safeStr($child->help);
            if (self::safeStr($child->meta_help) === '1' || $helpStr === 'Help!') {
                $h = $metainfo[$res_n] ?? $helpStr;
                $child->help = self::safeStr($h);
            }
        }

        switch ($child['type']) {
            case 'SDM':
                $model_list = \FreePBX::Sccp_manager()->dbinterface->getDb_model_info('ciscophones', 'model');
                $select_opt= $model_list;
                break;
            case 'SDMS':
                $model_list = \FreePBX::Sccp_manager()->dbinterface->getDb_model_info('sipphones', 'model');
                $select_opt= $model_list;
                break;
            case 'SDML':
                // Sccp extensions
                $assignedExts = \FreePBX::Sccp_manager()->dbinterface->getSccpDeviceTableData('getAssignedExtensions');
                $select_opt = \FreePBX::Sccp_manager()->dbinterface->getSccpDeviceTableData('SccpExtension');
                foreach ($assignedExts as $name => $nameArr ) {
                      $select_opt[$name]['label'] .= " -  in use";
                }
                $child->default = $fvalues['defaultLine'] ?? '';
                break;
            case 'SDMF':
                // Sip extensions
                $select_opt = \FreePBX::Sccp_manager()->dbinterface->getSipTableData('extensionList');
                $child->default = $fvalues['defaultLine'] ?? '';
                break;
            case 'SDE':
                $extension_list = \FreePBX::Sccp_manager()->dbinterface->getDb_model_info('extension', 'model');
                $extension_list[] = array( 'model' => 'NONE', 'vendor' => 'CISCO', 'dns' => '0');
                foreach ($extension_list as &$data) {
                    $d_name = explode(';', $data['model'] ?? '');
                    if (is_array($d_name) && (count($d_name) > 1)) {
                        $data['description'] = count($d_name).'x '.($d_name[0] ?? '');
                    } else {
                        $data['description'] = $data['model'] ?? '';
                    }
                }
                unset($data);
                $select_opt= $extension_list;
                break;
            case 'SDD':
                $device_list = \FreePBX::Sccp_manager()->dbinterface->getSccpDeviceTableData("SccpDevice");
                $device_list[]=array('name' => 'NONE', 'description' => 'No Device');
                $select_opt = $device_list;
                break;
        }
        ?>
        <div class="element-container">
           <div class="row"> <div class="form-group">
                   <div class="col-md-3">
                        <label class="control-label" for="<?php echo $res_id; ?>"><?php echo _($child->label);?></label>
                        <i class="fa fa-question-circle fpbx-help-icon" data-for="<?php echo $res_id; ?>"></i>
                    </div>
                    <div class="col-md-9"><div class = "lnet form-group form-inline" data-nextid=1> <?php
                            echo  '<select name="'.$res_id.'" class="'. $child->class . '" id="' . $res_id . '"';
                    if (isset($child->options)) {
                        foreach ($child->options->attributes() as $optkey => $optval) {
                            echo  ' '.$optkey.'="'.$optval.'"';
                        }
                    }
                            echo  '>';
                            $fld  = (string)$child->select['name'];
                            $flv  = (string)$child->select['name'];
                            $flv2 = (string)$child->select['addlabel'];
                            $flk  = (string)$child->select['dataid'];
                            $flkv = (string)$child->select['dataval'];
                            $key  = (string)$child->default;
                    $fval = $fvalues[$res_n] ?? array();
                    if (!empty($fval['data'] ?? null)) {
                        $child->value = self::safeStr($fval['data'] ?? '');
                        $key = $fval['data'];
                    }
                    foreach ($select_opt as $data) {
                        $optVal = $data[$fld] ?? '';
                        echo '<option value="' . self::h($optVal) . '"';
                        if ($key === (string)$optVal) {
                            echo ' selected="selected"';
                        }
                        if (!empty($flk)) {
                            echo ' data-id="'. self::h($data[$flk] ?? '') .'"';
                        }
                        if (!empty($flkv)) {
                            echo ' data-val="'. self::h($data[$flkv] ?? '') .'"';
                        }
                        echo '>' . self::h($data[$flv] ?? '');
                        if (!empty($flv2)) {
                            echo ' / ' . self::h($data[$flv2] ?? '');
                        }
                        echo '</option>';
                    }

                    ?>
                    </select>
                    </div>
                  </div>
                </div>
            </div>
            <div class="row"><div class="col-md-12">
                <span id="<?php echo $res_id;?>-help" class="help-block fpbx-help-block"><?php echo _($child->help);?></span>
            </div></div>
        </div>
        <?php
    }

    function addElementITED($child, $fvalues, $sccp_defaults, $npref, $napref) {
        $child->help = self::safeStr($child->help ?? '');
        $child->meta_help = self::safeStr($child->meta_help ?? '');
        $child->label = self::safeStr($child->label ?? '');
        $child->class = self::safeStr($child->class ?? '');
        $res_input = '';
        $res_na = self::safeStr($child->name ?? '');

    //        $res_value
        $lnhtm = '';
        $res_id = $napref.$child->name;
        $i = 0;

        $fval_na = $fvalues[$res_na] ?? array();
        if (!empty($fval_na['data'] ?? null)) {
            $res_value = explode(';', $fval_na['data']);
        }
        if (empty($res_value)) {
            $res_value = array((string) $child->default);
        }
        echo "<table class=table table-striped id=dp-table-{$res_id}>";

        foreach ($res_value as $dat_v) {
            echo '<tr data-nextid="'.($i+1).'" class="'.$res_id.'" id="'.$res_id.'-row-'.($i).'"> ';
            if (!empty($child->label)) {
                echo '<td class=""> <div class="input-group">'.$child->label.'</div></td>';
            }

            $res_vf = explode('/', $dat_v);
            $i2 = 0;

            foreach ($child->xpath('element') as $value) {
                $fields_id = (string)strtolower($value['field']);
                $res_n  = $res_id.'['.$i.']['.$fields_id.']';
                $res_ni = $res_id.'_'.$i.'_'.$fields_id;

                // Initialize array to prevent undefined key errors
                if (!isset($opt_at[$fields_id])) {
                    $opt_at[$fields_id] = array();
                }
                if (!isset($opt_at[$fields_id]['options'])) {
                    $opt_at[$fields_id]['options'] = array();
                }

                $opt_at[$fields_id]['display_prefix']=(string)$value['display_prefix'];
                $opt_at[$fields_id]['display_sufix']=(string)$value['display_sufix'];

                if (empty($value->options->class)) {
                    $opt_at[$fields_id]['options']['class']='form-control';
                }
                $opt_at[$fields_id]['type']=(string)$value['type'];
                $res_opt['addon'] ='';
                if (isset($value->options)) {
                    foreach ($value->options ->attributes() as $optkey => $optval) {
                        $opt_at[$fields_id]['options'][$optkey]=(string)$optval;
                        $res_opt['addon'] .=' '.$optkey.'="'.$optval.'"';
                    }
                }

                echo '<td class="">';
                $res_opt['inp_st'] = '<div class="input-group"> <span class="input-group-addon" id="basep_'.$res_n.'">'.$opt_at[$fields_id]['display_prefix'].'</span>';
                $res_opt['inp_end'] = '<span class="input-group-addon" id="bases_'.$res_n.'">'.$opt_at[$fields_id]['display_sufix'].'</span></div>';
                switch ($value['type']) {
                    case 'date':
                        echo $res_opt['inp_st'].'<input type="date" name="'. self::h($res_n) .'" value="'. self::h($res_vf[$i2] ?? '') .'"'.$res_opt['addon']. '>'.$res_opt['inp_end'];
                        break;
                    case 'number':
                        echo $res_opt['inp_st'].'<input type="number" name="'. self::h($res_n) .'" value="'. self::h($res_vf[$i2] ?? '') .'"'.$res_opt['addon']. '>'.$res_opt['inp_end'];
                        break;
                    case 'input':
                        echo $res_opt['inp_st'].'<input type="text" name="'. self::h($res_n) .'" value="'. self::h($res_vf[$i2] ?? '') .'"'.$res_opt['addon']. '>'.$res_opt['inp_end'];
                        break;
                    case 'title':
                        if ($i > 0) {
                            break;
                        }
                    case 'label':
                        $opt_at[$fields_id]['data'] = (string)$value;
                        echo '<label '.$res_opt['addon'].' >'.(string)$value.'</label>';
                        break;
                    case 'select':
                        echo  $res_opt['inp_st'].'<select name="'.$res_n.'" id="' . $res_n . '"'. $res_opt['addon'].'>';
                        $opt_at[$fields_id]['data']='';
                        foreach ($value->xpath('data') as $optselect) {
                            $opt_at[$fields_id]['data'].= (string)$optselect.';';
                            echo '<option value="' . self::h($optselect) . '"';
                            if (strtolower((string)$optselect) == strtolower((string)($res_vf[$i2] ?? ''))) {
                                echo ' selected="selected"';
                            }
                            echo '>' . self::h($optselect) . '</option>';
                        }
                        echo  '</select>'.$res_opt['inp_end'];
                        break;
                }
                echo '</td>';
                $i2 ++;
            }
            echo '<td><input type="button" id="'.$res_id.'-btn" data-id="'.($i).'" data-for="'.$res_id.'" data-json="'.bin2hex(json_encode($opt_at)).'" class="table-js-add" value="+" />';
            if ($i > 0) {
                echo '<input type="button" id="'.$res_id.'-btndel" data-id="'.($i).'" data-for="'.$res_id.'" class="table-js-del" value="-" />';
            }

            echo '</td></tr>';
            $i++;
        }
        echo '</table>';
    }

    function addElementHLP($child, $fvalues, $sccp_defaults,$npref) {
        $child->label = self::safeStr($child->label ?? '');
        $child->class = self::safeStr($child->class ?? '') ?: 'form-control';
        $res_n = self::safeStr($child->name ?? '');
        $res_id = $npref.$res_n;
        ?>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-info-circle"></i> <nbsp> <?php echo _($child->label);?>
                <a data-toggle="collapse" href="<?php echo '#'.$res_id;?>"><i class="fa fa-plus pull-right"></i></a></h3>
            </div>
            <div class="panel-body collapse" id="<?php echo $res_id;?>">
        <?php
        foreach ($child->xpath('element') as $value) {
            switch ($value['type']) {
                case 'p':
                case 'h1':
                case 'h2':
                case 'h3':
                case 'h4':
                    echo '<'.$value['type'].'>'._((string)$value).'</'.$value['type'].'>';
                    break;
                case 'table':
                    echo '<'.$value['type'].' class="table" >';
                    foreach ($value->xpath('row') as $trow) {
                        echo '<tr>';
                        foreach ($trow->xpath('col') as $tcol) {
                            echo '<td>'._((string)$tcol).'</td>';
                        }
                        echo '</tr>';
                    }
                    echo '</'.$value['type'].'>';
                    break;
            }
        }
        ?>
            </div>
        </div>
        <?php
    }

    function addElementSLTZN($child, $fvalues, $sccp_defaults,$npref) {
        $child->help = self::safeStr($child->help ?? '');
        $child->meta_help = self::safeStr($child->meta_help ?? '');
        $res_n = self::safeStr($child->name ?? '');
        $res_id = $npref.$res_n;
        $child->value ='';

        if (!empty($metainfo[$res_n] ?? null)) {
            $helpStr = self::safeStr($child->help);
            if (self::safeStr($child->meta_help) === '1' || $helpStr === 'Help!') {
                $h = $metainfo[$res_n] ?? $helpStr;
                $child->help = self::safeStr($h);
            }
        }

        $child->class = self::safeStr($child->class ?? '') ?: 'form-control';

        $fval = $fvalues[$res_n] ?? array();
        if (!empty($fval['data'] ?? null)) {
            $child->value = self::safeStr($fval['data'] ?? '');
        }

        $child->value = \date_default_timezone_get();
        ?>
        <div class="element-container">
           <div class="row">
              <div class="form-group">
                  <div class="col-md-3">
                        <label class="control-label" for="<?php echo $res_id; ?>"><?php echo _($child->label);?></label>
                        <i class="fa fa-question-circle fpbx-help-icon" data-for="<?php echo $res_id; ?>"></i>
                  </div>
                  <div class="col-md-9"> <?php
                      echo  $child->value;
                  ?>
                  </div>
                </div>
            </div>
            <div class="row"><div class="col-md-12">
                <span id="<?php echo $res_id;?>-help" class="help-block fpbx-help-block"><?php echo _($child->help);?></span>
            </div></div>
        </div>
        <?php
    }
}

?>
