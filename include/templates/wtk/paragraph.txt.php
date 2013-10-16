<?php
wtk_txt_children_data($this, $_indent, $_width);
$content = $this->renderChildren();
echo wtk_txt_wrap($content, $_width, $_indent);
?>

<?php ; ?>