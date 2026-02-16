<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

//$list_data = $this->getDialPlan('dialplan');
//print_r($list_data);
//$dialFelds = array('match','timeout','line','rewrite','tone');
//$dialFelds = array('match','timeout','User','rewrite','tone');
$dialFelds = array('match','timeout','rewrite','tone');
$dev_id = '*new*';
if (!empty($_REQUEST['extdisplay'])) {
    $dev_id = $_REQUEST['extdisplay'];
}
$def_val = array();
if ($dev_id != '*new*') {
    $list_data= $this->getDialPlan($dev_id);
    $data_s= '';
    foreach ($list_data['template'] as $key => $value) {
        foreach ($dialFelds as $fld) {
            if (isset($value[$fld])) {
                $data_s .=(string)$value[$fld];
            }
            $data_s .= '/';
        }
        $data_s = substr($data_s, 0, -1);
        $data_s .= ';';
    }
    $data_s = substr($data_s, 0, -1);
    $def_val['dialtemplate'] =  array("keyword" => 'dialtemplate', "data" => $data_s, "seq" => "99");
}

?>


<form autocomplete="off" name="frm_editdialtemplate" id="frm_editbuttons" class="fpbx-submit" action="" method="post" data-id="dial_template">
    
    <input type="hidden" name="idtemplate" value="<?php echo str_replace('dial', '', $dev_id);?>">
    <input type="hidden" name="Submit" value="Submit">
    <?php
    if ($dev_id == '*new*') {
        echo $this->showGroup('sccp_dp_new_template', 0, 'sccp_dial', $def_val);
    }
    ?>    
    
<?php
//    echo $this->showGroup('sccp_dp_new_template',0,'sccp_dial',$def_val);
    echo $this->showGroup('sccp_dp_template', 0, 'sccp_dial', $def_val);
?>    
</form>
