<?php

require_once 'Strass/Unites.php';
require_once 'Strass/Activites.php';

class UnitesController extends Strass_Controller_Action
{
  function indexAction()
  {
    $this->view->unite = $u = $this->_helper->Unite();
    $this->view->model = new Strass_Pages_Model_AccueilUnite($u, $this->_helper->Annee());
    $this->_helper->Annee->setBranche($this->view->annee = $a = $this->view->model->current);
    $this->metas(array('DC.Title' => $u->getFullname().' '.$a));

    $this->view->fiches = (bool) Zend_Registry::get('user');
    $config = new Strass_Config_Php($u->slug);
    $default = $u->isTerminale() ? array('photos') : array('unites');
    $this->view->blocs = $config->get('blocs', $default);

    if (!$u->findParentTypesUnite()->virtuelle)
      $this->actions->append(array('label' => "Inscrire"),
			     array('action' => 'inscrire', 'unite' => $u->slug),
			     array(null, $u));

    $soustypename = $u->getSousTypeName();
    if (!$u->isTerminale() && $soustypename)
      $this->actions->append(array('label' => "Fonder une ".$soustypename),
			     array('action' => 'fonder', 'unite' => $u->slug),
			     array(null, $u));

    $journal = $u->findJournaux()->current();
    if (!$journal)
      $this->actions->append("Fonder le journal",
			     array('controller' => 'journaux', 'action' => 'fonder'),
			     array(null, $u, 'fonder-journal'));

    $this->actions->append("Éditer l'unité",
			   array('controller' => 'unites', 'action' => 'editer', 'unite' => $u->slug),
			   array(null, $u));

    $this->actions->append("Paramétrer de la page",
			   array('controller' => 'unites', 'action' => 'parametres', 'unite' => $u->slug),
			   array(null, $u));

    if (!$u->isFermee())
      $this->actions->append("Fermer l'unité",
			     array('action' => 'fermer'),
			     array(null, $u));
  }

  function effectifsAction()
  {
    $this->metas(array('DC.Title.alternative' => 'Effectifs'));
    $this->view->unite = $u = $this->_helper->Unite();
    $this->branche->append(null, array('annee' => false));

    $this->view->model = new Strass_Pages_Model_Effectifs($u, $this->_helper->Annee());
    $this->_helper->Annee->setBranche($this->view->annee = $a = $this->view->model->current);
    $this->metas(array('DC.Title' => 'Effectifs '.$a));

    $this->view->fiches = $this->assert(null, $u, 'fiches');

    if (!$u->findParentTypesUnite()->virtuelle)
      $this->actions->append(array('label' => "Inscrire"),
			     array('action' => 'inscrire', 'unite' => $u->slug),
			     array(null, $u));

    if ($this->view->fiches)
      $this->formats('vcf', 'csv');
  }

