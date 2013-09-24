<?php
?><div<?php wtk_id_classes($id, $flags, $dojoType); ?>>
<?php if (isset($this->title)): ?>
<?php $level = min ($level, 6); ?>
<?php echo "<h".$level." ";
$flags[1] = "h";
wtk_classes($flags);
echo "><span>";
$this->title->output();
echo "</span></h".$level.">"; ?>

<?php endif; ?>
<?php $this->outputChildren(); ?>
</div>
