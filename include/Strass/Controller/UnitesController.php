<?php

require_once 'Strass/Unites.php';
require_once 'Strass/Activites.php';

class UnitesController extends Strass_Controller_Action
{
  function indexAction()
  {
    $this->view->unite = $u = $this->_helper->Unite();
    $this->view->annee = $a = $this->_helper->Annee();
    $this->view->model = new Strass_Pages_Model_AccueilUnite($u, $a,
							     $this->_helper->Annee->cetteAnnee(),
							     $this->assert(null, $u, 'calendrier'));

    $this->metas(array('DC.Title' => wtk_ucfirst($u->getFullname()).' '.$a));

    $this->view->fiches = (bool) Zend_Registry::get('user');

    if (!$u->findParentTypesUnite()->virtuelle)
      $this->actions->append(array('label' => "Inscrire"),
			     array('action' => 'inscrire', 'unite' => $u->slug),
			     array(null, $u));

    $soustypename = $u->getSousTypeName();
    if (!$u->isTerminale() && $soustypename)
      $this->actions->append(array('label' => "Fonder une ".$soustypename),
			     array('action' => 'fonder', 'parente' => $u->slug),
			     array(null, $u));

    $journal = $u->findJournaux()->current();
    if (!$journal)
      $this->actions->append("Fonder le journal",
			     array('controller' => 'journaux', 'action' => 'fonder'),
			     array(null, $u, 'fonder-journal'));

    $this->actions->append("Éditer",
			   array('controller' => 'unites', 'action' => 'editer', 'unite' => $u->slug),
			   array(null, $u));

    if (!$u->isFermee())
      $this->actions->append("Fermer l'unité",
			     array('action' => 'fermer'),
			     array(null, $u));
  }

  function contactsAction()
  {
    $this->metas(array('DC.Title.alternative' => 'Effectifs'));
    $this->view->unite = $u = $this->_helper->Unite();
    $this->branche->append(null, array('annee' => false));
    $this->view->annee = $a = $this->_helper->Annee();
    $this->metas(array('DC.Title' => 'Effectifs '.$a));

    $this->assert(null, $u, null,
		  "Vous n'avez pas le droit de voir les contacts de l'unité");
    $this->view->model = new Strass_Pages_Model_Contacts($u, $a);

    $i = Zend_Registry::get('user');
    // si l'individu est connecté, on propose le lien.
    $this->view->fiches = (bool) $i;


    if (!$u->findParentTypesUnite()->virtuelle)
      $this->actions->append(array('label' => "Inscrire"),
			     array('action' => 'inscrire', 'unite' => $u->slug),
			     array(null, $u));

    /* $this->formats('vcf', 'ods', 'csv'); */
  }

  function fonderAction()
  {
    $this->view->parente = $unite = $this->_helper->Unite(null, false);
    $this->assert(null, $unite, 'fonder',
		  "Pas le droit de fonder une sous-unité !");

    $ttu = new TypesUnite;
    // sous types possibles
    if ($unite) {
      $soustypes = $unite->getSousTypes();
    }
    else {
      $soustypes = $ttu->fetchAll($ttu->select()->where('virtuelle = 0'));
    }

    $st = $soustypes->count() > 1 ? 'sous-unité' : $soustypes->rewind()->current();
    if ($unite)
      $this->metas(array('DC.Title' => 'Fonder une '.$st.' de '.$unite->getFullname()));
    else
      $this->metas(array('DC.Title' => 'Fonder une unité'));

    $ens = array();
    $enum = array();
    foreach($soustypes as $type) {
      $en = $type->getExtraName();
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


    $m = new Wtk_Form_Model('fonder');
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
      $db = Zend_Registry::get('db');
      $db->beginTransaction();
      try {
	$t = new Unites;

	extract($m->get());
	$data = array('slug' => wtk_strtoid($types[$type].'-'.$nom),
		      'nom' => $nom,
		      'type' => $type,
		      'extra' => $extra,
		      'parent' => $unite ? $unite->id : null);
	$k = $t->insert($data);
	$u = $t->findOne($k);

	$this->logger->info("Fondation de ".$u->getFullname(),
			    $this->_helper->Url('index', 'unites', null, array('unite' => $u->slug), true));

	$db->commit();
	$this->redirectSimple('index', 'unites', null, array('unite' => $u->slug), true);
      }
      catch(Exception $e) {
	$db->rollBack();
	throw $e;
      }
    }

    $this->view->model = $m;
  }

