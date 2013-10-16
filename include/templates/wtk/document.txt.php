<?php
$creation = $metas->creation;
$year = date('Y');

wtk_txt_data($this->content, '', 72);
wtk_txt_data($this->footer, '', 72);
?>
<?php $this->content->output(); ?>

<?php
if ($footer = trim($this->footer->render()))
echo wtk_txt_center(wtk_txt_pad('', 36, '—'), 72)."\n".$footer;


