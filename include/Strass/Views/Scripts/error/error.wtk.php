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

	if (count($this->errors) == 1) {
	  $this->document->setTitle($titre);
	  $titre = null;
	}

	$section = $this->document->addSection("error".$i, $titre);
	$section->addChild (new Wtk_Text ("// ".$error->getMessage()." // \n"));

	if ($error instanceof Strass_Controller_Action_Exception_Forbidden) {
		if (Zend_Registry::get('user')) {
			$config = new Strass_Config_Php('strass');
			$section->addText("Si vous devriez avoir accès au site, ".
					  "[mailto:".$config->site->admin." contactez le webmestre].");
		}
		else {
			$section->addParagraph("Si vous êtes inscrit, identifiez-vous. Sinon, ",
					       $this->lien(array('controller' => 'membres',
								 'action' => 'inscription'),
							   "inscrivez-vous",
							   true),
					       ".");
		}			
	}

	if (!($error instanceof Strass_Controller_Action_Exception)) {
		$section->addText("à {{".$error->getFile().":".$error->getLine()."}}\n");
		$backtrace = $section->addSection("backtrace".$i, "Backtrace");
		$list = $backtrace->addChild(new Wtk_List(true));
		foreach ($error->getTrace() as $step) {
			extract($step);
			$list->addChild(new Wtk_Text((isset($file) ? $file.":".$line." " : "")." ".
						     (isset($class) ? $class."::" : "").$step['function']));
		}
	}
  }

