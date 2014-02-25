<?php

require_once 'Strass/Journaux.php';

/**
 * Cette fonction return une valeur remarquable d'une série. Elle
 * répartie la série en @div intervalles consécutives et stockes les
 * bornes dans un tableau. Elle retourne la position @pos de ce
 * tableau. Si @vals est définie, on prend la valeur dans @vals à la
 * clef calculée.
 *
 * Par défaut, elle calcule simplement a médiane : on divise en deux
 * intervalle et on prend la valeur du milieu (1 est au milieu de
 * (0,1,2)).
 */
function mediane($serie, $div = 2, $pos = 1, $vals = array())
{
  $serie = array_values($serie);
  $c = count($serie);
  $i = $pos * (($c - 1) / $div);
  //   Orror::dump($div.", ".$pos." => ".$i, $serie, $serie[$i], $vals);
  if (round($i) == $i) {
    if ($vals) {
      return $vals[$serie[$i]];
    }
    else {
      return $serie[$i];
    }
  }
  else {
    $r = round($i);
    if (!is_int($serie[$r]) && $vals) {
      return ($vals[$serie[$r - 1]] + $vals[$serie[$r]]) / 2;
    }
    else {
      return ($serie[$r - 1] + $serie[$r]) / 2;
    }
  }
}

class JournauxController extends Strass_Controller_Action
{
  function indexAction()
  {
    $this->metas(array('DC.Title' => "Gazette des unités",
		       'DC.Subject' => 'journaux,journal,gazette'));
    $journaux = new Journaux();
    $journaux = $journaux->fetchAll();
    if ($journaux->count() == 1) {
      $j = $journaux->current();
      $this->redirectSimple('lire', 'journaux', null,
			    array('journal' => $j->slug));
    }
    $this->view->journaux = $journaux;
    $this->branche->append('Journaux');
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
			    $this->_helper->Url('lire', 'journaux', array('journal' => $j->slug)));
	$db->commit();
      }
      catch(Exception $e) { $db->rollBack(); throw $e; }
      $this->redirectSimple('lire', 'journaux', null,  array('journal' => $j->slug));
    }
  }

  /* lire un journal = lister articles */
  function lireAction()
  {
    $this->view->journal = $j = $this->_helper->Journal();
    $this->formats('rss', 'atom');

    $s = $j->selectArticles();
    $this->view->model = new Strass_Pages_Model_Rowset($s, 30, $this->_getParam('page'));

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

  function brouillonsAction()
  {
    $this->view->journal = $j = $this->_helper->Journal();
    $this->assert(null, $j, 'publier',
		  "Vous n'avez pas le droit de publier des brouillons");
    $this->metas(array('DC.Title' => "Brouillons – ".$j->nom,
		       'DC.Subject' => 'journaux,journal,gazette,brouillons'));
    $b = $j->findArticles('public IS NULL');
    $this->view->current = $this->_getParam('page');
    $this->view->brouillons = $b;
    $this->formats('rss', 'atom');
  }

  function ecrireAction()
  {
    // init
    $j = $this->_helper->Journal();
    $this->assert(null, $j, 'ecrire',
		  "Vous n'avez pas le droit d'écrire un nouvel article dans ce journal");

    $this->metas(array('DC.Title' => "Écrire un article – ".$j->nom,
		       'DC.Subject' => 'journaux,journal,gazette'));
    $publier = $this->assert(null, $j, 'publier');
    $this->view->rubrique = $r = $this->_helper->Rubrique(false);

    // métier
    $m = new Wtk_Form_Model('ecrire');
    $rubs = $j->findRubriques();
    $enum = array();
    foreach($rubs as $rub)
      $enum[$rub->id] = $rub->nom;

    $selected = $r ? $r->id : key($enum);
    $m->addEnum('rubrique', "Rubrique", $selected, $enum);
    $i = $m->addString('titre', "Titre");
    $m->addConstraintRequired($i);
    if ($publier)
      $m->addEnum('public', 'Publication', null, array(null => 'Brouillon',
						       1 => 'Publier'));

    $m->addString('boulet', "Boulet");
    $i = $m->addString('article', "Article");
    $m->addConstraintRequired($i);
    $t = $m->addTable('images', "Images",
		      array('image' => array('File', "Image"),
			    'nom' => array('String', "Renommer en")),
		      false);
    $t->addRow();
    $m->addNewSubmission('poster', "Poster");

    if ($m->validate()) {
      $db = Zend_Registry::get('db');
      $db->beginTransaction();
      try {
	$data = $m->get();
	unset($data['images']);
	$data+=array('public' => null);

	$ind = Zend_Registry::get('user');
	$data = array_merge($data,
			    array('id' => wtk_strtoid($m->get('titre')),
				  'journal' => $j->id,
				  'auteur' => $ind->id,
				  'date' => strftime('%Y-%m-%d'),
				  'heure' => strftime('%H:%M')));

	$articles = new Articles();
	$k = $articles->insert($data);
	$a = $articles->find($k['id'], $k['date'],
			     $k['journal'])->current();

	// stocker les images
	$tables = $m->getInstance('images');
	$dossier = $a->getDossier();
	if (!is_readable($dossier))
	  mkdir($dossier, 0755, true);

	foreach($tables as $row) {
	  $if = $row->getChild('image');
	  if ($if->isUploaded()) {
	    $nom = $row->get('nom');
	    $fichier =
	      $dossier.($nom ? $nom : $if->getBasename());
	    if (!move_uploaded_file($if->getTempFilename(), $fichier)) {
	      throw new Strass_Controller_Action_Exception
		("Impossible de récupérer l'image ".$if->getBasename());
	    }
	  }
	}

	// envoi d'un courriel aux admis si besoin.
	if (!$this->assert(null, $j, 'publier')) {
	  $mail = new Strass_Mail("Nouvel article : ".$data['titre']);
	  // envoi à tous les chefs
	  $u = $j->findParentUnites();
	  $apps = $u->findAppartenances();
	  foreach($apps as $app) {
	    $ind = $app->findParentIndividus();
	    if ($ind->adelec)
	      $mail->addBcc($ind->adelec,
			    $ind->getFullName(false, false));
	  }

	  // article
	  $d = $mail->getDocument();
	  $d->addText("L'article suivant a été posté dans ".$j->nom.". ".
		      "Vous êtes conviés à la modérer.");
	  $s = $d->addSection(null, $data['titre']);
	  $s->addText($data['boulet']);
	  $s->addText($data['article']);
	  $l = $d->addList();
	  $l->addItem()->addLink($this->_helper->Url('editer', 'journaux',
						     array('article' => $data['id'])),
				 "Éditer ou publier cet article");
	  $mail->send();
	}

	$this->_helper->Log("Nouvel article", array($j),
			    $this->_helper->Url('consulter', 'journaux', null,
						array('journal' => $j->id,
						      'date' => $data['date'],
						      'article' => $data['id'])),
			    (string)$data['titre']);


	$db->commit();
	$this->redirectSimple('consulter', 'journaux', null,
			      array('journal' => $j->id,
				    'date' => $data['date'],
				    'article' => $data['id']));
      }
      catch(Exception $e) {
	$db->rollBack();
	throw $e;
      }
    }

    // vue
    $this->view->journal = $j;
    $this->view->model = $m;
  }

  function consulterAction()
  {
    $this->view->article = $a = $this->_helper->Article();
    $this->view->auteur = $a->findAuteur();

    $this->metas(array('DC.Title' => $a->titre,
		       'DC.Subject' => 'journaux,journal,gazette'));


    $this->actions->append("Éditer cet article",
			   array('action' => 'editer'),
			   array(Zend_Registry::get('user'), $a));
    $this->actions->append("Supprimer cet article",
			   array('action' => 'supprimer'),
			   array(Zend_Registry::get('user'), $a));
  }

  function supprimerAction()
  {
    $a = $this->_helper->Article();
    $this->assert(null, $a, 'supprimer');
    $this->metas(array('DC.Title' => "Supprimer ".$a->titre,
		       'DC.Subject' => 'journaux,journal,gazette'));

    $m = new Wtk_Form_Model('supprimer');
    $m->addBool('confirmer',
		"Je confirme la suppression de l'article ".$a->titre.".",
		false);
    $m->addNewSubmission('continuer', "Continuer");

    if ($m->validate()) {
      if ($m->confirmer) {
	$db = $a->getTable()->getAdapter();
	$db->beginTransaction();
	try {
	  $label = (string)$a;
	  $auteur = $a->findParentIndividus();
	  $j = $a->findParentJournaux();
	  $a->delete();
	  $this->_helper->Log("Article supprimé",
			      array($j, 'article' => $label, 'auteur' => $auteur),
			      $this->_helper->Url('lire', 'journaux', null,
						  array('journal' => $this->_getParam('journal'))),
			      (string)$j);
	  $db->commit();
	}
	catch(Exception $e) {
	  $db->rollBack();
	  throw $e;
	}
      }
      $this->redirectSimple('lire', 'journaux', null,
			    array('journal' => $this->_getParam('journal')));
    }

    $this->view->article = $a;
    $this->view->model = $m;
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
			    $this->_helper->Url('lire', null, null, array('journal' => $j->slug)));
	$db->commit();
      }
      catch(Exception $e) { $db->rollBack(); throw $e; }

      $this->redirectSimple('lire', null, null, array('journal' => $j->slug));
    }
  }
}
