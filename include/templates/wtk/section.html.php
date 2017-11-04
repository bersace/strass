<?php
?><div<?php wtk_id_classes($id, $flags, $dojoType); ?>>
<?php if (isset($this->title)): ?>
<?php $level = $level > 0 ? min($level, 6) : 6; ?>
<h<?php echo $level ?> class="h"><?php $this->title->output(); ?></h<?php echo $level ?>>

<?php endif; ?>
<?php $this->outputChildren(); ?>
</div>
