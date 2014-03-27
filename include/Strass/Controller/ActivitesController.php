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
      $this->redirectUrl(array('action' => 'calendrier', 'unite' => $u->slug));
    else
      throw new Strass_Controller_Action_Exception_Notice("Vous n'appartenez à aucune unité", 404,
							  "Impossible de vous présenter vos activités !");
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

    $this->view->model = $m = new Wtk_Form_Model('prevoir');

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
    $debut = $annee ? $annee.'-09-01' : strftime('%Y-%m-%d');
    $fin = $annee ? $annee.'-09-02' : strftime('%Y-%m-%d', time() + 60 * 60 * 24);
    $m->addDate('debut', 'Début', $debut.' 14:30', '%Y-%m-%d %H:%M');
    $m->addDate('fin', 'Fin', $fin.'17:00', '%Y-%m-%d %H:%M');
    $m->addString('intitule', 'Intitulé explicite', "");
    $m->addString('lieu', 'Lieu');

    $enum = array(null => 'Nouveau document');
    foreach ($u->findDocuments() as $doc)
      $enum[$doc->id] = $doc->titre;
    $t = $m->addTable('documents', "Pièces-jointes",
		      array('document' => array('Enum', "Document", $enum),
			    'fichier' => array('File', "Envoi"),
			    'titre' => array('String', "Titre")),
                     false);
    $t->addRow();

    $m->addBool('prevoir', "J'ai d'autres activités à prévoir", true);
    $m->addNewSubmission('ajouter', 'Ajouter');
    $m->addConstraintRequired($m->getInstance('unites'));

    if ($m->validate()) {
      $t = new Activites;
      $tu = new Unites;
      $td = new Documents;

      $a = new Activite;
      $a->debut = $m->debut;
      $a->fin = $m->fin;
      $a->lieu = $m->lieu;

      $unites = call_user_func_array(array($tu, 'find'), (array) $m->unites);
      // génération de l'intitulé
      $type = $unites->current()->findParentTypesUnite();
      $a->intitule = $m->intitule;
      $intitule = $type->getIntituleCompletActivite($a);
      $a->slug = $slug = $t->createSlug($intitule);

      $db = $t->getAdapter();
      $db->beginTransaction();
      try {
	$a->save();
	$a->updateUnites($unites);

	foreach($m->getInstance('documents') as $row) {
	  $if = $row->getChild('fichier');

	  if ($row->document)
	    $d = $td->findOne($row->document);
	  elseif (!$if->isUploaded())
	    continue;
	  else {
	    $d = new Document;
	    $d->slug = $d->getTable()->createSlug($row->titre);
	    $d->titre = $row->titre;
	    $d->suffixe = end(explode('.', $row->fichier['name']));
	    $d->save();
	    $d->storeFile($if->getTempFilename());
	  }

	  $pj = new PieceJointe;
	  $pj->activite = $a->id;
	  $pj->document = $d->id;
	  $pj->save();
	}

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
    if ($a->isFuture())
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
    $m->addString('description', 'Description', $a->description);

    $enum = array(null => 'Nouveau document');
    foreach ($explicites->rewind()->current()->findDocuments() as $doc)
      $enum[$doc->id] = $doc->titre;
    $t = $m->addTable('documents', "Pièces-jointes",
		      array('document' => array('Enum', "Document", $enum),
			    'fichier' => array('File', "Fichier"),
			    'titre' => array('String', "Titre"),
			    'origin' => array('Integer')),
		      false);
    foreach ($a->findPiecesJointes() as $pj) {
      $doc = $pj->findParentDocuments();
      $titre = $doc->countLiaisons() > 1 ? null : $doc->titre;
      $t->addRow($pj->document, null, $titre, $pj->id);
    }
    $t->addRow();

    $m->addNewSubmission('enregistrer', 'Enregistrer');

    if ($m->validate()) {
      $t = new Activites;
      $tu = new Unites;
      $tpj = new PiecesJointes;
      $td = new Documents;

      $unites = call_user_func_array(array($tu, 'find'), (array) $m->unites);

      $db = $a->getTable()->getAdapter();
      $db->beginTransaction();
      try {
	$champs = array('debut', 'fin', 'lieu');
	foreach($champs as $champ)
	  $a->$champ = $m->get($champ);

	$a->updateUnites($unites);
	$a->intitule = $m->intitule;
	$a->slug = $t->createSlug($a->getIntituleComplet(), $a->slug);
	$a->save();

	$old = $a->findPiecesJointes();
	$new = array();
	/* création et mise à jour de pièce jointe */
	foreach($m->getInstance('documents') as $row) {
	  $d = null;
	  if ($row->document)
	    $d = $td->findOne($row->document);

	  if ($row->origin) {
	    $pj = $tpj->findOne($row->origin);
	    if (!$d)
	      $d = $pj->findParentDocuments();
	  }
	  else {
	    $pj = new PieceJointe;
	    $pj->activite = $a->id;
	    if (!$d)
	      $d = new Document;
	  }

	  /* On ne met à jour que les pièces jointes exclusives */
	  if (!$row->document) {
	    $d->slug = $d->getTable()->createSlug($row->titre);
	    $d->titre = $row->titre;

	    $if = $row->getChild('fichier');
	    if ($if->isUploaded()) {
	      $d->suffixe = end(explode('.', $row->fichier['name']));
	      $d->storeFile($if->getTempFilename());
	    }
	    elseif (!$row->origin)
	      continue; /* ligne vide */

	    $d->save();
	  }

	  $pj->document = $d->id;
	  $pj->save();
	  $new[] = $pj->id;
	}

	// Nettoyage des documents supprimés
	foreach ($old as $opj) {
	  if (in_array($opj->id, $new))
	    continue;

	  $opj->delete();
	}

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
		"Je confirme la destruction de toutes informations relative à cette activité.", false);
    $m->addNewSubmission('continuer', 'Continuer');

    if ($m->validate()) {
      if ($m->confirmer) {
	$db = $a->getTable()->getAdapter();
	$db->beginTransaction();
	try {
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

	$this->redirectSimple('calendrier', 'activites', null, array('unite' => $unite->slug));
      }
      else
	$this->redirectSimple('consulter', 'activites', null,
			      array('activite' => $a->slug));
    }
  }
}
