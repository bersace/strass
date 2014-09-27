<?php

require_once 'Strass/Commentaires.php';

class CommentairesController extends Strass_Controller_Action
{
  function editerAction()
  {
    $this->view->photo = $p = $this->_helper->Photo(false);
    $this->metas(array('DC.Title' => 'Éditer un commentaire'));
    $this->view->commentaire = $c = $this->_helper->Commentaire();
    $this->assert(null, $c, 'editer',
		  "Vous n'avez pas le droit d'éditer ce commentaire !");

    $this->view->model = $m = new Wtk_Form_Model('editer');
    $m->addNewSubmission('enregistrer', 'Enregistrer');
    $m->addString('message', "Message", $c->message);

    if ($m->validate()) {
      $c->message = $m->get('message');

      if ($p)
	$url = $this->_helper->Url('voir', 'photos', null, array('message' => null));
      else
	$url;

      $db = $c->getTable()->getAdapter();
      $db->beginTransaction();
      try {
	$c->save();
	$this->logger->info('Commentaire édité', $url);
	$db->commit();
      }
      catch (Exception $e) { $db->rollBack(); throw $e; }

      if ($p) {
	$this->redirectSimple('voir', 'photos', null, array('message' => null));
      }
    }
  }

  function supprimerAction()
  {
    $p = $this->_helper->Photo(false);
    $this->metas(array('DC.Title' => 'Supprimer un commentaire'));
    $this->view->commentaire = $c = $this->_helper->Commentaire();
    $this->assert(null, $c, 'editer',
		  "Vous n'avez pas le droit de supprimer ce commentaire !");

    $this->view->model = $m = new Wtk_Form_Model('supprimer');
    $m->addNewSubmission('continuer', 'Continuer');
    $m->addBool('confirmer', "Je confirmer la suppression", false);

    if ($m->validate()) {
      if ($m->get('confirmer')) {
	if ($p)
	  $url = $this->_helper->Url('voir', 'photos', null, array('message' => null));
	else
	  $url;

	$db = $c->getTable()->getAdapter();
	$db->beginTransaction();
	try {
	  $c->delete();
	  $this->_helper->Flash->info("Commentaire supprimé");
	  $this->logger->info('Commentaire supprimé', $url);
	  $db->commit();
	}
	catch (Exception $e) { $db->rollBack(); throw $e; }
      }

      if ($p) {
	$this->redirectSimple('voir', 'photos', null, array('message' => null));
      }
    }
  }
}