<tr>
<?php foreach ($this->children as $child): ?>
<td>
<?php $this->$child->output (); ?>
</td>
<?php endforeach; ?>
</tr>
