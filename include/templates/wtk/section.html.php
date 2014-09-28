<?php
?><div<?php wtk_id_classes($id, $flags, $dojoType); ?>>
<?php if (isset($this->title)): ?>
<?php $level = $level > 0 ? min($level, 6) : 6; ?>
<?php echo "<h".$level." ";
$flags[1] = "h";
wtk_classes($flags);
echo ">";
$this->title->output();
echo "</h".$level.">"; ?>

<?php endif; ?>
<?php $this->outputChildren(); ?>
</div>
