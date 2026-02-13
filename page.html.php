<div class="container-fluid">
    <h1><?php echo isset($this) && method_exists($this, 'escapeHtml') ? $this->escapeHtml($display_info ?? '') : htmlspecialchars($display_info ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></h1>
    <div class="row">
        <div class="col-sm-12">
            <div class="fpbx-container">
                <div class="display no-border">
                    <div class="nav-container">
                        <div class="scroller scroller-left"><i class="glyphicon glyphicon-chevron-left"></i></div>
                        <div class="scroller scroller-right"><i class="glyphicon glyphicon-chevron-right"></i></div>
                        <div class="wrapper">
                            <ul class="nav nav-tabs list" role="tablist">
                                <?php foreach ($display_page as $key => $page) {
                                    $h = (isset($this) && method_exists($this, 'escapeHtml')) ? array($this, 'escapeHtml') : 'htmlspecialchars';
                                    $ek = is_callable($h) ? $h($key) : htmlspecialchars($key, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                                    $en = is_callable($h) ? $h($page['name'] ?? '') : htmlspecialchars($page['name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                                ?>
                                    <li data-name="<?php echo $ek ?>" class="change-tab <?php echo $key == 'general' ? 'active' : ''?>"><a href="#<?php echo $ek ?>" aria-controls="<?php echo $ek ?>" role="tab" data-toggle="tab"><?php echo $en ?></a></li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                    <div class="tab-content display">
                        <?php foreach ($display_page as $key => $page) {
                            $ek = (isset($this) && method_exists($this, 'escapeHtml')) ? $this->escapeHtml($key) : htmlspecialchars($key, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                        ?>
                            <div id="<?php echo $ek ?>" class="tab-pane <?php echo $key == 'general' ? 'active' : ''?>">
                                <?php echo $page['content']?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal alerts-->
<div class="modal" id="hwalert" tabindex="-1" role="dialog" aria-labelledby="lhwalert">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel"><?php echo _("Result"); ?></h4>
      </div>
      <div class="modal-body">
        ...
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _("Close"); ?></button>
      </div>
    </div>
  </div>
</div>
