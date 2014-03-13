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
    $this->view->unite = $u = $this->_helper->Unite();
    $this->view->model = new Strass_Pages_Model_Calendrier($u, $this->_helper->Annee());
    $this->_helper->Annee->setBranche($this->view->annee = $annee = $this->view->model->current);
    $this->metas(array('DC.Title' => 'Calendrier '.$annee,
		       'DC.Title.alternative' => 'Calendrier '.$annee.
		       ' – '.$u->getFullname()));

    if ($annee >= date('Y', time()-243*24*60*60))
      $this->assert(null, $u, 'calendrier',
		    "Vous n'avez pas le droit de voir le calendrier de cette unité.");

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

    $t = new Unites;
    $enum = array();
    foreach($t->fetchAll() as $unite)
      if ($this->assert(null, $unite, 'prevoir'))
	$enum[$unite->id] = $unite->getFullname();

    if (!$enum)
      throw new Strass_Controller_Action_Exception_Notice("Vous ne pouvez pas enregistrer une activité");
    $i = $m->addEnum('unites', 'Unités participantes', key($enum), $enum, true);    // multiple
    $m->addConstraintRequired($i);

    $annee = $this->_helper->Annee(false);
    // On ne décale pas en septembre afin de réserver pour la date
    // actuelle si possible.
    $annee = $annee ? $annee : date('Y');

    $this->view->model = $m = new Wtk_Form_Model('prevoir');
    $enum = array();
    foreach($programmables as $unite)
      $enum[$unite->id] = $unite->getFullname();
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

    $this->metas(array('DC.Title' => $a->getIntitule()));

    $this->view->documents = $a->findPiecesJointes();
    $this->view->photos = $a->findPhotosAleatoires(6);
    $i = Zend_Registry::get('individu');

    if (!$a->isFuture()) {
      $this->actions->append("Envoyer une photo",
			     array('action' => 'envoyer',
				   'controller' => 'photos',
				   'activite' => $a->slug),
			     array(null, $a, 'envoyer-photo'));
      $this->connexes->append("Photos",
			     array('action' => 'consulter',
				   'controller' => 'photos',
				   'album' => $a->slug));
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

    $this->view->model = $m = new Wtk_Form_Model('activite');

    $t = new Unites;
    $explicites = $a->findUnitesParticipantesExplicites();
    $enum = array();
    foreach($t->fetchAll() as $unite)
      if ($this->assert(null, $unite, 'prevoir'))
	$enum[$unite->id] = $unite->getFullname();
    $values = array();
    foreach($explicites as $unite)
      $values[] = $unite->id;
    $i = $m->addEnum('unites', 'Unités participantes', $values, $enum, true);    // multiple
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
    $this->view->activite = $a = $this->_helper->Activite();
    $this->metas(array('DC.Title' => 'Annuler '.$a->getIntitule()));
    $this->assert(null, $a, 'annuler',
		  "Vous n'avez pas le droit d'annuler cette activité");


    $this->view->model = $m = new Wtk_Form_Model('annuler');
    $m->addBool('confirmer',
		"Je confirme la destruction de toute informations relative à l'activité ".
		$a->getIntituleCourt().".", false);
    $m->addNewSubmission('continuer', 'Continuer');

    if ($m->validate()) {
      if ($m->confirmer) {
	$db = $a->getTable()->getAdapter();
	$db->beginTransaction();
	try {
	  // desctruction des documents
	  // liés *uniquement* à cette
	  // activité.
	  $das = $a->findPiecesJointes();
	  foreach($das as $da) {
	    $doc = $da->findParentDocuments();
	    if ($doc->countLiaisons() == 1)
	      $doc->delete();
	  }
	  // destruction de l'activité.
	  $unite = $a->findUnitesParticipantesExplicites()->current();
	  $intitule = $a->getIntituleComplet();
	  $a->delete();
	  $this->logger->warn("Activité annulée",
			      $this->_helper->Url('calendrier', 'activites',
						  null, array('unite' => $unite->slug)));
	  $this->_helper->Flash->info("Activité annulée");
	  $db->commit();
	}
	catch(Exception $e) { $db->rollBack(); throw $e; }

	$this->redirectSimple('index', 'activites');
      }
      else {
	$this->redirectSimple('consulter', 'activites', null,
			      array('activite' => $a->slug));
      }
    }
  }
}
