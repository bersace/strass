<?php

class Strass_View_Helper_LienUnite {
	protected $view;

	public function setView($view)
	{
		$this->view = $view;
	}

	public function lienUnite($unite, $label = null,
				  $urlOptions = array(), $reset = true) {
		$urlOptions = array_merge(array('controller' => 'unites',
						'action' => 'accueil',
						'unite' => $unite->id),
					  (array) $urlOptions);
		$label = $label ? $label : wtk_ucfirst($unite->getName());
		$acl = Zend_Registry::get('acl');
		if ($acl->isAllowed(Zend_Registry::get('individu'),
				    $unite, $urlOptions['action']))
			return $this->view->lien($urlOptions, $label, $reset)->addFlags('unite');
		else
			return new Wtk_RawText($label);
	}
  }

