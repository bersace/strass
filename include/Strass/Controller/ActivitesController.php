<?php

require_once 'Strass/Individus.php';
require_once 'Strass/Activites.php';
require_once 'Strass/Unites.php';
require_once 'Strass/Photos.php';

class ActivitesController extends Strass_Controller_Action
{
  /**
   * Par défaut on redirige automatiquement vers l'unité actuelle de l'individu
   * sinon, vers les photos …
   */
  function indexAction()
  {
    $i = Zend_Registry::get('individu');
    if (!$i)
      throw new Strass_Controller_Action_Exception_Notice("Vous devez être identifié pour voir ".
							  "le calendrier des activités.");

    $u = current($i->findUnites());

    if ($u)
      $this->redirectUrl(array('action' => 'calendrier',
			       'unite' => $u->slug));
    else
      throw new Strass_Controller_Action_Exception_Notice("Vous n'appartenez à aucune ".
							  "unité, impossible de vous ".
							  "présenter vos activités !");
  }

  /* Afficher les activités d'une unité pour une année. On affiche aussi un
     liens vers chacune des années où l'unité a participé à des activités. */
  function calendrierAction()
  {
    $u = $this->_helper->Unite();
    $annee = $this->_helper->Annee();
    $future = $annee >= date('Y', time()-243*24*60*60);

    $this->metas(array('DC.Title' => 'Calendrier '.$annee,
		       'DC.Title.alternative' => 'Calendrier '.$annee.
		       ' – '.wtk_ucfirst($u->getFullname())));

    if ($future)
      $this->assert(null, $u, 'calendrier',
		    "Vous n'avez pas le droit de voir le calendrier de cette unité.");

    $this->view->model = new Strass_Pages_Model_Calendrier($u, $annee);
    $this->view->unite = $u;
    $this->view->annee = $annee;

    $this->actions->append("Nouvelle activité",
			   array('action' => 'prevoir'),
			   array(Zend_Registry::get('user'), $u));

    $this->formats('ics');
  }

  /* prévoir une nouvelle activité pour une ou plusieurs unités */
  function prevoirAction()
  {
    $this->metas(array('DC.Title' => 'Prévoir une nouvelle activité',
		       'DC.Title.alternate' => 'Prévoir'));
    $u = $this->_helper->Unite(false);
    $this->branche->append();

    $individu = Zend_Registry::get('individu');
    $t = new Unites;
    $unites = $individu->findUnites();
    $programmables = array();
    foreach($unites as $unite)
      if ($this->assert(null, $unite, 'prevoir'))
	array_push($programmables, $unite);

    if (!$programmables)
      throw new Strass_Controller_Action_Exception_Notice("Vous n'avez pas le droit d'enregistrer une activité");

    $annee = $this->_helper->Annee(false);
    // On ne décale pas en septembre afin de réserver pour la date
    // actuelle si possible.
    $annee = $annee ? $annee : date('Y');

    $this->view->model = $m = new Wtk_Form_Model('prevoir');
    $enum = array();
    foreach($programmables as $unite)
      $enum[$unite->id] = wtk_ucfirst($unite->getFullname());
    $default = $u ? $u->id : null;
    $m->addEnum('unites', 'Unités participantes',
		$default, $enum, true);        // multiple
    $m->addDate('debut', 'Début',
		$annee.date('-m-d').' 14:30',
		'%Y-%m-%d %H:%M');
    $m->addDate('fin', 'Fin',
		$annee.date('-m-d', time()+60*60*24).'17:00',
		'%Y-%m-%d %H:%M');
    $m->addString('intitule', 'Intitulé explicite', "");
    $m->addString('lieu', 'Lieu');

    $m->addBool('prevoir', "J'ai d'autres activités à prévoir", true);
    $m->addNewSubmission('ajouter', 'Ajouter');
    $m->addConstraintRequired($m->getInstance('unites'));

    if ($m->validate()) {
      $t = new Activites;
      $tu = new Unites;

      $a = new Activite;
      $a->debut = $m->debut;
      $a->fin = $m->fin;
      $a->lieu = $m->lieu;

      // Sélectionner les sous unités des unités sélectionné à l'année de l'activité
      $unites = $m->unites;
      $annee = intval(date('Y', strtotime($m->get('debut')) - 243 * 24 * 60 * 60));
      $participantes = $tu->getIdSousUnites((array) $unites, $annee);

      // génération de l'intitulé
      $unites = $tu->find(array_values($participantes));
      $type = $unites[0]->findParentTypesUnite();
      $a->intitule = $m->intitule;
      $intitule = $type->getIntituleCompletActivite($a);
      $a->slug = $slug = $t->createSlug(wtk_strtoid($intitule));

      $db = $t->getAdapter();
      $db->beginTransaction();
      try {
	$a->save();

	$tp = new Participations;
	$tp->updateActivite($a, $participantes);

	$this->_helper->Flash->info("Activité enregistrée");
	$this->logger->info("Nouvelle activite",
			    $this->_helper->Url('consulter', null, null, array('activite' => $a->slug)));

	$db->commit();
      }
      catch(Exception $e) {
	$db->rollBack();
	throw $e;
      }

      if ($m->get('prevoir'))
	$this->redirectSimple('prevoir');
      else
	$this->redirectSimple('consulter', null, null, array('activite' => $slug));
    }
  }

