<?php

require_once 'Strass/Liens.php';

class LiensController extends Strass_Controller_Action
{
	protected $_titreBranche = 'Liens';
   
	function indexAction()
	{
		$liens = new Liens();
		$this->metas(array('DC.Title' => 'Liens',
				   'DC.Subject' => 'liens,externes'));

		$this->actions->append("Éditer les liens",
				       array('action'	=> 'editer'),
				       array(Zend_Registry::get('individu'),
					     $liens, 'editer'));
		$this->view->liens = $liens->fetchAll();
	}

	function editerAction()
	{
		$liens = new Liens();
		$this->assert(Zend_Registry::get('individu'), $liens, 'editer',
			      "Vous n'avez pas le droit d'éditer de liens");

		$this->metas(array('DC.Title' => 'Éditer les liens',
				   'DC.Subject' => 'liens,externes'));

		$m = new Wtk_Form_Model('liens');
		$i = $m->addInstance('Table', 'liens', "Liens", array('url'		=> array('String', "URL"),
								      'nom'		=> array('String', 'Nom'),
								      'description'	=> array('String', 'Description')));
		$lns = $liens->fetchAll();
		foreach($lns as $lien) {
			$i->addRow($lien->toArray());
		}
		$i->addRow();
		$m->addNewSubmission('ajouter', "Ajouter");

		if ($m->validate()) {
			$db = $liens->getAdapter();
			$db->beginTransaction();
			try {
				$listes = $m->get('liens');
				$db->query('DELETE FROM `liens`;');

				foreach($listes as $data)
					if ($data['url'])
						$liens->insert($data);

				$this->_helper->Log("Liens édités", array(),
						    $this->_helper->Url('index', 'liens'),
						    "Liens");
				$db->commit();
				$this->redirectSimple('index', 'liens');
			}
			catch (Exception $e) {
				$db->rollBack();
				throw $e;
			}
		}

		$this->view->model = $m;
	}
}


