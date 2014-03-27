<?php

class Strass_Pages_Model_PhotosEnvoyer extends Strass_Pages_Model_Historique
{
  function __construct($controller, $unite, $annee, $activite, $photo)
  {
    parent::__construct($unite, $annee);
    $this->controller = $controller;
    $this->activite = $activite;
    $this->photo = $photo;
  }

  function fetch($annee = NULL)
  {
    $as = $this->unite->findActivites($annee);

    $activite = null;
    if ($as->count() == 1) {
      $activite = $as->current();
      $this->controller->_helper->Album->setBranche($activite);
    }

    $m = new Wtk_Form_Model('envoyer');
    $i = $m->addString('titre', 'Titre', $this->photo ? $this->photo->titre : null);
    $m->addConstraintRequired($i);

    $enum = array();
    foreach($as as $a)
      if ($this->controller->assert(null, $a, 'envoyer-photo'))
	$enum[$a->id] = $a->getIntituleComplet();
    if ($as->count() && !$enum)
      throw new Strass_Controller_Action_Exception_Forbidden("Vous ne pouvez envoyer de photos ".
							     "dans aucune activité.");

    if ($this->activite)
      $default = $this->activite->id;
    else
      $default = key($enum);

    $m->addEnum('activite', "Activité", $default, $enum);
    $m->addFile('photo', "Photo");
    $m->addString('commentaire', 'Votre commentaire', $this->photo ? $this->photo->getDescription() : null);
    if (!$this->photo)
      $m->addBool('envoyer', "J'ai d'autres photos à envoyer", true);
    $m->addNewSubmission('envoyer', "Envoyer");

    if ($m->validate()) {
      if ($this->photo) {
	$p = $this->photo;
	$c = $p->findParentCommentaires();
      }
      else {
	$c = new Commentaire;
	$c->auteur = $individu->id;
	$p = new Photo;
      }
      $p->activite = $m->activite;
      $p->titre = $m->titre;
      $p->slug = $p->getTable()->createSlug($p->titre, $p->slug);

      try {
	$action = $m->envoyer ? 'envoyer' : 'consulter';
      }
      catch (Exception $e) {
	$action = 'consulter';
      }

      $db = $p->getTable()->getAdapter();
      $db->beginTransaction();
      try {
	$c->message = $m->commentaire;
	$c->save();

	$p->commentaires = $c->id;
	$p->save();

	$i = $m->getInstance('photo');
	if ($i->isUploaded()) {
	  $tmp = $i->getTempFilename();
	  $p->storeFile($tmp);
	}

	$this->controller->_helper->Flash->info("Photo envoyée");
	$this->controller->logger->info("Photo envoyée",
			    $this->controller->_helper->Url('voir', null, null,
							    array('photo' => $p->slug)));

	$db->commit();
      }
      catch(Exception $e) {
	$db->rollBack();
	throw $e;
      }

      $activite = $p->findParentActivites();
      $this->controller->redirectSimple($action, null, null, array('album' => $activite->slug));
    }

    return array('unite' => $this->unite,
		 'annee' => $annee,
		 'form_model' => $m,
		 'activite' => $activite,
		 'photo' => $this->photo,
		 );
  }
}