  function editerAction()
  {
    $u = $this->_helper->Unite();
    $this->assert(null, $u, 'editer',
		  "Vous n'avez pas le droit de modifier cette unité");

    $this->metas(array('DC.Title' => 'Éditer '.$u->getFullname()));

    $m = new Wtk_Form_Model('unite');
    $m->addString('nom', "Nom", $u->nom);
    $m->addString('extra',
		  $u->findParentTypesUnite()->getExtraName(),
		  $u->extra);
    $m->addFile('image', "Image");
    $w = $u->getWiki(null, false);
    $m->addString('presentation', "Message d'accueil", is_readable($w) ? file_get_contents($w) : '');
    $m->addNewSubmission('enregistrer', "Enregistrer");

    // métier;
    if ($m->validate()) {
      $db = Zend_Registry::get('db');
      $db->beginTransaction();
      try {
	$u->nom = $m->get('nom');
	$u->slug = wtk_strtoid($u->getFullname());
	$u->extra = $m->get('extra');
	$u->save();

	// photos
	$i = $m->getInstance('image');
	if ($i->isUploaded()) {
	  $u->saveImage($i->getTempFilename(), $i->getMimeType());
	}

	// wiki
	$w = $u->getWiki(null, false);
	$d = dirname($w);
	if (!file_exists($d))
	  mkdir($d, 0755, true);

	file_put_contents($w, trim($m->get('presentation')));

	$this->logger->info("Édition de ".$u->getFullname(),
			    array('controller' => 'unites', 'action' => 'index', 'unite' => $u->slug));

	$db->commit();
	$this->redirectSimple('index', 'unites', null,
			      array('unite' => $u->slug));
      }
      catch(Exception $e) {
	$db->rollBack();
	throw $e;
      }
    }

    // vue
    $this->view->unite = $u;
    $this->view->model = $m;
  }

