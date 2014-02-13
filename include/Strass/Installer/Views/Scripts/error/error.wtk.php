<?php
// simple listage des erreurs.
if (count($this->errors > 1)) {
  $this->document->setTitle("Erreurs");
}

foreach ($this->errors as $i => $error) {
  if ($error instanceof Strass_Controller_Action_Exception_Forbidden)
    $titre = "Accès refusé";
  else if ($error instanceof Strass_Controller_Action_Exception_Notice)
    $titre = "Notice";
  else
    $titre = "Erreur".(count($this->errors)>1 ? " #".($i+1) : "");

  $this->document->setTitle(null);

  $section = $this->document->addDialog($titre)
    ->setId("error".$i)
    ->addFlags('error');

  $section->addText("// ".$error->getMessage()." // \n");

  $d = $section->addSection(null, 'Détails')->addFlags('details');
  $d->addText("à {{".$error->getFile().":".$error->getLine()."}}\n");
  $backtrace = $d->addSection("backtrace".$i, "Backtrace")->addFlags('trace');
  $list = $backtrace->addList()->setOrdered()->setReversed();
  foreach ($error->getTrace() as $step) {
    extract($step);
    $list->addItem()->addText((isset($file) ? $file.":".$line." " : "")." ".
			      (isset($class) ? $class."::" : "").$step['function']);
  }

  if (!($error instanceof Zend_Controller_Action_Exception)) {
    $section->addFlags('showtrace');
  }
}
