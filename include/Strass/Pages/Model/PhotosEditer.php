<?php

class Strass_Pages_Model_PhotosEditer extends Strass_Pages_Model_Historique
{
  function __construct($controller, $unite, $annee, $photo)
  {
    parent::__construct($unite, $annee);
    $this->controller = $controller;
    $this->photo = $photo;
  }

  function fetch($annee = NULL)
  {
    $ta = new Activites;
    $as = $ta->findByAnnee($annee);

    $activite = null;
    if ($as->count() == 1) {
      $activite = $as->current();
      $this->controller->_helper->Album->setBranche($activite);
    }

    $m = new Wtk_Form_Model('editer');
    $i = $m->addString('titre', 'Titre', $this->photo->titre);
    $m->addConstraintRequired($i);

    $enum = array();
    foreach($as as $a)
      if ($this->controller->assert(null, $a, 'editer-photo'))
	$enum[$a->id] = $a->getIntituleComplet();

    if ($enum)
      $m->addEnum('activite', "Activité", $this->photo->activite, $enum);
    $m->addFile('photo', "Photo");
    $m->addBool('promouvoir', "Promouvoir en page d'accueil", $this->photo->promotion);
    $m->addNewSubmission('enregistrer', "Enregistrer");

    if ($m->validate()) {
      $t = $this->photo->getTable();
      $db = $t->getAdapter();
      $db->beginTransaction();
      try {
	$i = $m->getInstance('photo');
	if ($i->isUploaded()) {
	  $tmp = $i->getTempFilename();
	  $this->photo->storeFile($tmp);
	}

	try {
	  $this->photo->activite = $m->activite;
	}
	catch (Exception $e) {}

	$this->photo->titre = $m->titre;
	$this->photo->slug = $t->createSlug($m->titre, $this->photo->slug);
	$this->photo->promotion = (int) $m->promouvoir;
	$this->photo->save();

	$this->controller->_helper->Flash->info("Photo éditée");
	$this->controller->logger->info("Photo éditée",
			    $this->controller->_helper->Url('voir', null, null,
							    array('photo' => $this->photo->slug)));

	$db->commit();
      }
      catch(Exception $e) {
	$db->rollBack();
	throw $e;
      }

      $this->controller->redirectSimple('voir', null, null, array('photo' => $this->photo->slug));
    }

    return array('unite' => $this->unite,
		 'annee' => $annee,
		 'form_model' => $m,
		 'activite' => $activite,
		 'photo' => $this->photo,
		 );
  }
}
