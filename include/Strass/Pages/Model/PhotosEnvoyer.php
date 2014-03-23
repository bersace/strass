<?php

class Strass_Pages_Model_PhotosEnvoyer extends Strass_Pages_Model_Historique
{
  function __construct($controller, $unite, $annee, $activite)
  {
    parent::__construct($unite, $annee);
    $this->controller = $controller;
    $this->activite = $activite;
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
    $i = $m->addString('titre', 'Titre');
    $m->addConstraintRequired($i);

    $enum = array();
    foreach($as as $a)
      if ($this->controller->assert(null, $a, 'envoyer-photo'))
	$enum[$a->id] = $a->getIntituleComplet();
    if (!$enum)
      throw new Strass_Controller_Action_Exception_Forbidden("Vous ne pouvez envoyer de photos ".
							     "dans aucune activité.");

    if ($this->activite)
      $default = $this->activite->id;
    else
      $default = key($enum);

    $m->addEnum('activite', "Activité", $default, $enum);
    $m->addFile('photo', "Photo");
    $m->addString('commentaire', 'Votre commentaire');
    $m->addBool('envoyer', "J'ai d'autres photos à envoyer", true);
    $m->addNewSubmission('envoyer', "Envoyer");

    if ($m->validate()) {
      $ta = new Activites;
      $t = new Photos;
      $photo = $m->get();
      unset($photo['photo']);

      $activite = $ta->find($photo['activite'])->current();
      $photo['slug'] = $t->createSlug(wtk_strtoid($photo['titre']));

      $action = $photo['envoyer'] ? 'envoyer' : 'consulter';
      unset($photo['envoyer']);
      unset($photo['commentaire']);

      $db = $t->getAdapter();
      $db->beginTransaction();

      try {
	$tc = new Commentaires;
	$k = $tc->insert(array('auteur' => $individu->id,
			       'message' => $m->get('commentaire')));
	$photo['commentaires'] = $k;
	$k = $t->insert($photo);
	$photo = $t->findOne($k);

	$i = $m->getInstance('photo');
	if ($i->isUploaded()) {
	  $tmp = $i->getTempFilename();
	  $photo->storeFile($tmp);
	}

	$this->controller->_helper->Flash->info("Photo envoyée");
	$this->controller->logger->info("Photo envoyée",
			    $this->controller->_helper->Url('voir', null, null,
							    array('photo' => $photo->slug)));

	$db->commit();
      }
      catch(Exception $e) {
	$db->rollBack();
	throw $e;
      }

      $this->controller->redirectSimple($action, null, null, array('album' => $activite->slug));
    }

    return array('unite' => $this->unite,
		 'annee' => $annee,
		 'form_model' => $m,
		 'activite' => $activite,
		 );
  }
}