  function inscrireAction()
  {
    $this->metas(array('DC.Title.alternative' => "Inscrire"));
    $this->view->unite = $u = $this->_helper->Unite();
    $this->branche->append(null, array('annee' => false));
    $this->view->annee = $a = $this->_helper->Annee();
    $this->metas(array('DC.Title' => "Inscrire pour l'année $a-".($a+1)));

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
      $g->addString('sexe', $tu->sexe);

    $g->addDate('naissance', 'Date de naissance', ($a - $tu->age_min) . '-01-01');

    /* Détails du mandat */
    $g = $m->addGroup('app');
    $roles = $u->findParentTypesUnite()->findRoles();
    $enum = array();
    foreach ($roles as $role) {
      $enum[$role->id] = wtk_ucfirst($role->titre);
    }
    $g->addEnum('role', 'Rôle', null, $enum);
    $g->addDate('debut', 'Début', $a.'-10-08');
    $i0 = $g->addBool('clore', 'Inscription terminée', false);
    $i1 = $g->addDate('fin', 'Fin', ($a+1).'-10-08');
    $m->addConstraintDepends($i1, $i0);

    $validated = $pm->validate();
    if ($m->get('individu/individu') == '$$nouveau$$') {
      /* Proposer un role inoccupé */
      if ($role = $u->findRolesCandidats($u, $a)->current())
	$m->getInstance('app/role')->set($role->id);
    } else {
      if ($pm->current == 'fiche') {
	/* Sauter l'étape fiche si l'individu est déjà en base */
	if ($m->sent_submission->id == 'continuer')
	  $pm->gotoPage('app');
	else if ($m->sent_submission->id == 'precedent')
	  $pm->gotoPage('individu');
      }
      else if ($pm->current == 'app') {
	/* préremplir l'inscription selon l'individu */
	$individu = $ti->findOne($m->get('individu/individu'));

	if ($role = $individu->findRolesCandidats($u)->current())
	  $m->getInstance('app/role')->set($role->id);

	$m->getInstance('app/clore')->set($individu->estActifDans($u));

	if ($app = $individu->findInscriptionSuivante($u, $a)) {
	  $m->getInstance('app/fin')->set($app->debut);
	}
      }
    }

    if ($validated) {
      $t = new Appartenances;
      $db->beginTransaction();
      try {
	if ($m->get('individu/individu') == '$$nouveau$$') {
	  $data = $m->get('fiche');
	  $data['slug'] = wtk_strtoid($data['prenom'].' '.$data['nom']);
	  $k = $ti->insert($data);
	  $i = $ti->findOne($k);
	}
	else {
	  $i = $individu;
	}

	$data = array('unite' => $u->id,
		      'individu' => $i->id,
		      'role' => $m->get('app/role'),
		      'debut' => $m->get('app/debut'),
		      );
	if ($m->get('app/clore'))
	  $data['fin'] = $m->get('app/fin');

	$t->insert($data);
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
    $u = $this->_helper->Unite();
    $m = new Wtk_Form_Model('fermer');
    $m->addDate('fin', 'Date de fermeture');
    $m->addNewSubmission('continuer', 'Continuer');
    $this->metas(array('DC.Title' => 'Fermer '.$u->getFullname()));

    if ($m->validate()) {
      $db = $u->getTable()->getAdapter();
      $db->beginTransaction();
      try {
	$u->fermer($m->get('fin'));
	$this->_helper->Log("Fermeture de l'unité ".$u, array($u),
			    $this->_helper->Url('index', 'unites', null, array('unite' => $u->id)),
			    (string) $u);
	$db->commit();
	$this->redirectSimple('index');
      }
      catch(Exception $e) {
	$db->rollBack();
	throw $e;
      }
    }

    $this->view->unite = $u;
    $this->view->model = $m;
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
	  $message = $nom." supprimé";
	  $this->logger->warn($message,
			      $this->_helper->Url('index', 'unites'));
	  $this->_helper->Flash->info($message);
	  $db->commit();
	  $this->redirectSimple('index', null, null, null, true);
	}
	catch(Exception $e) {
	  $db->rollBack();
	  throw $e;
	}
      }
      else
	$this->redirectSimple('index', null, null, array('unite' => $u->slug));
    }
  }

  function nouveauxAction()
  {
    $t = new Individus();
    $s = $t->select()
      ->from('individus')
      ->joinLeft('appartient',
		 'appartient.individu = individus.id',
		 array())
      ->where('appartient.individu IS NULL');
    $is = $t->fetchAll($s);
    $p = $this->_getParam('page');
    $p = $p ? $p : 1;
    $this->view->individus = new Wtk_Pages_Model_Iterator($is, 20, $p);
    $this->view->fiches = (bool) Zend_Registry::get('user');
    $this->branche->append('Nouveaux');
  }

  function nonenregistresAction()
  {
    $this->view->unite = $unite = $this->_helper->Unite();
    $annee = $this->_helper->Annee();

    $this->assert(null, $unite, 'nonenregistres',
		  "Vous n'avez pas le droit de voir les individus de ce site");

    $ti = new Individus;
    $s = $ti->select()
      ->from('individus')
      ->join('unites',
	     "unites.id = '".$unite->id."'".
	     " OR ".
	     "unite.parent = '".$unite->id."'",
	     array())
      ->join('appartient',
	     'appartient.individu = individus.id'.
	     ' AND '.
	     "appartient.unite = unites.id".
	     ' AND '.
	     "appartient.debut < '".$annee."-10'".
	     ' AND '.
	     ("(appartient.fin > '".($annee+1)."-08'".
	      ' OR '.
	      "appartient.fin IS NULL)"),
	     array())
      ->where("individus.username IS NULL or individus.username = ''")
      ->order('individus.id');
    $is = $ti->fetchAll($s);
    $p = $this->_getParam('page');
    $p = $p ? $p : 1;
    $this->view->individus = new Wtk_Pages_Model_Iterator($is, 20, $p);
    $this->view->fiches = (bool) Zend_Registry::get('user');
  }
}
