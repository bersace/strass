<?php

if ($this->citation) {
	$s = $this->addons->addSection('citation', 'Citation');
	$s->addParagraph("« ".$this->citation->citation." »")->addFlags('citation');
	$s->addParagraph($this->citation->auteur)->addFlags('signature');
 }
