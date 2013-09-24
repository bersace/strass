<?php if (is_array($value)): ?>
<?php foreach($value as $k => $v): ?>
<input <?php
	  wtk_id_classes($id, $flags);
	  wtk_attr('type', 'hidden');
	  wtk_attr('name', $name.'['.(is_int($k) ? '' : $k).']');
	  wtk_attr('value', $v); 
	  ?>/>

<?php endforeach; ?>
<?php else : ?>
<input <?php
	  wtk_id_classes($id, $flags);
	  wtk_attr('type', 'hidden');
	  wtk_attr('name', $name);
	  wtk_attr('value', $value); 
	  ?>/>
<?php endif; ?>
