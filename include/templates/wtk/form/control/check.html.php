<input type="checkbox" <?php
wtk_id_classes ($id, $flags);
wtk_attr ('name', $name);
wtk_attr ('value', strval ($value));
wtk_attr ('checked', $value ? 'checked' : NULL);
?> />
