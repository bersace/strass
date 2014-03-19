<?php
$compact = is_null($compact) ? count($items) > 3 : $compact;
?>
<?php if ($compact): ?>
<select<?php
wtk_id_classes ($id, $flags);
wtk_attr ('name', $name.($multiple ? '[]' : ''));
wtk_attr ('multiple', $multiple ? 'multiple' : NULL);
wtk_attr ('size', $multiple ? min (count ($items), 8) : NULL);
?>>
<?php foreach ($items as $value => $caption): ?>
<option<?php
wtk_attr ('value', $value ? str_replace (' ', '&nbsp;', $value) : 'NULL');
wtk_attr ('selected', (is_array($selected) ? in_array($value, $selected) : $value == $selected) ? 'selected' : NULL);
?>><?php echo $caption; ?></option>
<?php endforeach; ?>
</select>
<?php else: ?>
<?php foreach ($items as $value => $caption): ?>
<?php $for = wtk_strtoid($name.$value); ?>
<label<?php
wtk_attr ('for', $for);
wtk_classes(wtk_strtoid($value));
?>><input<?php
wtk_attr ('id', $for);
wtk_attr ('name', $name.($multiple ? '[]' : ''));
wtk_attr ('value', $value);
wtk_attr ('type', $multiple ? 'checkbox' : 'radio');
wtk_attr ('checked', (is_array($selected) ? in_array($value, $selected) : $value == $selected) ? 'checked' : NULL);
 ?> />
<?php echo $caption; ?></label>
<?php endforeach; ?>
<?php endif; ?>