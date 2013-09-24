<div<?php
wtk_id_classes ($id, $flags);
?>>
<?php if ($title): ?>
    <h6<?php wtk_classes($flags, 'h'); ?>><?php echo $title; ?></h6>
<?php endif; ?>
<?php $this->content->output (); ?>
</div>
