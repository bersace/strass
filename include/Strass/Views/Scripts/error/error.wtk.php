<?php

$dialog = $this->document->addDialog("Bug !")
  ->setId("errors")->addFlags('error');

$aide = $dialog->addSection('aide');
$details = $dialog->addSection('details', 'Détails');

foreach ($this->errors as $i => $error) {
  if ($error instanceof Strass_Controller_Action_Exception_Forbidden) {
    $dialog->title = $titre = "Accès refusé";
    $dialog->addFlags('forbidden');
    if (Zend_Registry::get('user')->isMember()) {
      $config = Zend_Registry::get('config');
      $aide->addText("Si vous devriez avoir accès au site, ".
		     "[mailto:".$config->system->admin." contactez le webmestre].");
    }
    else {
      $aide->addParagraph("Si vous êtes inscrit, identifiez-vous. Sinon, ",
			     $this->lien(array('controller' => 'membres',
					       'action' => 'inscription'),
					 "inscrivez-vous", true),
			     ".");
    }
  }
  else if ($error instanceof Strass_Controller_Action_Exception_Notice) {
    $dialog->title = $titre = $error->getMessage();
  }
  else if ($i == 0) {
    $dialog->title = "Bug !";
    $titre = null;
    $dialog->addFlags('bug');
    $aide->addText("Désolé pour la gêne occasionée. ".
		   "Le bug est enregistré dans le journal et nous ferons notre possible ".
		   "pour le corriger. //En attendant, essayez de le contourner !//");
    $dialog->addFlags('showtrace');
  }

  $section = $details->addSection(null, $titre)->addFlags('error');
  $section->addText("{{".get_class($error). "}}: // ".$error->getMessage()." // \n");

  $section->addText("à {{".$error->getFile().":".$error->getLine()."}}\n");
  $backtrace = $section->addSection("backtrace".$i, "Backtrace")->addFlags('trace');
  $list = $backtrace->addList()->setOrdered()->setReversed();
  foreach ($error->getTrace() as $step) {
    extract($step);
    $list->addItem()->addText("{{".(isset($file) ? $file.":".$line." " : "")." ".
			      (isset($class) ? $class."::" : "").$step['function']."}}");
  }
}
