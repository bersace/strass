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
    $this->metas(array('DC.Title' => 'Envoyer un document'));

    $i = Zend_Registry::get('individu');

    $unites = $i->findUnites(null, true);
    $envoyables = array();
    foreach($unites as $u)
      if ($this->assert(null, $u, 'envoyer-document'))
	$envoyables[$u->id] = wtk_ucfirst($u->getFullName());

    if (!count($envoyables))
      throw new Strass_Controller_Exception
	("Vous n'avez le droit d'envoyer de document pour aucune unité");

    $this->view->model = $m = new Wtk_Form_Model('envoyer');
    $m->addNewSubmission('envoyer', "Envoyer");

    $m->addInstance('Enum', 'unite', "Unité concernée", key($envoyables), $envoyables);
    $m->addInstance('String', 'titre', "Titre");
    $i = $m->addInstance('File', 'document', "Document");

    if ($m->validate()) {
      $i = $m->getInstance('document');
      if (!$i->isUploaded())
	throw new Exception("Fichier manquant");

      $t = new Documents;
      $data = $m->get();
      $data['suffixe'] =
	strtolower(end(explode('.', $data['document']['name'])));
      unset($data['document']);
      $data['slug'] = $t->createSlug(wtk_strtoid($data['titre']));
      $data['date'] = strftime('%Y-%m-%d');
      $unite = $data['unite'];
      unset($data['unite']);

      $db = $t->getAdapter();
      $db->beginTransaction();
      try {
	$k = $t->insert($data);
	$d = $t->findOne($k);
	$d->storeFile($i->getTempFilename());

	$tdu = new DocsUnite();
	$data = array('unite' => $unite,
		      'document' => $d->id);
	$k = $tdu->insert($data);
	$du = $tdu->findOne($k);

	$this->logger->info("Document envoyé",
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
    $this->assert(null, null, null,
		  "Vous devez être identifier pour envoyer un document");

    $m = new Wtk_Form_Model('supprimer');
    $m->addNewSubmission('supprimer', 'Supprimer');
    $us = $this->unitesEnvoyables();
    $td = new Documents();
    $tdu = new DocsUnite();
    $ds = $tdu->fetchAll('unite = "'.implode('" OR unite = "',
					     array_keys($us)).'"');
    $enum = array();
    foreach($ds as $d)
      $enum[$d->document] = $d->findParentDocuments()->titre;

    $m->addEnum('documents', 'Documents', null, $enum, true);       // multiple

    if ($m->validate()) {
      $db = Zend_Registry::get('db');
      $db->beginTransaction();
      try {
	$ids = $m->get('documents');
	$docs = $td->find($ids);
	foreach($docs as $doc)
	  $doc->delete();

	$this->_helper->Log("Documents supprimés", array("documents" => $ids),
			    $this->_helper->Url(null, 'documents'),
			    "Documents");

	$db->commit();
	$this->redirectSimple('index');
      }
      catch(Exception $e) {
	$db->rollBack();
	throw $e;
      }
    }

    $this->metas(array('DC.Title' => 'Supprimer un document'));
    $this->view->model = $m;
  }
}
