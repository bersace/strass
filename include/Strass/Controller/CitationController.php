<?php

class CitationController extends Strass_Controller_Action
{
	protected $_titreBranche = 'Citations';

	function indexAction()
	{
		$this->metas(array('DC.Title' => 'Citations'));

		$this->view->current = $this->_getParam('page');
		$this->view->citations = new Citation();

		$this->actions->append("Enregistrer un citation",
				       array('action' => 'enregistrer'));
		$this->formats('rss', 'atom');
	}

	function enregistrerAction()
	{
		$tc = new Citation();
		$this->assert(null, $tc, 'enregistrer',
			      "Vous n'avez pas le droit d'enregistrer une nouvelle citation.");

		$this->metas(array('DC.Title' => 'Enregistrer un citations'));

		$this->view->model = $m = new Wtk_Form_Model('citation');
		$m->addConstraintRequired($m->addString('auteur', 'Auteur'));
		$m->addConstraintRequired($m->addString('citation', 'Citation'));
		$m->addNewSubmission('enregistrer', 'Enregistrer');

		if ($m->validate()) {
			$db = $tc->getAdapter();
			$db->beginTransaction();
			try {
				$data = $m->get();
				$data['date'] = strftime('%Y-%m-%d %H:%M:%S');
				$tc->insert($data);
				$this->_helper->Log("Citation enregistrée", array(),
						    $this->_helper->Url(null, 'citations'),
						    'Citations');
				$db->commit();
				$this->redirectSimple('index');
			}
			catch (Exception $e) {
				$db->rollback();
				throw $e;
			}
		}
	}
}