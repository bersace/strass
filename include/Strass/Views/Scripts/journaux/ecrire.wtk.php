<?php

$f = $this->document->addForm($this->model);

if ($this->model->getInstance('auteur') instanceof Wtk_Form_Model_Instance_Enum)
  $f->addSelect('auteur', true);
else
  $f->addHidden('auteur');

$f->addEntry('titre', 46);

try {
  $f->addSelect('public');
}
catch(Exception $e) {
  $f->addParagraph("Votre article sera en attente de modération.")->addFlags('info');
}

$f->addEntry('boulet', 64, 4);
$s = $f->addSection('editeur');
$e = $f->addEntry('article', 64, 16);
$e->reparent($s);

$aide = <<<EOS
++ Formatage

EOS
  ;

$samples = array('//emphase//',
		 '**emphase forte**',
		 '__souligné__',
		 '[http://url.tld/ lien]',
		 '[[image /data/styles/strass/favicon.png  Image]]',
		 );
foreach($samples as $sample)
  $aide.= "|| {{``".$sample."``}} || ".$sample."||\n";
$aide.= <<<EOS

  [http://pear.reversefold.com/dokuwiki/doku.php?id=text_wiki#rules Plus de formattage].

EOS
  ;

$s->addSection('cheatsheet')->addText($aide);

$f->addTable('images', array('image'  => array('File'),
			     'nom'    => array('Entry', 16),
			     'origin' => array('Hidden')));

$f->addForm_ButtonBox()->addForm_Submit($this->model->getSubmission('poster'));
