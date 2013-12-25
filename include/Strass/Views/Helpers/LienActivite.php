<?php

class Strass_View_Helper_LienActivite {
	protected $view;

	public function setView($view)
	{
		$this->view = $view;
	}

	public function lienActivite($activite,
				     $label = null,
				     $action = null,
				     $controller = null,
				     $unite = null,
				     $reset = true) {
		$label = $label ? $label : wtk_ucfirst($activite->getIntitule());
		$uid = null;
		$resource = $activite;

		$controller = $controller ? $controller : 'activites';

		if ($unite) {
			$action = 'rapport';
			$resource = $unite;
			$uid = $unite->id;
		}

		if (!$action)
			$action = 'consulter';


		$acl = Zend_Registry::get('acl');
		$i = Zend_Registry::get('user');
		if ($acl->isAllowed($i, $resource, $action)) {
			return $this->view->lien(array('activite' => $activite->id,
						       'action' => $action, 
						       'controller' => $controller,
						       'unite' => $uid),
						 $label,
						 $reset)->addFlags($controller, $action);
		}
		else {
			return new Wtk_Inline($label);
		}
	}
  }

