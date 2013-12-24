<?php

class CitationController extends Strass_Controller_Action
{
	protected $_titreBranche = 'Citations';

	function indexAction()
	{
		$this->metas(array('DC.Title' => 'Citations'));

		$this->view->citations = $table = new Citation();
		$s = $table->select()->order('date DESC');
		$this->view->model = new Strass_Pages_Model_Rowset($table, $s, 10, $this->_getParam('page'));

		$this->actions->append("Enregistrer un citation",
				       array('action' => 'enregistrer'));

		$this->formats('rss', 'atom');
	}

	function enregistrerAction()
	{
		$tc = new Citation();
		$this->assert(null, $tc, 'enregistrer',
			      "Vous n'avez pas le droit d'enregistrer une nouvelle citation.");

		$this->metas(array('DC.Title' => 'Enregistrer une citation'));

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

	function editerAction()
	{
		$tc = new Citation();
		$this->assert(null, $tc, 'editer',
			      "Vous n'avez pas le droit d'éditer les citations.");

		$citation = $tc->find($this->_getParam('citation'))->current();
		$this->metas(array('DC.Title' => 'Éditer une citation'));

		$this->view->model = $m = new Wtk_Form_Model('citation');
		$m->addConstraintRequired($m->addString('auteur', 'Auteur', $citation->auteur));
		$m->addConstraintRequired($m->addString('texte', 'Citation', $citation->texte));
		$m->addNewSubmission('enregistrer', 'Enregistrer');

		if ($m->validate()) {
			$db = $tc->getAdapter();
			$db->beginTransaction();
			try {
				$data = $m->get();
				$citation->auteur = $data['auteur'];
				$citation->texte = $data['texte'];
				$citation->save();

				$this->_helper->Log("Citation éditée", array(),
						    $this->_helper->Url(null, 'citation'),
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

	function supprimerAction()
	{
		$tc = new Citation();
		$this->assert(null, $tc, 'supprimer',
			      "Vous n'avez pas le droit de supprimer les citations.");

		$this->view->citation = $citation = $tc->find($this->_getParam('citation'))->current();
		$this->metas(array('DC.Title' => 'Supprimer une citation'));

		$this->view->model = $m = new Wtk_Form_Model('citation');
		$m->addBool('confirmer', "Je confirme vouloir supprimer cette citation.");
		$m->addNewSubmission('continuer', 'continuer');

		if ($m->validate()) {
		  if ($m->get('confirmer')) {
		    $db = $tc->getAdapter();
		    $db->beginTransaction();
		    try {
		      $citation->delete();
		      
		      $this->_helper->Log("Citation de ".$citation->auteur."supprimée", array(),
					  $this->_helper->Url(null, 'citation'),
					  'Citations');
		      $db->commit();
		    }
		    catch (Exception $e) {
		      $db->rollback();
		      throw $e;
		    }
		  }

		  $this->redirectSimple('index');
		}
	}
}
