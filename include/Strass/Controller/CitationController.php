<?php

class CitationController extends Strass_Controller_Action
{
  protected $_titreBranche = 'Citations';

  function indexAction()
  {
    $this->metas(array('DC.Title' => 'Citations'));

    $this->view->citations = $table = new Citation;
    $s = $table->select()->order('date DESC');
    $this->view->model = new Strass_Pages_Model_Rowset($s, 10, $this->_getParam('page'));

    $this->actions->append("Enregistrer un citation",
			   array('action' => 'enregistrer'));

    $this->formats('rss', 'atom');
  }

  function enregistrerAction()
  {
    $tc = new Citation;
    $this->assert(null, $tc, 'enregistrer',
		  "Vous n'avez pas le droit d'enregistrer une nouvelle citation.");

    $this->metas(array('DC.Title' => 'Enregistrer une citation'));

    $this->view->model = $m = new Wtk_Form_Model('citation');
    $m->addConstraintRequired($m->addString('auteur', 'Auteur'));
    $m->addConstraintRequired($m->addString('texte', 'Citation'));
    $m->addNewSubmission('enregistrer', 'Enregistrer');

    if ($m->validate()) {
      $db = $tc->getAdapter();
      $db->beginTransaction();
      try {
	$data = $m->get();
	$data['date'] = new Zend_Db_Expr('CURRENT_TIMESTAMP');
	$tc->insert($data);
	$this->logger->info("Citation enregistrée", $this->_helper->Url(null, 'citations'));
	$db->commit();
	$this->redirectSimple('index', null, null, null, true);
      }
      catch (Exception $e) {
	$db->rollback();
	throw $e;
      }
    }
  }

  function editerAction()
  {
    $tc = new Citation;
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

	$this->logger->info("Citation éditée", array('controller' => 'citation'));

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
    $tc = new Citation;
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
	  $this->logger->warn("Citation de ".$citation->auteur." supprimée",
			      array('controller' => 'citation'));
	  $citation->delete();
	  $db->commit();
	}
	catch (Exception $e) {
	  $db->rollback();
	  throw $e;
	}
      }

      $this->redirectSimple('index', null, null, null, true);
    }
  }
}
