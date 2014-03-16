<?php

require_once 'Strass/Unites.php';
require_once 'Strass/Documents.php';

class DocumentsController extends Strass_Controller_Action
{
  function indexAction()
  {
    $this->view->unite = $unite = $this->_helper->Unite();

    $this->view->docs = $unite->findDocuments();
    $this->metas(array('DC.Title' => 'Documents'));
    $this->branche->append('Documents',
			   array('controller' => 'documents',
				 'action' => 'index',
				 'unite' => $unite->slug),
			   array(),
			   true);

    $this->actions->append('Envoyer',
			   array('action' => 'envoyer'),
			   array(null, $unite, 'envoyer-document'));
  }


  function envoyerAction()
  {
    $this->view->doc = $d = $this->_helper->Document(false);
    if ($d) {
      $this->metas(array('DC.Title' => 'Éditer'));
      $this->view->unite = $unite = $d->findUnite();
    }
    else {
      $this->view->unite = $unite = $this->_helper->Unite(false);
      $this->branche->append('Documents', array('action' => 'index'));
      $this->metas(array('DC.Title' => 'Envoyer un document',
			 'DC.Title.alternative' => 'Envoyer'));
      $this->branche->append();
    }

    $t = new Unites;
    $unites = $t->fetchAll();
    $envoyables = array();
    foreach($unites as $u)
      if ($this->assert(null, $u, 'envoyer-document'))
	$envoyables[$u->id] = $u->getFullName();

    if (!count($envoyables))
      throw new Strass_Controller_Action_Exception_Forbidden
	("Vous n'avez le droit d'envoyer de document pour aucune unité");

    $this->view->model = $m = new Wtk_Form_Model('envoyer');
    $m->addNewSubmission('envoyer', "Envoyer");

    $m->addEnum('unite', "Unité", $unite->id, $envoyables);
    $i = $m->addString('titre', "Titre", $d ? $d->titre : null);
    $m->addConstraintRequired($i);
    $m->addString('auteur', "Auteur", $d ? $d->auteur : null);
    $m->addDate('date', "Date", $d ? $d->date : strftime('%F %T'));
    $m->addString('description', "Description", $d ? $d->description : null);
    $i = $m->addInstance('File', 'fichier', "Fichier");

    if ($m->validate()) {
      $i = $m->getInstance('fichier');
      if (!$d && !$i->isUploaded())
	throw new Exception("Fichier manquant");

      $t = new Documents;
      $db = $t->getAdapter();
      $db->beginTransaction();
      try {
	if ($d) {
	  $message = "Document modifié";
	  $du = $d->findDocsUnite()->current();
	}
	else if (!$d) {
	  $message = "Document envoyé";
	  $d = new Document;
	  $du = new DocUnite;
	}

	$d->slug = $t->createSlug(wtk_strtoid($m->titre), $d->slug);
	$d->titre = $m->titre;
	$d->auteur = $m->auteur;
	$d->date = $m->date;
	$d->description = $m->description;

	if ($i->isUploaded()) {
	  $d->suffixe =strtolower(end(explode('.', $m->fichier['name'])));
	  $d->save();
	  $d->storeFile($i->getTempFilename());
	}
	else
	  $d->save();

	$du->document = $d->id;
	$du->unite = $m->unite;
	$du->save();

	$this->logger->info($message,
			    $this->_helper->Url('index', null, null,
						array('unite' => $du->findParentUnites()->slug), true));

	$db->commit();
      }
      catch(Exception $e) { $db->rollBack(); throw $e; }

      $this->redirectSimple('index', null, null,
			    array('unite' => $du->findParentUnites()->slug));
    }
  }

  function supprimerAction()
  {
    $this->view->doc = $d = $this->_helper->Document();
    $this->assert(null, $d, 'supprimer',
		  "Vous n'avez pas le droit de supprimer ce document.");

    try {
      $this->view->unite = $u = $d->findUnite();
      $urlArgs = array('index', 'documents', null, array('unite' => $u->slug), true);
    }
    catch (Strass_Db_Table_NotFound $e) {
      $urlArgs = array('index', 'documents', null, null, true);
    }

    $this->view->model = $m = new Wtk_Form_Model('supprimer');
    $m->addBool('confirmer', "Je confirme la suppression de ce document", false);
    $m->addNewSubmission('supprimer', 'Supprimer');
    if ($m->validate()) {
      $db = $d->getTable()->getAdapter();
      $db->beginTransaction();
      try {
	$message = $d->titre. " supprimé";
	$d->delete();

	$this->logger->warn($message, call_user_func_array(array($this->_helper, 'Url'), $urlArgs));
	$this->_helper->Flash->info($message);

	$db->commit();
      }
      catch(Exception $e) { $db->rollBack(); throw $e; }

      call_user_func_array(array($this, 'redirectSimple'), $urlArgs);
    }
  }
}
