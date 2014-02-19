<?php

require_once 'Strass/Commentaires.php';

class CommentairesController extends Strass_Controller_Action
{
  function editerAction()
  {
    $this->metas(array('DC.Title' => 'Éditer un commentaire'));
    $this->view->commentaire = $c = $this->_helper->Commentaire();
    $this->assert(null, $c, 'editer',
		  "Vous n'avez pas le droit d'éditer ce commentaire !");

    $this->view->model = $m = new Wtk_Form_Model('editer');
    $m->addNewSubmission('enregistrer', 'Enregistrer');
    $m->addString('message', "Message", $c->message);

    if ($m->validate()) {
      $c->message = $m->get('message');

      $db = $c->getTable()->getAdapter();
      $db->beginTransaction();
      try {
	$c->save();
	$this->logger->info('Commentaire édité');
	$db->commit();
      }
      catch (Exception $e) { $db->rollBack(); throw $e; }
    }
  }
}