<?php

require_once 'Strass/Statique.php';

class StatiquesController extends Strass_Controller_Action
{
	function indexAction()
	{
		$page = $this->_getParam('page');
		if (!$page) {
			throw new Knema_Controller_Action_Exception("Aucune page à afficher");
		}
        
		$page = new Statique($page);

		if (!$page->readable())
		  $this->view->wiki = null;
		else
		  $this->view->wiki = $page->read();
		$this->metas(array('DC.Title' => $page->getTitle()));
		$this->branche->append($page->getTitle());
		$this->actions->append('Éditer',
				       array('action' => 'editer'),
				       array(null, $page));
	}
    
	function editerAction()
	{             
		$page = $this->_getParam('page');
		if (!$page)
			throw new Knema_Controller_Action_Exception("Aucune page à éditer");
        
		$page = new Statique($page);
		$this->metas(array('DC.Title' => "Éditer ".$page->getTitle()));
		$this->branche->append($page->getTitle(),
				       array('action' => 'index'));
		$this->branche->append('Éditer');
        
        
		$this->assert(null, $page, 'editer',
			      "Vous n'avez pas le droit d'éditer cette page");
         
		$m = new Wtk_Form_Model('editer');
		$m->addNewSubmission('enregistrer', 'Enregistrer');
		$m->addString('wiki', 'Texte', $page->read());
        
		if ($m->validate()) {
			$page->write($m->get('wiki'));
			$this->redirectSimple('index');
		}
		$this->view->statique = $page;
		$this->view->model = $m;
	}
}
