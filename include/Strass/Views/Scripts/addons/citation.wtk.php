<?php

if ($this->citation) {
  $link = $this->lien(array('controller' => 'citation', 'action' => 'index'), 'Citation');
  $s = $this->addons->addSection('citation', $link);
  $s->addParagraph("« ".$this->citation->citation." »")->addFlags('citation');
  $s->addParagraph($this->citation->auteur)->addFlags('signature');
}
