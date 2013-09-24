<?php

require_once 'Strass/Activites.php';
require_once 'Strass/Photos.php';

class IndexController extends Strass_Controller_Action implements Zend_Acl_Resource_Interface
{
	protected $_titreBranche = 'Accueil';
   
	function init()
	{
		parent::init();
		$acl = Zend_Registry::get('acl');
		$acl->add($this);
		$unites = new Unites();
		$racine = $unites->fetchAll('unites.parent IS NULL')->current();
		$acl->allow($racine->getRoleRoleId('chef'), $this);
	}

	public function getResourceId()
	{
		return (string) Zend_Registry::get('site')->id;
	}

	public function indexAction()
	{
		$config = new Knema_Config_Php('strass/index');
		$this->view->bienvenue = file_get_contents('data/intro.wiki');
		$this->metas(array('DC.Title' => 'Accueil'));

		$articles = new Articles();
		$db = $articles->getAdapter();

		$unites = new Unites();
		// on chope les unités parentes.
		$this->view->unites = $unites->getOuvertes("unites.parent IS NULL");
		// connexes
		$this->connexes->append("Unités fermées",
					array('controller' => 'unites',
					      'action' => 'fermees'));
		$this->connexes->append("Toutes les citations",
					array('controller' => 'citation'));
		$this->connexes->append("Nouveaux",
					array('controller' => 'unites',
					      'action' => 'nouveaux'));
		$this->connexes->append("Anciens",
					array('controller' => 'unites',
					      'action' => 'anciens'));
		// actions
		$this->actions->append("Éditer l'introduction",
				       array('action' => 'editer'),
				       array(null, $this));
		$this->actions->append("Fonder une unité",
				       array('controller' => 'unites',
					     'action' => 'fonder'));
	}

	function editerAction()
	{
		$this->assert(null, $this, 'editer',
			      "Vous n'avez pas le droit d'administrer ce site");

		$m = new Wtk_Form_Model('intro');
		$m->addString('introduction', "Introduction", file_get_contents('data/intro.wiki'));
		$m->addNewSubmission('enregistrer', 'Enregistrer');

		if ($m->validate()) {
			file_put_contents('data/intro.wiki', $m->get('introduction'));
			$this->redirectSimple('index');
		}

		$this->view->model = $m;
	}
}
