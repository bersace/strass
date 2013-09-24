<table<?php wtk_id_classes ($id, $flags, $dojoType); ?>>
<?php if ($show_header): ?>
<thead>
<tr>
   <?php foreach ($columns as $i => $col): ?>
	 <th<?php wtk_classes($cclasses[$i]); ?>><?php echo $col->getTitle(); ?></th>
   <?php endforeach; ?>
</tr>
</thead>
   <?php endif; ?>
<tbody>
<?php for($i = 0; $i < $rows; $i++): ?>
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
</tbody>
</table>
