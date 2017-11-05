<table<?php wtk_id_classes ($id, $flags, $dojoType, $tooltip); ?>>
<?php if ($show_header): ?>
<thead>
<tr>
   <?php foreach ($columns as $i => $col): ?>
   <th<?php wtk_classes('h', $cclasses[$i]); ?>><?php echo $col->getTitle(); ?></th>
   <?php endforeach; ?>
</tr>
</thead>
   <?php endif; ?>
 <?php $cat_opened = false; ?>
<?php for($i = 0; $i < $rows; $i++): ?>
 <?php $cat_id = 'category-'.$i; ?>
 <?php if (property_exists($this, $cat_id)): ?>
 <?php if ($cat_opened): ?>
</tbody>
 <?php endif; ?>
<tbody<?php wtk_classes($cat_classes[$i]); ?>>
	 <tr class="category h">
   <td colspan="<?php echo count($columns); ?>"<?php wtk_classes($cat_classes[$i]); ?>><?php $this->$cat_id->output(); ?></td>
   </tr>
</tr>
 <?php $cat_opened = true; ?>
 <?php endif; ?>
   <tr<?php wtk_classes (array((($i+1)%2 ? 'odd' : 'even'),
			       $rclasses[$i]));
wtk_djt($rowDojoType);
?>>
    <?php for ($j = 0; $j < $cols; $j++): ?>
       <?php $id = 'cell-'.$i.'-'.$j; ?>
    <td<?php wtk_classes($cclasses[$j]); ?>><?php $this->$id->output(); ?></td>
<?php endfor; ?>
</tr>
<?php endfor; ?>
 <?php if ($cat_opened): ?>
</tbody>
 <?php endif; ?>
</table>
