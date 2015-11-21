<?php if ($height == 1 || $type != 'text'): ?>
<input<?php
wtk_id_classes ($id, $flags, $dojoType);
wtk_attr('type', $type);
wtk_attr('name', $name);
wtk_attr('value', $value);
wtk_attr('size', $width);
wtk_attr('placeholder', $placeholder);
wtk_attr('pattern', $pattern);
if ($readonly)
	wtk_attr('disabled', 'disabled');
?>/><?php echo $suffix; ?>
<?php else: ?>
<textarea <?php
wtk_id_classes($id, $flags);
wtk_attr('name', $name);
wtk_attr('rows', $height);
wtk_attr('cols', $width);
wtk_attr('placeholder', $placeholder);
if ($readonly)
	wtk_attr('disabled', 'disabled');
?>><?php echo htmlspecialchars($value); ?>
</textarea>
<?php endif; ?>
