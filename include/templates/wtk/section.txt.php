<?php if (isset($this->title)): ?>

	  <?php wtk_txt_data($this->title, $_indent, $_width); ?>
<?php $title = $this->title->render(); ?>
<?php
if ($level == 1) {
	echo "\n";
	$title = wtk_txt_center(mb_strtoupper($title), $_width)."\n";
 }
 else
	 $title = $_indent.' '.$title;
?>
<?php echo $title; ?>


<?php endif; ?>
<?php
wtk_txt_children_data($this, $_indent, $_width);
$this->outputChildren();

echo "\n";