  function consulterAction()
  {
    $this->view->activite = $a = $this->_helper->Activite();
    $this->assert(null, $a, 'consulter',
		  "Vous n'avez pas le droit de consulter les détails de cette activité");

    $this->metas(array('DC.Title' => wtk_ucfirst($a->getIntitule())));

    $this->view->documents = $a->findPiecesJointes();
    $i = Zend_Registry::get('individu');

    if (!$a->isFuture()) {
      $this->actions->append("Envoyer une photo",
			     array('action' => 'envoyer',
				   'controller' => 'photos',
				   'activite' => $a->slug),
			     array(null, $a, 'envoyer-photo'));
    }

    $this->actions->append('Éditer',
			   array('action' => 'editer',
				 'activite' => $a->slug),
			   array(null, $a));
    $this->actions->append('Annuler',
			   array('action' => 'annuler',
				 'activite' => $a->slug),
			   array(null, $a));

    $this->formats('ics');
  }

  function editerAction()
  {
    $this->view->activite = $a = $this->_helper->Activite();
    $this->assert(null, $a, 'editer',
		  "Vous n'avez pas le droit d'éditer cettes activités");

    $this->metas(array('DC.Title' => 'Éditer '.$a->getIntitule()));

    $individu = Zend_Registry::get('individu');
    $unites = $individu->findUnites();
    $programmables = array();
    foreach($unites as $unite)
      if ($this->assert(null, $unite, 'prevoir'))
	array_push($programmables, $unite);
    $participantes = $a->findUnitesViaParticipations();
    $explicites = Activites::findUnitesParticipantesExplicites($participantes);

    $enum = array();
    foreach($programmables as $unite)
      $enum[$unite->id] = wtk_ucfirst($unite->getFullname());
    $this->view->model = $m = new Wtk_Form_Model('activite');
    $i = $m->addEnum('unites', 'Unités participantes', $explicites, $enum, true);    // multiple
    $m->addConstraintRequired($i);

    $m->addString('intitule', 'Intitulé explicite', $a->intitule);
    $m->addString('lieu', 'Lieu', $a->lieu);
    $m->addDate('debut', 'Début', $a->debut, '%Y-%m-%d %H:%M');
    $m->addDate('fin', 'Fin', $a->fin, '%Y-%m-%d %H:%M');
    $m->addNewSubmission('enregistrer', 'Enregistrer');

    if ($m->validate()) {
      $t = new Activites;
      $tu = new Unites;
      $unites = $tu->getIdSousUnites((array) $m->get('unites'), $a->getAnnee());

      $db = $a->getTable()->getAdapter();
      $db->beginTransaction();
      try {
	$champs = array('debut', 'fin', 'lieu');
	foreach($champs as $champ)
	  $a->$champ = $m->get($champ);

	$data = $m->get();
	unset($data['unites']);
	$a->intitule = $m->intitule;
	$a->slug = $t->createSlug(wtk_strtoid($a->getIntituleComplet()), $a->slug);
	$a->save();

	$tp = new Participations;
	$tp->updateActivite($a, $unites);

	$this->logger->info("Activité mise-à-jour",
			    $this->_helper->Url('consulter', null, null, array('activite' => $a->slug)));

	$db->commit();

	$this->redirectSimple('consulter', null, null, array('activite' => $a->slug));
      }
      catch(Exception $e) {
	$db->rollBack();
	throw $e;
      }
    }
  }

  function annulerAction()
  {
    $a = $this->_helper->Activite();
    $this->assert(Zend_Registry::get('user'), $a, 'annuler',
		  "Vous n'avez pas le droit d'annuler cette activités");

    $this->metas(array('DC.Title' => 'Annuler '.$a->getIntitule()));


    $m = new Wtk_Form_Model('annuler');
    $m->addBool('confirmer',
		"Je confirme la destruction de toute informations relative à l'activite ".
		$a->intitule.".", false);
    $m->addNewSubmission('continuer', 'Continuer');

    if ($m->validate()) {
      if ($m->get('confirmer')) {
	$db = $a->getTable()->getAdapter();
	$db->beginTransaction();
	try {
	  // desctruction des documents
	  // liés *uniquement* à cette
	  // activité.
	  $das = $a->findDocsActivite();
	  foreach($das as $da) {
	    $doc = $da->findParentDocuments();
	    if ($doc->countLiaisons() == 1)
	      $doc->delete();
	  }
	  // destruction de l'activité.
	  $unite = $a->findUnitesParticipantesExplicites()->current();
	  $intitule = (string) $a;
	  $a->delete();
	  $this->_helper->Log("Activité annulé", array(),
			      $this->_helper->url->Url(array('action' => 'calendrier',
							     'unite' => $unite->slug)),
			      $intitule);

	  $db->commit();
	  $this->redirectSimple('index', 'activites');
	}
	catch(Exception $e) {
	  $db->rollBack();
	  throw $e;
	}
      }
      else {
	$this->redirectSimple('consulter', 'activites', null,
			      array('activite' => $a->slug));
      }
    }

    $this->branche->append(ucfirst($a->intitule),
			   array('action' => 'consulter'));

    $this->view->activite = $a;
    $this->view->model = $m;
  }

  // HELPER
  function findUnitesProgrammable($assert = TRUE) {
    $unites = array();
    $individu = Zend_Registry::get('individu');
    if ($this->assert()) {
      // Sélectionner toutes les unites pour les admin !
      $table = new Unites;
      $rows = $table->fetchAll();
      foreach($rows as $row)
	$unites[$row->id] = wtk_ucfirst($row->getFullname());
    }
    else {
      // Sélectionner les unité où l'individu est ou a été inscrit
      // et dont il a le droit de prévoir une activité.
      $us = $individu->findUnites(null, true);
      foreach($us as $u)
	if ($this->assert(null, $u, 'prevoir-activite'))
	  $unites[$u->id] = wtk_ucfirst($u->getFullname());
    }

    if (!count($unites) && $assert) {
      throw new Strass_Controller_Action_Exception
	("Vous n'avez le droit de prévoir d'activité pour aucune unités !");
    }
    return $unites;
  }
}
