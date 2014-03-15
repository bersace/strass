<?php

if ($this->citation) {
  $link = $this->lien(array('controller' => 'citation', 'action' => 'index'), 'Citation', true);
  $s = $this->document->footer->current()->addSection('citation', $link);
  $s->addParagraph("« ".$this->citation->texte." »")->addFlags('citation');
  $s->addParagraph($this->citation->auteur)->addFlags('signature');
}
