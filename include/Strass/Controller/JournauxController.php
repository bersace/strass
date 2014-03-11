<?php

require_once 'Strass/Journaux.php';

class JournauxController extends Strass_Controller_Action
{
  function indexAction()
  {
    $this->view->journal = $j = $this->_helper->Journal();
    $this->formats('rss', 'atom');

    $s = $j->selectArticles();
    $s->where('public IS NOT NULL');
    $this->view->model = new Strass_Pages_Model_Rowset($s, 10, $this->_getParam('page'));

    $this->actions->append("Écrire un article",
			   array('action' => 'ecrire',
				 'journal' => $j->slug),
			   array(null, $j));
    $this->actions->append("Éditer",
			   array('action' => 'editer',
				 'journal' => $j->slug),
			   array(null, $j));
    $brouillons = $j->findArticles('public IS NULL');
    if ($brouillons->count()) {
      $this->actions->append("Brouillons",
			     array('action' => 'brouillons',
				   'journal' => $j->slug),
			     array(null, $j));
    }
  }

  function fonderAction()
  {
    $this->view->unite = $u = $this->_helper->Unite();
    $this->metas(array('DC.Title' => "Fonder le journal de ".$u->getFullName(),
		       'DC.Subject' => 'journaux,journal,gazette,blog'));

    $tu = $u->findParentTypesUnite();
    if ($tu->isTerminale() && $tu->age_min < 12)
      throw new Strass_Controller_Action_Exception("Impossible de créer un journal d'unité ".
						   "pour ".$u->getFullName());

    $this->assert(null, $u, 'fonder-journal',
		  "Vous n'avez pas le droit de fonder le journal de cette unité");

    $this->view->model = $m = new Wtk_Form_Model('fonder-journal');
    $i = $m->addString('nom', "Nom");
    $m->addConstraintRequired($i);
    $m->addNewSubmission('fonder', "Fonder");

    if ($m->validate()) {
      $t = new Journaux;
      $db = $t->getAdapter();
      $db->beginTransaction();
      try {
	$data = array('nom' => $m->get('nom'),
		      'slug' => $t->createSlug(wtk_strtoid($m->get('nom'))),
		      'unite' => $u->id);
	$k = $t->insert($data);
	$j = $t->findOne($k);

	$this->logger->info("Nouveau journal d'unité",
			    $this->_helper->Url('index', 'journaux', array('journal' => $j->slug)));
	$db->commit();
      }
      catch(Exception $e) { $db->rollBack(); throw $e; }
      $this->redirectSimple('index', 'journaux', null,  array('journal' => $j->slug));
    }
  }

  function editerAction()
  {
    $this->view->journal = $j = $this->_helper->Journal();
    $this->metas(array('DC.Title' => "Modifier ".wtk_ucfirst($j->nom)));

    $this->assert(null, $j, 'editer',
		  "Vous n'avez pas le droit de modifier ce journal");


    $this->view->model = $m = new Wtk_Form_Model('journal');
    $i = $m->addString('nom', 'Nom', $j->nom);
    $m->addConstraintRequired($i);
    $m->addNewSubmission('enregistrer', 'Enregistrer');

    if ($m->validate()) {
      $t = $j->getTable();
      $db = $t->getAdapter();
      $db->beginTransaction();
      try {
	$j->nom = $m->get('nom');
	$j->slug = $t->createSlug(wtk_strtoid($j->nom), $j->slug);
	$j->save();

	$this->logger->info("Journal édité",
			    $this->_helper->Url('index', null, null, array('journal' => $j->slug)));
	$db->commit();
      }
      catch(Exception $e) { $db->rollBack(); throw $e; }

      $this->redirectSimple('index', null, null, array('journal' => $j->slug));
    }
  }