  function fonderAction()
  {
    $this->view->parente = $unite = $this->_helper->Unite(false);
    $this->assert(null, $unite, 'fonder',
		  "Pas le droit de fonder une sous-unité !");

    /* on crée une sous unité si le parent est explicitement désignée */
    $this->view->sousunite = $sousunite = $this->_getParam('unite');
    $ttu = new TypesUnite;
    // sous types possibles
    if ($this->view->sousunite)
      $soustypes = $unite->getSousTypes();
    else
      $soustypes = $ttu->fetchAll($ttu->select()->where('virtuelle = 0'));

    $st = $soustypes->count() > 1 ? 'sous-unité' : $soustypes->rewind()->current();
    if ($sousunite)
      $this->metas(array('DC.Title' => 'Fonder une '.$st.' de '.$unite->getFullname()));
    else
      $this->metas(array('DC.Title' => 'Fonder une unité'));

    $m = new Wtk_Form_Model('fonder');
    /* Parente */
    $i = $m->addEnum('parente', 'Unité parente');
    if ($sousunite)
      $i->set($unite->id);
    else {
      $t = new Unites;
      $i->addItem(null, 'Orpheline');
      foreach($t->findSuperUnites() as $u)
	$i->addItem($u->id, $u->getFullname());
    }

    /* Types */
    $ens = array();
    $enum = array();
    foreach($soustypes as $type) {
      $en = $type->extra;
      if ($en)
	array_push($ens, $en);

      $label = wtk_ucfirst($type->nom);
      /* en cas de nom doublon (ex: équipe, sizaine), inclure le nom du type parent */
      $homonymes = $ttu->countRows($ttu->select()->where('nom = ?', $type->nom)) > 1;
      if (!$unite && $homonymes)
	$label.= ' (' .$type->findParentTypesUnite()->nom. ')';
      $enum[$type->id] =  $label;
    }
    $ens = array_unique($ens);
    $types = $enum;


    $m->addEnum('type', 'Type', key($enum), $enum);

    if (key($enum) == 'sizloup') {
      // préselectionner les couleurs des loups.
      $couleurs =
	array('noir', 'gris', 'brun', 'blanc', 'fauve', 'tacheté');
      $enum = array();
      foreach($couleurs as $couleur) {
	// ne pas permettre de recréer une sizaine.
	$ex = $unite->findUnites("unites.nom = '".$couleur."'")->current();
	if (!$ex)
	  $enum[wtk_strtoid($couleur)] = wtk_ucfirst($couleur);
      }
      $m->addEnum('nom', 'Nom', null, $enum);
    }
    else
      $m->addString('nom', "Nom");

    $m->addString('extra', current($ens));
    $m->addNewSubmission('fonder', 'Fonder');

    if ($m->validate()) {
      $t = new Unites;
      $db = $t->getAdapter();
      $db->beginTransaction();
      try {
	$u = new Unite;
	$u->slug = $t->createSlug(wtk_strtoid($types[$m->type].'-'.$m->nom));
	$u->nom = $m->nom;
	$u->type = $m->type;
	$u->extra = $m->extra;
	$u->parent = $m->parente ? $m->parente : null;
	$u->save();

	$this->logger->info("Fondation de ".$u->getFullname(),
			    $this->_helper->Url('index', 'unites', null, array('unite' => $u->slug), true));

	$db->commit();
      }
      catch(Exception $e) { $db->rollBack(); throw $e; }
      $this->redirectSimple('index', 'unites', null, array('unite' => $u->slug), true);
    }

    $this->view->model = $m;
  }

  function archivesAction()
  {
    $this->view->unite = $u = $this->_helper->Unite();
    $this->metas(array('DC.Title' => 'Archives'));
    $this->branche->append();

    $this->view->fermees = $u->findFermees();
  }

  function editerAction()
  {
    $this->view->unite = $u = $this->_helper->Unite();
    $this->assert(null, $u, 'editer',
		  "Vous n'avez pas le droit de modifier cette unité");

    $this->metas(array('DC.Title' => 'Éditer '.$u->getFullname()));

    $this->view->model = $m = new Wtk_Form_Model('unite');
    $enum = array(null => 'Orpheline');
    foreach ($u->findParenteCandidates() as $c)
      $enum[$c->id] = $c->getFullname();
    $m->addEnum('parente', "Unité parente", $u->parent, $enum);
    $m->addString('nom', "Nom", $u->nom);
    $m->addString('extra',
		  $u->findParentTypesUnite()->extra,
		  $u->extra);
    $m->addFile('image', "Image");
    $w = $u->getWiki(null, false);
    $m->addString('presentation', "Message d'accueil", is_readable($w) ? file_get_contents($w) : '');
    $m->addNewSubmission('enregistrer', "Enregistrer");

    if ($m->validate()) {
      $t = $u->getTable();
      $db = $t->getAdapter();
      $db->beginTransaction();
      try {
	$u->parent = $m->parente ? $m->parente : null;
	$u->nom = $m->nom;
	$u->slug = $t->createSlug(wtk_strtoid($u->getFullname()), $u->slug);
	$u->extra = $m->extra;
	$u->save();

	$u->storePresentation($m->get('presentation'));
	$i = $m->getInstance('image');
	if ($i->isUploaded())
	  $u->storeImage($i->getTempFilename());

	$this->logger->info("Édition de ".$u->getFullname(),
			    array('controller' => 'unites', 'action' => 'index', 'unite' => $u->slug));

	$db->commit();
      }
      catch(Exception $e) { $db->rollBack(); throw $e; }

      $this->redirectSimple('index', 'unites', null, array('unite' => $u->slug));
    }
  }

