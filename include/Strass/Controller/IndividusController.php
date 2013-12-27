<?php

require_once 'Image/Transform.php';
require_once 'Strass/Individus.php';
require_once 'Strass/Unites.php';
require_once 'Strass/Progression.php';
require_once 'Strass/Formation.php';

class IndividusController extends Strass_Controller_Action
{
  function indexAction()
  {
    $this->redirectSimple('index', 'unites');
  }

  function ficheAction()
  {
    $individu = $this->_helper->Individu->param();

    $this->metas(array('DC.Title' => $individu->getFullname(false, false)));

    $this->assert(null, $individu, 'fiche',
		  "Vous n'avez pas le droit de voir la fiche de ".$individu->getName().". ");

    $this->formats('vcf', 'csv');

    $this->view->chef = $this->assert(null, $individu, 'progression');
    $this->view->individu = $individu;
    $select = $individu->getTable()->select()->where('fin IS NULL');
    $this->view->appactives = $individu->findAppartenances($select);
    $select = $individu->getTable()->select()->where('fin IS NOT NULL')->order('debut DESC');
    $this->view->historique = $individu->findAppartenances($select);
    $s = $individu->getTable()->select()->order('date DESC');
    $this->view->progression = $individu->findProgression(clone $s);
    $this->view->formation = $individu->findFormation(clone $s);
    $s->order(array('date DESC', 'heure DESC'))->limit(5);
    $this->view->commentaires = $individu->findCommentaires(clone $s);
    $this->view->articles = $individu->findArticles(clone $s);
    $this->view->user = $user = $individu->findUser();

    $this->actions->append("Éditer la fiche",
			   array('action'	=> 'editer'),
			   array(null, $individu));
    $this->actions->append("Éditer l'inscription",
			   array('controller'	=> 'inscription',
				 'action'		=> 'editer'),
			   array(null, $individu, 'inscrire'));
    $this->actions->append("Compléter la progression",
			   array('action'	=> 'progression'),
			   array(null, $individu, 'progresser'));
    $this->actions->append("Compléter la formation",
			   array('action'	=> 'formation'),
			   array(null, $individu, 'former'));
    $this->actions->append("Compléter l'historique",
			   array('controller'	=> 'inscription',
				 'action'		=> 'historique'),
			   array(null, $individu, 'inscrire'));
    $this->actions->append("Désinscrire",
			   array('controller'	=> 'inscription',
				 'action'		=> 'desinscrire'),
			   array(null, $individu, 'inscrire'));
    $this->actions->append("Administrer",
			   array('controller'	=> 'inscription',
				 'action'		=> 'administrer'),
			   array(null, null, 'admin'));

    $moi = Zend_Registry::get('individu');
    if ($moi->id != $individu->id) {
      $this->actions->append("Prendre l'identité",
			     array('controller'	=> 'membres',
				   'action' => 'sudo',
				   'username' => $user->username),
			     array(null, null, 'admin'));
    }

    if ($individu->isMember()) {
      $this->actions->append("Paramètres utilisateur",
			     array('controller'	=> 'membres',
				   'action' => 'parametres',
				   'membre' => $user->username,
				   'individu' => null),
			     array(null, null, 'admin'));
    }
  }

  function editerAction()
  {
    $this->view->individu = $individu = $this->_helper->Individu();
    $this->assert(null, $individu, 'editer',
		  "Vous n'avez pas le droit d'éditer la fiche de cet individu.");

    $this->metas(array('DC.Title' => 'Éditer '.$individu->getFullname()));

    $this->view->model = $m = new Wtk_Form_Model('editer');

    if ($this->assert(null, $individu, 'editer-id')) {
      $m->addConstraintRequired($m->addString('prenom', 'Prénom', $individu->prenom));
      $m->addConstraintRequired($m->addString('nom', 'Nom', $individu->nom));
      $m->addDate('naissance', 'Date de naissance', $individu->naissance);
    }

    $m->addFile('image', 'Photo');

    $sachem = $this->assert(null, $individu, 'totem');
    if ($sachem)
      $m->addString('totem', 'Totem', $individu->totem);

    $m->addString('notes', "Notes", $individu->notes);
    // devrait suffire chez les FSE, et les SUF ?
    $m->addInteger('numero', "Numéro adhérent", $individu->numero, 1, 999999);

    // contacts;
    if (!$individu->isMember())
      $m->addString('adelec', "Adélec", $individu->adelec);
    $m->addString('portable', "Téléphone portable", $individu->portable);
    $m->addString('fixe', "Téléphone fixe", $individu->fixe);
    $m->addString('adresse', "Adresse", $individu->adresse);

    $m->addNewSubmission('valider', 'Valider');

    if ($m->validate()) {
      $db = $individu->getTable()->getAdapter();
      $db->beginTransaction();
      try {
	// contacts
	$champs = array('nom', 'prenom', 'naissance',
			'adelec', 'portable',
			'fixe', 'adresse', 'notes');

	if ($sachem)
	  $champs[] = 'totem';

	if ($this->assert(null, $individu, 'progression')) {
	  $champs[] = 'numero';
	}

	foreach($champs as $champ)
	  if ($m->getInstance($champ))
	    $individu->$champ = trim($m->get($champ));

	$individu->fixe = $this->_helper->Telephone($individu->fixe);
	$individu->portable = $this->_helper->Telephone($individu->portable);
	$individu->slug = wtk_strtoid($individu->getFullname(false, false));
	$individu->save();

	$image = $m->getInstance('image');
	if ($image->isUploaded()) {
	  $tmp = $image->getTempFilename();
	  if ($fichier = $individu->getImage())
	    unlink($fichier);

	  $fichier = $individu->getImage(null, false);
	  $dossier = dirname($fichier);
	  if (!file_exists($dossier))
	    mkdir($dossier, 0755, true);
	  // largeur max de 128px;
	  $mw = 128;
	  $tr = Image_Transform::factory('GD');
	  $tr->load($tmp);
	  list($w, $h) = $tr->getImageSize();
	  $ratio = $w > $mw ? $w/$mw : null;
	  if ($ratio || $image->getMimeType() != 'image/png') {
	    if ($ratio) {
	      $w /= $ratio;
	      $h /= $ratio;
	      $tr->resize(intval($w), intval($h));
	    }
	    $tr->save($fichier, 'png');
	  }
	  else {
	    copy($tmp, $fichier);
	  }
	  $tr->free();
	}

	$this->_helper->Log("Fiche individu mis-à-jour", array($individu),
			    $this->_helper->Url('fiche', 'individus', null, array('individu' => $individu->slug)),
			    (string) $individu);

	$db->commit();
	$this->redirectSimple('fiche', 'individus', null,
			      array('individu' => $individu->slug));

      }
      catch (Exception $e) {
	$db->rollBack();
	throw $e;
      }
    }

    $this->actions->append("Administrer",
			   array('controller'	=> 'inscription',
				 'action'	=> 'administrer'),
			   array(null, $individu, 'admin'));
  }