  function ecrireAction()
  {
    if ($this->_getParam('article')) {
      $a = $this->_helper->Article();
      $j = $a->findParentJournaux();
    }
    else {
      $a = null;
      $j = $this->_helper->Journal();
    }

    $this->metas(array('DC.Title' => "Écrire un article"));
    $this->assert(null, $j, 'ecrire',
		  "Vous n'avez pas le droit d'écrire un nouvel article dans ce journal");

    $publier = $this->assert(null, $j, 'publier');

    $this->view->model = $m = new Wtk_Form_Model('ecrire');
    $i = $m->addString('titre', "Titre", $a ? $a->titre : null);
    $m->addConstraintRequired($i);
    if ($publier)
      $m->addEnum('public', 'Publication', $a ? $a->public : null,
		  array(null => 'Brouillon', 1 => 'Publier'));

    $m->addString('boulet', "Boulet", $a ? $a->boulet : null);
    $i = $m->addString('article', "Article", $a ? $a->article : null);
    $m->addConstraintRequired($i);
    $t = $m->addTable('images', "Images",
                     array('image' => array('File', "Image"),
                           'nom' => array('String', "Renommer en"),
			   'origin' => array('String')),
                     false);
    if ($a) {
      foreach ($a->getImages() as $image) {
	$t->addRow(null, $image, $image);
      }
    }
    $t->addRow();
    $m->addNewSubmission('poster', "Poster");

    if ($m->validate()) {
      $me = Zend_Registry::get('individu');

      $t = new Articles;
      $tc = new Commentaires;
      $db = $t->getAdapter();
      $db->beginTransaction();
      try {
	if ($a) {
	  $a->titre = $m->titre;
	  $a->slug = $t->createSlug(wtk_strtoid($a->titre), $a->slug);
	  $a->boulet = $m->boulet;
	  $a->article = $m->article;
	  $a->public = $m->public;
	  $a->save();
	  $message = "Article édité";
	}
	else {
	  $data = array('auteur' => $me->id);
	  $c = $tc->findOne($tc->insert($data));

	  $data = array('journal' => $j->id,
			'slug' => $t->createSlug(wtk_strtoid($m->get('titre'))),
			'titre' => $m->get('titre'),
			'boulet' => $m->get('boulet'),
			'article' => $m->get('article'),
			'public' => $m->get('public', null),
			'commentaires' => $c->id,
			);
	  $a = $t->findOne($t->insert($data));
	  $message = "Nouvel article";
	}

	$oldImages = $a->getImages();
	$table = $m->getInstance('images');
	foreach($table as $row) {
	  if ($row->origin && $row->origin != $row->nom) {
	    $a->renameImage($row->origin, $row->nom);
	  }
	  else {
	    $if = $row->getChild('image');
	    if ($if->isUploaded()) {
	      $nom = $row->nom;
	      $a->storeImage($if->getTempFilename(), $nom ? $nom : $if->getBasename());
	    }
	  }
	}
	$oldImages = array_unique($oldImages);
	$newImages = $a->getImages();

	/* Nettoyage des images */
	foreach ($oldImages as $image) {
	  if (!in_array($image, $newImages))
	    $a->deleteImage($image);
	}

	$this->logger->info($message, $this->_helper->url('consulter', 'journaux', null,
							  array('article' => $a->slug), true));

	if (!$this->assert(null, $j, 'publier')) {
	  $mail = new Strass_Mail_Article($a);
	  $mail->send();
	}

	$db->commit();
      }
      catch(Exception $e) { $db->rollBack(); throw $e; }

      $this->redirectSimple('consulter', 'journaux', null, array('article' => $a->slug), true);
    }
  }

  function brouillonsAction()
  {
    $this->view->journal = $j = $this->_helper->Journal();
    $this->metas(array('DC.Title' => "Brouillons"));
    $this->branche->append();
    $this->assert(null, $j, 'publier',
		  "Vous n'avez pas le droit de publier des brouillons");
    $s = $j->selectArticles();
    $s->where('public IS NULL');
    $this->view->model = new Strass_Pages_Model_Rowset($s, 30, $this->_getParam('page'));
  }

  function consulterAction()
  {
    $this->view->article = $a = $this->_helper->Article();
    $this->view->auteur = $a->findAuteur();

    $this->actions->append("Éditer",
			   array('action' => 'ecrire'),
			   array(null, $a));
    $this->actions->append("Supprimer",
			   array('action' => 'supprimer'),
			   array(null, $a));
  }

  function supprimerAction()
  {
    $this->view->article = $a = $this->_helper->Article();
    $this->assert(null, $a, 'supprimer',
		 "Vous n'avez pas le droit de supprimer cet article");
    $this->metas(array('DC.Title' => "Supprimer ".$a->titre));

    $j = $a->findParentJournaux();
    $this->view->model = $m = new Wtk_Form_Model('supprimer');
    $m->addBool('confirmer',
		"Je confirme la suppression de l'article ".$a->titre.".",
		false);
    $m->addNewSubmission('continuer', "Continuer");

    if ($m->validate()) {
      if ($m->confirmer) {
	$db = $a->getTable()->getAdapter();
	$db->beginTransaction();
	try {
	  $a->delete();
	  $this->logger->info("Article supprimé",
			      $this->_helper->Url('index', 'journaux', null,
						  array('journal' => $j->slug), true));
	  $db->commit();
	}
	catch(Exception $e) { $db->rollBack(); throw $e; }
	$this->redirectSimple('index', 'journaux', null, array('journal' => $j->slug), true);
      }
      else {
	$this->redirectSimple('consulter', 'journaux');
      }
    }
  }
}
