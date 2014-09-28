<div<?php
wtk_id_classes ('control-'.$id, $flags, $dojoType, $tooltip);
if (isset($wtkConstraint))
	foreach($wtkConstraint as $cons)
		wtk_attr("wtkConstraint", $cons);
?>>
<?php if (isset($this->errors)): ?>
<?php $this->errors->output(); ?>
<?php endif; ?>
<?php if (isset ($this->caption)): ?>
<label<?php wtk_attr ('for', $id); ?>><?php $this->caption->output (); ?></label>
<?php endif; ?>
<?php $iflags = array_merge($flags, array('input')); ?>
<span<?php wtk_classes($iflags); ?>>
<?php $this->control->output (); ?>
</span>
</div>
