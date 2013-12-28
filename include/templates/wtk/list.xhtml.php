<?php $tag = $ordered ? 'o' : 'u'; ?>
<?php
echo "<".$tag."l";
wtk_id_classes ($id, $flags);

if ($reversed) echo ' reversed="reversed"';
echo ">\n";
?>
<?php foreach($this as $item): ?>
<?php $item->output(); ?>
<?php endforeach; ?>
<?php echo "</".$tag."l>\n"; ?>