  function parametresAction()
  {
    static $blocs = array('unites' => 'Les unités',
			  'photos' => 'Photos aléatoires',
			  'activites' => 'Activités marquantes',
			  );

    $this->view->unite = $u = $this->_helper->Unite();
    $this->assert(null, $u, 'parametres',
		  "Vous n'avez pas le droit de modifier cette unité");

    $this->metas(array('DC.Title' => 'Paramètres '.$u->getFullname()));

    $this->view->model = $m = new Wtk_Form_Model('parametres');
    $config = new Strass_Config_Php($u->slug);
    $t = $m->addTable('blocs', "Blocs de la page d'accueil",
		      array('id' => array('String'),
			    'nom' => array('String', 'Bloc', true),
			    'enable' => array('Bool', 'Actif')),
		      true, false);

    $enabled = $config->blocs;
    if ($enabled)
      $enabled = $enabled->toArray();
    else
      $enabled = array();

    foreach($enabled as $k)
      $r = $t->addRow($k, $blocs[$k], true);

    /* nouveau blocs */
    foreach($blocs as $k => $v)
      if (!in_array($k, $enabled))
	$r = $t->addRow($k, $v, false);

    $m->addNewSubmission('enregistrer', "Enregistrer");

    if ($m->validate()) {
      $blocs = array();
      foreach ($m->blocs as $row)
	if ($row['enable'])
	  array_push($blocs, $row['id']);
      $config->blocs = $blocs;
      $config->write();
      $this->logger->info("Configuration de page d'accueil");
      $this->redirectSimple('index', 'unites', null, array('unite' => $u->slug));
    }
  }

  function inscrireAction()
  {
    $this->metas(array('DC.Title.alternative' => "Inscrire"));
    $this->view->unite = $u = $this->_helper->Unite();
    $this->branche->append(null, array('annee' => false));
    $this->view->annee = $a = $this->_helper->Annee();
    $this->metas(array('DC.Title' => "Inscrire pour l'année $a-".($a+1)));

    $this->assert(null, $u, 'inscrire',
		  "Vous n'avez pas le droit d'inscrire dans cette unité");

    $ti = new Individus;
    $db = $ti->getAdapter();

    $m = new Wtk_Form_Model('inscrire');
    $this->view->model = $pm = new Wtk_Pages_Model_Form($m);

    /* Sélection de l'individu à inscrire */
    $g = $m->addGroup('individu');
    $candidats = $u->findCandidats($a);
    $enum = array();
    $enum['$$nouveau$$'] = 'Inscrire un nouveau';
    foreach($candidats as $candidat) {
      $enum[$candidat->id] = $candidat->getFullname(false, false);
    }
    $g->addEnum('individu', 'Individu', null, $enum);

    /* Enregistrement d'un nouvel individu */
    $g = $m->addGroup('fiche');
    $g->addString('prenom', 'Prénom');
    $g->addString('nom', 'Nom');
    $tu = $u->findParentTypesUnite();
    if ($tu->sexe == 'm')
      $g->addEnum('sexe', 'Sexe', null, array('h' => 'Masculin', 'f' => 'Féminin'));
    else
      $g->addString('sexe', null, $tu->sexe)->setReadonly();

    $g->addDate('naissance', 'Date de naissance', ($a - $tu->age_min) . '-01-01');

    /* Détails du mandat */
    $g = $m->addGroup('app');
    $roles = $u->findParentTypesUnite()->findRoles();
    $enum = array();
    foreach ($roles as $role) {
      $enum[$role->id.'__'] = wtk_ucfirst($role->titre);
      foreach ($role->findTitres() as $titre) {
	$enum[$role->id.'__'.$titre->nom] = wtk_ucfirst($titre->nom);
      }
    }
    $g->addEnum('role', 'Rôle', null, $enum);
    $g->addDate('debut', 'Début', $a.'-10-08');
    $i0 = $g->addBool('clore', 'Inscription terminée', false);
    $i1 = $g->addDate('fin', 'Fin', ($a+1).'-10-08');
    $m->addConstraintDepends($i1, $i0);

    $page = $pm->partialValidate();

    if ($m->get('individu/individu') == '$$nouveau$$') {
      /* Proposer un role inoccupé */
      if ($role = $u->findRolesCandidats($u, $a)->current())
	$m->getInstance('app/role')->set($role->id);
    } else {
      if ($page == 'fiche') {
	/* Sauter l'étape fiche si l'individu est déjà en base */
	if ($m->sent_submission->id == 'continuer')
	  $pm->gotoPage('app');
	else if ($m->sent_submission->id == 'precedent')
	  $pm->gotoPage('individu');
      }
      else if ($pm->pageCmp($page, 'app') >= 0) {
	/* préremplir l'inscription selon l'individu */
	$individu = $ti->findOne($m->get('individu/individu'));

	if ($role = $individu->findRolesCandidats($u)->current())
	  $m->getInstance('app/role')->set($role->id);

	$m->getInstance('app/clore')->set($individu->estActifDans($u));

	if ($app = $individu->findInscriptionSuivante($a)) {
	  $m->getInstance('app/fin')->set($app->debut);
	}
      }
    }

    if ($pm->validate()) {
      $t = new Appartenances;
      $db->beginTransaction();
      try {
	if ($m->get('individu/individu') == '$$nouveau$$') {
	  $i = new Individu;
	  $i->prenom = $m->get('fiche/prenom');
	  $i->nom = $m->get('fiche/nom');
	  $i->sexe = $m->get('fiche/sexe');
	  $i->naissance = $m->get('fiche/naissance');
	  $i->slug = $i->getTable()->createSlug(wtk_strtoid($i->getFullname(false, false)));
	  $i->save();
	}
	else {
	  $i = $individu;
	}

	$app = new Appartient;
	$app->unite = $u->id;
	$app->individu = $i->id;
	$app->debut = $m->get('app/debut');
	list($role, $titre) = explode('__', $m->get('app/role'));
	$app->role = intval($role);
	$app->titre = $titre;
	if ($m->get('app/clore'))
	  $app->fin = $m->get('app/fin');
	$app->save();

	$message = $i->getFullname(false, false)." inscrit.";
	$this->logger->info($message);
	$this->_helper->Flash->info($message);
	$db->commit();
      }
      catch (Exception $e) {
	$db->rollBack();
	throw $e;
      }

      $this->redirectSimple('index');
    }
  }

