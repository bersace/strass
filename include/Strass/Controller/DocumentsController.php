<?php

require_once 'Strass/Documents.php';

class DocumentsController extends Strass_Controller_Action
{
	protected $_titreBranche = 'Documents';

	protected function _completeBranche()
	{
		$this->branche->append('Document');
	}

	function indexAction()
	{
		$docsunite = new DocsUnite();
		$this->view->docs = $docsunite->fetchAll(NULL, 'unite');
		$this->metas(array('DC.Title' => 'Documents'));

		if (count($this->unitesEnvoyables())) {
			$this->actions->append("Envoyer un nouveau document",
					       array('action' => 'envoyer'));
			$this->actions->append("Supprimer un document",
					       array('action' => 'supprimer'));
		}
	}

	function unitesEnvoyables()
	{
		$i = Zend_Registry::get('individu');
		if (!$i)
			return array();

		$unites = array();

		if ($this->assert(null)) {
			$tu = new Unites();
			$us = $tu->fetchAll();
		}
		else
			$us = $i->getUnites();

		foreach($us as $u) {
			if ($this->assert(null, $u, 'envoyer-document'))
				$unites[$u->id]=wtk_ucfirst($u->getFullname());
		}

		return $unites;
	}

	function envoyerAction()
	{
		$i = Zend_Registry::get('individu');


		$unites = $this->unitesEnvoyables();
		if (!count($unites))
			throw new Knema_Controller_Exception
				("Vous n'avez le droit d'envoyer de document pour aucune unité");

		$this->metas(array('DC.Title' => 'Envoyer un document'));

		$this->view->model = $m = new Wtk_Form_Model('envoyer');
		$m->addInstance('Enum', 'unite', "Unité concernée", key($unites),
				$unites);
		$m->addInstance('String', 'titre', "Titre");
		$i = $m->addInstance('File', 'document', "Document");
		$m->addNewSubmission('envoyer', "Envoyer");

		if ($m->validate()) {
			$docs = new Documents();
			$db = $docs->getAdapter();
			$db->beginTransaction();

			try {
				$data = $m->get();
				$data['suffixe'] =
					strtolower(end(explode('.', $data['document']['name'])));
				unset($data['document']);
				$data['id'] = wtk_strtoid($data['titre']);
				$data['date'] = strftime('%Y-%m-%d');
				$data['type_mime'] = $i->getMimeType();
				$unite = $data['unite'];
				unset($data['unite']);

				$i = $m->getInstance('document');
				$fichier = 'data/documents/'.$data['id'].'.'.$data['suffixe'];
				mkdir(dirname($fichier), 0775, true);
				if (!move_uploaded_file($i->getTempFilename(), $fichier)) {
					throw new Zend_Controller_Exception
						("Impossible de copier le fichier !");
				}

				$docs->insert($data);

				$du = new DocsUnite();
				$data = array('unite' => $unite, 'document' => $data['id']);
				$du->insert($data);

				$this->_helper->Log("Document envoyé", array("Document" => $data['titre'], $unite),
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

