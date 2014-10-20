<div<?php wtk_id_classes ('control-'.$id, $flags, $dojoType, $tooltip); ?>>
<?php if (isset($this->errors)): ?>
<?php $this->errors->output(); ?>
<?php endif; ?>
<?php $this->control->output (); ?>
</div>
