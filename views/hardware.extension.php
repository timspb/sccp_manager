<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
// vim: set ai ts=4 sw=4 ft=phtml:
$roming_enable = '';
if (!empty($this->sccpvalues['system_rouminguser'])) {
    if ($this->sccpvalues['system_rouminguser']['data'] == 'yes') {
        $roming_enable = 'yes';
    }
}
?>
<div class="fpbx-container container-fluid">
    <div class="row">
        <div class="col-sm-12">
            <div class="display no-border">
                <h1><?php echo _("Extensions (Line)") ?></h1>
                <div id="toolbar-sccp-extension">
                    <a class="btn btn-default" href="config.php?display=extensions&tech_hardware=sccp_custom"><i class="fa fa-plus">&nbsp;</i><?php echo _("Add Extension") ?></a>
                </div>
                <table data-cookie="true" data-cookie-id-table="sccp-extension-table" data-url="ajax.php?module=sccp_manager&command=getExtensionGrid&type=extGrid" data-cache="false" data-show-refresh="true" data-toolbar="#toolbar-sip" data-maintain-selected="true" data-show-columns="true" data-show-toggle="true" data-toggle="table" data-pagination="true" data-search="true" data-row-style="sccpExtensionRowStyle" class="table table-striped ext-list-sccp" id="table-sccp-extension" data-id="name">
                    <thead>
                        <tr>
                            <th data-sortable="true" data-field="name"><?php echo _('Extension') ?></th>
                            <th data-sortable="true" data-field="label"><?php echo _('Display Name') ?></th>
                            <th data-sortable="true" data-field="mac"><?php echo _('Device') ?></th>
                            <th data-sortable="true" data-field="line_status" class="text-center" data-formatter="LineStatusColorFormatter"><?php echo _('Status | Active') ?></th>
                            <th data-field="actions" data-formatter="DispayPhoneActionsKeyFormatter"><?php echo _('Actions') ?></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    function sccpExtensionRowStyle(row, index) {
        var s = (row.line_status === null || row.line_status === undefined) ? '' : String(row.line_status).trim();
        var isOk = (s.indexOf('OK') !== -1 && (s.indexOf('Yes') !== -1 || s.indexOf('yes') !== -1));
        return { classes: isOk ? 'sccp-row-ok' : 'sccp-row-off' };
    }
    function LineStatusColorFormatter(value, row, index) {
        var s = (value === null || value === undefined) ? '' : String(value).trim();
        var isOk = (s.indexOf('OK') !== -1 && (s.indexOf('Yes') !== -1 || s.indexOf('yes') !== -1));
        var cls = isOk ? 'sccp-status-ok' : 'sccp-status-off';
        var icon = '<i class="fa fa-phone ' + cls + '" aria-hidden="true"></i> ';
        return '<span class="' + cls + '">' + icon + (s !== '' ? s : '—') + '</span>';
    }
    function DispayPhoneActionsKeyFormatter(value, row, index) {
        var exp_dev = '';
        var rmn_dev = '<?php echo $roming_enable ?>';
        exp_dev += '<a href="config.php?display=extensions&amp;extdisplay=' + row['name'] + '"><i class="fa fa-pencil"></i></a> &nbsp;';
        exp_dev += '<a class="clickable delete" data-id="' + row['name'] + '"><i class="fa fa-trash"></i></a>';
        if (rmn_dev == 'yes') {
            exp_dev += '<a href="config.php?display=sccp_phone&amp;tech_hardware=r_user&amp;ru_id=' + row['name'] + '"><i class="fa fa-bicycle"></i></a> &nbsp;';
        }
        return  exp_dev;
        return  '<a href="config.php?display=extensions&amp;extdisplay=' + row['name'] + '"><i class="fa fa-pencil"></i></a> &nbsp;<a class="clickable delete" data-id="' + row['name'] + '"><i class="fa fa-trash"></i></a>';
    }
    $(function() {
        function applyExtensionRowColors() {
            var $table = $('#table-sccp-extension');
            if (!$table.length) return;
            var rows = $table.bootstrapTable('getData');
            $table.find('tbody tr').each(function(i) {
                $(this).removeClass('sccp-row-ok sccp-row-off');
                var row = rows[i];
                if (!row) return;
                var s = (row.line_status === null || row.line_status === undefined) ? '' : String(row.line_status).trim();
                var isOk = (s.indexOf('OK') !== -1 && (s.indexOf('Yes') !== -1 || s.indexOf('yes') !== -1));
                $(this).addClass(isOk ? 'sccp-row-ok' : 'sccp-row-off');
            });
        }
        $('#table-sccp-extension').on('load-success.bs.table', applyExtensionRowColors);
        if ($('#table-sccp-extension').find('tbody tr').length) applyExtensionRowColors();
    });
</script>
