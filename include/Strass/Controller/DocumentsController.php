<?php

require_once 'Strass/Unites.php';
require_once 'Strass/Documents.php';

class DocumentsController extends Strass_Controller_Action
{
  function indexAction()
  {
    $unite = $this->_helper->Unite();

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
    $unite = $this->_helper->Unite(false);
    $this->view->doc = $d = $this->_helper->Document(false);
    if ($d) {
      $this->metas(array('DC.Title' => 'Éditer'));
      $unite = $d->findUnite();
    }
    else {
      $this->metas(array('DC.Title' => 'Envoyer un document'));
      $this->branche->append();
    }

    $t = new Unites;
    $unites = $t->fetchAll();
    $envoyables = array();
    foreach($unites as $u)
      if ($this->assert(null, $u, 'envoyer-document'))
	$envoyables[$u->id] = wtk_ucfirst($u->getFullName());

    if (!count($envoyables))
      throw new Strass_Controller_Action_Exception_Forbidden
	("Vous n'avez le droit d'envoyer de document pour aucune unité");

    $this->view->model = $m = new Wtk_Form_Model('envoyer');
    $m->addNewSubmission('envoyer', "Envoyer");

    $m->addInstance('Enum', 'unite', "Unité", $unite->id, $envoyables);
    $m->addInstance('String', 'titre', "Titre", $d ? $d->titre : null);
    $i = $m->addInstance('File', 'document', "Document");

    if ($m->validate()) {
      $i = $m->getInstance('document');
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
	$d->suffixe =strtolower(end(explode('.', $m->document['name'])));
	$d->save();

	if ($i->isUploaded())
	  $d->storeFile($i->getTempFilename());

	$du->document = $d->id;
	$du->unite = $m->unite;
	$du->save();

	$this->logger->info($message,
			    $this->_helper->Url('index', null, null,
						array('unite' => $du->findParentUnites()->slug)));

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
      $u = $d->findUnite();
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