  function fermerAction()
  {
    $this->view->unite = $u = $this->_helper->Unite();
    $this->metas(array('DC.Title' => 'Fermer '.$u->getFullname(),
		       'DC.Title.alternative' => 'Fermer'));
    $this->branche->append();

    $this->assert(null, $u, 'fermer',
		  "Vous n'avez pas le droit de fermer cette unité");

    $this->view->model = $m = new Wtk_Form_Model('fermer');
    $m->addDate('fin', 'Date de fermeture');
    $m->addNewSubmission('continuer', 'Continuer');

    if ($m->validate()) {
      $db = $u->getTable()->getAdapter();
      $db->beginTransaction();
      try {
	$u->fermer($m->fin);
	$this->logger->warn("Fermeture de l'unité ".$u,
			    $this->_helper->Url('index', 'unites', null, array('unite' => $u->slug)));
	$db->commit();
      }
      catch(Exception $e) { $db->rollBack(); throw $e; }

      $this->redirectSimple('index');
    }
  }

  function supprimerAction()
  {
    $this->view->unite = $u = $this->_helper->Unite();
    $this->assert(null, $u, 'supprimer',
		  "Vous n'avez pas le droit de supprimer cette unité.");

    $this->metas(array('DC.Title' => 'Supprimer '.$u->getFullname()));

    $this->view->model = $m = new Wtk_Form_Model('supprimer');
    $m->addBool('confirmer',
		"Je confirme la suppression de l'unité et de toutes ses données.", false);
    $m->addNewSubmission('continuer', 'Continuer');

    if ($m->validate()) {
      if ($m->get('confirmer')) {
	$db = $u->getTable()->getAdapter();
	$db->beginTransaction();
	try {
	  $nom = (string) $u;
	  $u->delete();
	  $message = wtk_ucfirst($nom)." supprimé";
	  $this->logger->warn($message,
			      $this->_helper->Url('index', 'unites'));
	  $this->_helper->Flash->info($message);
	  $db->commit();
	}
	catch(Exception $e) { $db->rollBack(); throw $e; }

	$this->redirectSimple('unites', 'admin', null, null, true);
      }
      else
	$this->redirectSimple('index', null, null, array('unite' => $u->slug));
    }
  }
}
