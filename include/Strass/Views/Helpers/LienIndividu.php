<?php

class Strass_View_Helper_LienIndividu
{
	protected	$view;

	public function setView($view)
	{
		$this->view = $view;
	}

	public function lienIndividu($individu,
				     $label = null,
				     $action = 'voir',
				     $controller = 'individus')
	{
		if (!$individu) {
			return null;
		}

		$acl = Zend_Registry::get('acl');
		if ($acl->isAllowed(Zend_Registry::get('user'),
				    $individu, $action)) {
			$lien =  $this->view->lien(array('action' => $action,
							 'individu' => $individu->id,
							 'controller' => $controller),
						   $label ? $label : wtk_nbsp($individu->getFullName()),
						   true);
			if ($p = $individu->getProgression())
				$lien->addFlags($p->etape);

			return $lien;
		}
		else
			return new Wtk_RawText($label ? $label : $individu->getName());
	}
}