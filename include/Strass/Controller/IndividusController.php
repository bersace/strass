<?php

require_once 'Strass/Individus.php';
require_once 'Strass/Unites.php';

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
    $this->view->etape = $individu->findParentEtapes();
    $select = $individu->getTable()->select()->where('fin IS NULL');
    $this->view->appactives = $individu->findAppartenances($select);
    $select = $individu->getTable()->select()->where('fin IS NOT NULL')->order('debut DESC');
    $this->view->historique = $individu->findAppartenances($select);
    $s = $individu->getTable()->select()->order('date DESC');
    $s->order(array('date DESC', 'heure DESC'))->limit(5);
    $this->view->commentaires = $individu->findCommentaires(clone $s);
    $this->view->articles = $individu->findArticles(clone $s);
    $this->view->user = $user = $individu->findUser();

    $this->actions->append("Éditer la fiche",
			   array('action'	=> 'editer'),
			   array(null, $individu));
    $this->actions->append("Inscription",
			   array('action' => 'inscrire'),
			   array(null, $individu, 'inscrire'));
    $this->actions->append("Supprimer",
			   array('action' => 'supprimer'),
			   array(null, $individu, 'supprimer'));
    $this->actions->append("Administrer",
			   array('controller' => 'inscription',
				 'action' => 'administrer'),
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
	$champs = array('nom', 'prenom', 'naissance', 'portable',
			'fixe', 'adresse', 'notes');
	try {
	  $m->getInstance('adelec');
	  array_push($champs, 'adelec');
	}
	catch(Exception $e) {}

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
	  $individu->storeImage($tmp);
	}

	$this->logger->info("Fiche individu mis-à-jour",
			    $this->_helper->Url('fiche', 'individus', null,
						array('individu' => $individu->slug)));

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

  function inscrireAction()
  {
    $this->view->individu = $individu = $this->_helper->Individu();
    $this->metas(array('DC.Title' => 'Inscription'));
    $this->branche->append();

    $this->assert(null, $individu, 'inscrire',
		  "Vous n'avez pas le droit d'inscrire cet individu dans une unité.");

    $apps = $individu->findInscriptionsActives();
    $unites = $individu->findUnitesCandidates();

    $m = new Wtk_Form_Model('inscrire');
    $g = $m->addGroup('actuel');
    $g->addDate('date', "Date d'inscription");

    $gg = $g->addGroup('apps');
    $default_next = null;

    if ($apps->count()) {
      $default_next = $apps->rewind()->current()->unite;

      foreach ($apps as $app)
	$gg->addBool($app->id, "N'est plus ".$app->getShortDescription(), true);
    }

    if ($unites->count()) {
      $i0 = $g->addBool('inscrire', "Inscrire dans une autre unité ou promouvoir", true)
	/* Pour un nouveau, on viens forcément pour inscrire */
	->setReadonly((bool) $apps->count() == 0);
      $i1 = $g->addEnum('unite', "Unité", $default_next);
      foreach($unites as $u)
	$i1->addItem($u->id, wtk_ucfirst($u->getFullname()));
      if ($apps->count()) {
	$m->addConstraintDepends($i1, $i0);
      }
    }

    $g = $m->addGroup('role');
    $g->addEnum('role', 'Rôle');
    $i0 = $g->addBool('clore', "Ne l'est plus depuis", $apps->count() > 0);
    $i1 = $g->addDate('fin', "Date de fin", $m->get('actuel/date'));
    $m->addConstraintDepends($i1, $i0);

    $g = $m->addGroup('titre');
    $i = $g->addEnum('predefini', 'Titre', '$$autre$$', array('$$autre$$' => 'Autre'));
    $g->addString('autre', 'Autre');

    $this->view->model = $pm = new Wtk_Pages_Model_Form($m);

    $tu = new Unites;
    $tr = new Roles;

    $page = $pm->partialValidate();

    if ($pm->pageCmp($page, 'role') >= 0) {
      $g = $m->getInstance('role');

      /* Sélections des rôles ou on peut l'inscrire */
      $unite = $tu->findOne($m->get('actuel/unite'));
      $roles = $unite->findParentTypesUnite()->findRoles();
      $i = $g->getChild('role');
      foreach ($roles as $role)
	$i->addItem($role->id, wtk_ucfirst($role->titre));
    }

    /* Ne préremplir que si la page role va etre affichée */
    if ($pm->pageCmp($page, 'role') == 0) {
      /* Préselection du role */
      $candidats = $individu->findRolesCandidats($unite);
      if ($candidats->count())
	$i->set($candidats->current()->id);

      /* Présélection de la date */
      $annee = intval(strtok($m->get('actuel/date'), '/'));
      if ($app = $individu->findInscriptionSuivante($annee)) {
	/* on a trouvé un successeur, donc potentiellement on clot */
	$m->getInstance('role/clore')->set(TRUE);
	$m->getInstance('role/fin')->set($app->debut);
      }
      else
	$m->getInstance('role/fin')->set($m->get('actuel/date'));
    }

    $page = $pm->partialValidate();

    if ($pm->pageCmp($page, 'titre') >= 0) {
      $g = $m->getInstance('titre');
      $role = $tr->findOne($m->get('role/role'));
      $titres = $role->findTitres();
      $i = $g->getChild('predefini');
      foreach ($titres as $titre)
	$i->addItem($titre->nom, wtk_ucfirst($titre->nom));
    }

    if ($pm->validate()) {
      $t = new Appartenances;

      $db = $t->getAdapter();
      $db->beginTransaction();
      try {
	foreach($m->get('actuel/apps') as $k => $clore) {
	  if (!$clore)
	    continue;
	  $app = $t->findOne($k);
	  $app->fin = $m->get('actuel/date');
	  $app->save();
	}

	if ($m->get('actuel/inscrire')) {
	  $titre = $m->get('titre/predefini');
	  if ($titre == '$$autre$$')
	    $titre = $m->get('titre/autre');

	  $data = array('individu' => $individu->id,
			'unite' => $m->get('actuel/unite'),
			'role' => $m->get('role/role'),
			'titre' => $titre,
			'debut' => $m->get('actuel/date'),
			);
	  if ($m->get('role/clore'))
	    $data['fin'] = $m->get('role/fin');
	  $k = $t->insert($data);
	  $app = $t->findOne($k);
	}

	$this->logger->info("Inscription éditée", $this->_helper->Url('fiche'));
	$db->commit();
      }
      catch (Exception $e) { $db->rollBack(); throw $e; }

      $this->redirectSimple('fiche');
    }
  }

  function supprimerAction()
  {
    $this->view->individu = $i = $this->_helper->Individu();
    $this->assert(null, $i, 'supprimer',
		  "Vous n'avez pas le droit de supprime cette fiche.");

    $this->metas(array('DC.Title' => 'Supprimer '.$i->getFullname()));

    $this->view->model = $m = new Wtk_Form_Model('desinscrire');
    $m->addBool('confirmer',"Je confirme la destruction de cette fiche.", false);
    $m->addNewSubmission('continuer', 'Continuer');

    if ($m->validate()) {
      if ($m->get('confirmer')) {
	$db = $i->getTable()->getAdapter();
	$db->beginTransaction();
	try {
	  $this->logger->warn("Suppression de ".$i->getFullname(),
			      $this->_helper->Url('individus', 'admin'));
	  $i->delete();
	  $db->commit();
	}
	catch (Exception $e) { $db->rollBack(); throw $e; }

	$this->_helper->Flash->info("Fiche supprimée");
	$this->redirectSimple('individus', 'admin');
      }
      else {
	$this->redirectSimple('fiche', 'individus', null, array('individu' => $i->slug));
      }
    }
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
    $etapes = $te->fetchAll($select);
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
}
