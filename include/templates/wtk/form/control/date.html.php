<?php if (strpos($format, '%Y') !== false): ?>
<input type="date"
<?php
wtk_id_classes($id, $flags);
wtk_attr('name', $name . '[date]');
wtk_attr('value', substr($value, 0, 10));
?> />
<?php endif; ?>
<?php if (strpos($format, '%H') !== false): ?>
<input type="time"
<?php
wtk_id_classes($id, str_replace('date', 'time', join(' ', $flags)));
wtk_attr('name', $name . '[time]');
wtk_attr('value', substr($value, 11, 5));
?> />
<?php endif; ?>
