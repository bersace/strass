<?php

require_once 'Strass/Livredor.php';

class LivredorController extends Strass_Controller_Action
{
  protected	$_titreBranche = "Livre d'or";

  function indexAction()
  {
    $this->metas(array('DC.Title' => "Livre d'or",
		       'DC.Subject' => 'livre,or'));

    $this->view->livredor = $t = new Livredor;
    $s = $t->select()->where('public')->order('date DESC');
    $this->view->model = new Strass_Pages_Model_Rowset($s, 15, $this->_getParam('page'));

    $this->connexes->append('Poster un message',
			    array('action' => 'poster'));

    $s = $t->select()->where('public IS NULL');
    if ($t->countRows($s)) {
      $this->actions->append('Valider des messages',
			     array('action' => 'moderer'),
			     array(null, $t));
    }

    $this->formats('rss', 'atom');
  }

  function posterAction()
  {
    $this->view->model = $m = new Wtk_Form_Model('poster');
    $this->metas(array('DC.Title' => "Écrire dans le livre d'or"));

    $i = $m->addString('auteur', 'Nom ou pseudonyme');
    $m->addConstraintRequired($i);
    $m->addString('adelec', 'Adresse électronique');
    $i = $m->addString('message', 'Message');
    $m->addConstraintRequired($i);
    $m->addNewSubmission('poster', 'Poster');

    if ($m->validate()) {
      $ti = new Livredor;
      $db = $ti->getAdapter();
      $db->beginTransaction();
      try {
	$tuple = $m->get();
	$tuple['date'] = strftime('%Y-%m-%d %H:%m');
	$tuple['public'] = Zend_Registry::get('user')->isMember() ? 1 : NULL;
	$ti->insert($tuple);

	// signaler à l'admin qu'il faut modérer un nouveau message
	// sur le livre d'or. On épargne la modération du livre d'or à
	// tout les admins.
	if (!$tuple['public']) {
	  $mail = new Strass_Mail("Nouveau message sur le livre d'or");
	  $d = $mail->getDocument();
	  $d->level+=2;
	  $d->addParagraph("Cher administrateur,");
	  $d->addParagraph($tuple['auteur']." a posté un message sur le livre d'or, ".
			   "vous êtes invité à le ",
			   new Wtk_Link($this->_helper->Url->full('moderer'), "modérer"),
			   ".");
	  $s = $d->addSection(null, 'Message de '.$tuple['auteur']);
	  $s->addText($tuple['message']);
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
    $this->view->messages = new Wtk_Pages_Model_Table(new Livredor(),
						      'public IS NULL',
						      'date DESC',
						      15, $page);
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
      $db->commit();
    }
    catch(Exception $e) {
      $db->rollBack();
      throw $e;
    }

    $this->redirectSimple('index', 'livredor', null, null, true);
  }

  function supprimerAction()
  {
    $t = new Livredor;
    $this->assert(null, $t, 'moderer',
		  "Vous n'avez pas le droit de modérer les messages du livre d'or.");

    $this->view->message = $message = $this->_helper->Livredor();

    $this->view->model = $m = new Wtk_Form_Model('suppression');
    $m->addBool('confirmer', "Je confirme vouloir supprimer ce message.");
    $m->addNewSubmission('continuer', 'continuer');

    if ($m->validate()) {
      if ($m->get('confirmer')) {
	$db = $t->getAdapter();
	$db->beginTransaction();
	try {
	  $message->delete();
	  $db->commit();
	}
	catch(Exception $e) {
	  $db->rollBack();
	  throw $e;
	}
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

    $this->view->model = $m = new Wtk_Form_Model('livredor');
    $this->metas(array('DC.Title' => "Éditer le livre d'or"));

    $i = $m->addString('auteur', 'Nom ou pseudonyme', $message->auteur);
    $m->addConstraintRequired($i);
    $m->addString('adelec', 'Adresse électronique', $message->adelec);
    $i = $m->addString('message', 'Message', $message->message);
    $m->addConstraintRequired($i);
    $m->addBool('public', 'Publier ce message', (bool) $message->public);
    $m->addNewSubmission('enregistrer', 'Enregistrer');

    if ($m->validate()) {
      $db = $t->getAdapter();
      $db->beginTransaction();
      try {
	$tuple = $m->get();
	$message->setFromArray($tuple);
	$message->save();
	$db->commit();
	$this->redirectSimple('index');
      }
      catch (Exception $e) {
	$db->rollBack();
	throw $e;
      }
    }
  }
}