  // éditer la progression d'un individu
  function progressionAction()
  {
    $this->view->individu = $i = $this->_helper->Individu();
    $this->assert(null, $i, 'progresser',
		  "Vous n'avez pas le droit d'éditer la progression de cet individu");
    $this->metas(array('DC.Title' => 'Éditer la progression de '.$i->getFullname()));

    $this->view->model = $m = new Wtk_Form_Model('progression');
    $te = new Etape();
    $db = $te->getAdapter();
    $exists = $db->select()
      ->distinct()
      ->from('progression')
      ->where("individu = ?", $i->slug)
      ->where("etape = depend");
    $notexists = $db->select()
      ->distinct()
      ->from('progression')
      ->where("individu = ?", $i->slug)
      ->where('etape = id')
      ->where('progression.sexe = etapes.sexe');
    $select = $db->select()
      ->where("sexe = ? OR sexe = 'm'", $i->sexe)
      // on ne teste pas l'age pour permettre de
      // compléter l'historique
      ->where("depend IS NULL".
	      ' OR '.
	      "EXISTS (?)",
	      new Zend_Db_Expr($exists->__toString()))
      ->where("NOT EXISTS (?)", new Zend_Db_Expr($notexists->__toString()));
    $etapes = $te->fetchSelect($select);
    $enum = array();
    $sexes = array();
    foreach($etapes as $etape) {
      $enum[$etape->id] = $etape->titre;
      $sexes[$etape->id] = $etape->sexe;
    }
    end($enum);
    $m->addEnum('etape', 'Étape de progression', key($enum),$enum);
    $m->addDate('date', 'Date', strftime("%Y-%m-%d %H:%M:%S"), '%Y-%m-%d');
    $m->addString('lieu', 'Lieu');
    $m->addNewSubmission('enregistrer', 'Enregistrer');


    if ($m->validate()) {
      $db->beginTransaction();
      try {
	$data = $m->get();
	$data['individu'] = $i->slug;
	$data['sexe'] = $sexes[$data['etape']];
	$tp = new Progression();
	$tp->insert($data);

	$this->_helper->Log("Progression enregistrée", array($i),
			    $this->_helper->Url('fiche', 'individus', null, array($i->id)),
			    (string) $i);

	$db->commit();
	$this->redirectSimple('fiche');
      }
      catch (Exception $e) {
	$db->rollback();
	throw $e;
      }
    }
  }

  // compléter la formation d'un individu
  function formationAction()
  {
    $this->view->individu = $i = $this->_helper->Individu();
    $this->assert(null, $i, 'former',
		  "Vous n'avez pas le droit d'éditer la formation de cet individu");
    $this->metas(array('DC.Title' => 'Éditer la formation de '.$i->getFullname()));
    $this->view->model = $m = new Wtk_Form_Model('formation');

    $td = new Diplomes();
    $db = $td->getAdapter();
    $notexists = $db->select()
      ->distinct()
      ->from('formation')
      ->where("individu = ?", $i->slug)
      ->where('diplome = id');
    $select = $db->select()
      ->where("NOT EXISTS (?)", new Zend_Db_Expr($notexists->__toString()))
      ->where("sexe = 'm' OR sexe = ?", $i->sexe)
      ->where("? >= age_min", $i->getAge());
    $etapes = $td->fetchSelect($select);

    $enum = array();
    foreach($etapes as $etape)
      $enum[$etape->id."#".$etape->branche] = $etape->accr." ".$etape->getBranche();
    $m->addEnum('diplome', 'Diplôme', key($enum),$enum);
    $m->addDate('date', 'Date', strftime("%Y-%m-%d %H:%M:%S"), '%Y-%m-%d');
    $m->addNewSubmission('enregistrer', 'Enregistrer');

    if ($m->validate()) {
      $db->beginTransaction();
      try {
	$data = $m->get();
	$data['individu'] = $i->slug;
	$tf = new Formation();
	$did = explode('#', $data['diplome']);
	$data['diplome'] = (string)$did[0];
	if ($did[1] != 'NULL')
	  $data['branche'] = $did[1];
	$tf->insert($data);

	$this->_helper->Log("Formation enregistrée", array($i),
			    $this->_helper->Url('fiche', 'individus', null, array($i->slug)),
			    (string) $i);

	$db->commit();
	$this->redirectSimple('fiche');
      }
      catch (Exception $e) {
	$db->rollback();
	throw $e;
      }
    }
  }
}
