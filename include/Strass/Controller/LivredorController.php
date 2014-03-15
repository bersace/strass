<?php

require_once 'Strass/Livredor.php';

class LivredorController extends Strass_Controller_Action
{
  protected $_titreBranche = "Livre d'or";
  public $_afficherMenuUniteRacine = true;

  function indexAction()
  {
    $this->metas(array('DC.Title' => "Livre d'or",
		       'DC.Subject' => 'livre,or,guestbook'));

    /* Pour RSS et ATOM, on passe directement la table */
    $this->view->livredor = $t = new Livredor;
    $s = $t->select()->where('public')->order('date DESC');
    $this->view->page_model = new Strass_Pages_Model_Rowset($s, 10, $this->_getParam('page'));

    $s = $t->select()->where('public = 0');
    if ($t->countRows($s)) {
      $this->actions->append('Valider des messages',
			     array('action' => 'moderer'),
			     array(null, $t));
    }

    $this->view->form_model = $m = new Wtk_Form_Model('poster');

    $individu = Zend_Registry::get('individu');
    $i = $m->addString('auteur', 'Votre nom', $individu->getFullname(false, false));
    $m->addConstraintRequired($i);
    $i = $m->addString('contenu', 'Votre message public');
    $m->addConstraintRequired($i);
    $m->addNewSubmission('poster', 'Poster');

    if ($m->validate()) {
      $db = $t->getAdapter();
      $db->beginTransaction();
      try {
	$tuple = $m->get();
	$tuple['public'] = Zend_Registry::get('user')->isMember() ? 1 : 0;
	$key = $t->insert($tuple);
	$message = $t->findOne($key);

	$this->logger->info("Nouveau message");

	// signaler à l'admin qu'il faut modérer un nouveau message
	// sur le livre d'or.
	if (!$message->public) {
	  $this->_helper->Flash->info("Message en attente de validation");
	  $mail = new Strass_Mail_Livredor($message);
	  $mail->send();
	}

	$db->commit();
	$this->redirectSimple('index');
      }
      catch (Exception $e) {
	$db->rollBack();
	throw $e;
      }
    }
  }

  function modererAction()
  {
    $ti = new Livredor();
    $this->assert(null, $ti, 'moderer',
		  "Vous n'avez pas le droit de modérer les messages du livre d'or.");

    $this->metas(array('DC.Title' => "Modérer le livre d'or"));

    $page = $this->_getParam('page');
    $t = new Livredor;
    $s = $t->select()->where('public = 0')->order('date DESC');
    $this->view->messages = new Strass_Pages_Model_Rowset($s, 10, $page);
  }

  function accepterAction()
  {
    $t = new Livredor;
    $this->assert(null, $t, 'moderer',
		  "Vous n'avez pas le droit de modérer les messages du livre d'or.");

    $this->metas(array('DC.Title' => "Valider un message du livre d'or"));

    $message = $this->_helper->Livredor();

    $db = $t->getAdapter();
    $db->beginTransaction();
    try {
      $message->public = 1;
      $message->save();
      $this->logger->info("Message de {$message->auteur} accepté",
			  array('controller'=>'livredor', 'action'=>'editer', 'message' => $message->id));
      $db->commit();
    }
    catch(Exception $e) {
      $db->rollBack();
      throw $e;
    }

    $this->_helper->Flash->info("Message publié");
    $this->redirectSimple('moderer', 'livredor', null, null, true);
  }

  function supprimerAction()
  {
    $t = new Livredor;
    $this->assert(null, $t, 'moderer',
		  "Vous n'avez pas le droit de modérer les messages du livre d'or.");

    $this->view->message = $message = $this->_helper->Livredor();

    $s = $t->select()->where('public = 0');
    if ($t->countRows($s)) {
      $this->actions->append('Valider des messages',
			     array('action' => 'moderer'),
			     array(null, $t));
    }

    $this->view->model = $m = new Wtk_Form_Model('suppression');
    $m->addBool('confirmer', "Je confirme vouloir supprimer ce message.");
    $m->addNewSubmission('continuer', 'continuer');

    if ($m->validate()) {
      if ($m->get('confirmer')) {
	$db = $t->getAdapter();
	$db->beginTransaction();
	try {
	  $this->logger->warn("Message de {$message->auteur} supprimé",
			      $this->_helper->Url('index', 'livredor', null, null, true));
	  $this->_helper->flash->info("Message supprimé");
	  $message->delete();
	  $db->commit();
	}
	catch(Exception $e) { $db->rollBack(); throw $e; }
	$action = $message->public ? 'index' : 'moderer';
      }

      $action = $this->_getParam('redirect', 'index');
      $this->redirectSimple($action, 'livredor', null, null, true);
    }
  }

  function editerAction() {
    $t = new Livredor;
    $this->assert(null, $t, 'moderer',
		  "Vous n'avez pas le droit d'éditer les messages du livre d'or.");

    $message = $this->_helper->Livredor();

    $s = $t->select()->where('public = 0');
    if ($t->countRows($s)) {
      $this->actions->append('Valider des messages',
			     array('action' => 'moderer'),
			     array(null, $t));
    }

    $this->view->model = $m = new Wtk_Form_Model('editer');
    $this->metas(array('DC.Title' => "Éditer le livre d'or"));

    $i = $m->addString('auteur', 'Auteur', $message->auteur);
    $m->addConstraintRequired($i);
    $i = $m->addString('contenu', 'Message', $message->contenu);
    $m->addConstraintRequired($i);
    $m->addBool('public', 'Publier ce message', (bool) $message->public);
    $m->addNewSubmission('enregistrer', 'Enregistrer');

    if ($m->validate()) {
      $db = $t->getAdapter();
      $db->beginTransaction();
      try {
	$tuple = $m->get();
	$tuple['public'] = intval($m->get('public'));
	$message->setFromArray($tuple);
	$message->save();
	$this->logger->info("Message de {$message->auteur} édité");
	$db->commit();
	$this->redirectSimple('index', 'livredor', null, null, true);
      }
      catch (Exception $e) {
	$db->rollBack();
	throw $e;
      }
    }
  }
}
