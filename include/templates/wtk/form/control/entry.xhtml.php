<?php if ($height == 1): ?>
<input<?php
wtk_id_classes ($id, $flags, $dojoType);
wtk_attr('type', 'text');
wtk_attr('name', $name);
wtk_attr('value', $value);
wtk_attr('size', $width);
if ($readonly)
	wtk_attr('disabled', 'disabled');
?>/><?php echo $suffix; ?>
<?php else: ?>
<textarea <?php
wtk_id_classes($id, $flags);
wtk_attr('name', $name);
wtk_attr('rows', $height);
wtk_attr('cols', $width);
if ($readonly)
	wtk_attr('disabled', 'disabled');
?>><?php echo htmlspecialchars($value); ?>
</textarea>
<?php endif; ?>