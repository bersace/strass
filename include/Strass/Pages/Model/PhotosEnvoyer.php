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
    if (!$this->activite)
      return array('activites' => $this->unite->findActivites($annee));
    else
      $this->controller->assert(null, $this->activite, 'envoyer-photo',
				"Vous n'avez pas le droit d'envoyer de photo de ".
				$this->activite->getIntituleComplet().".");

    $m = new Wtk_Form_Model('envoyer');
    $i = $m->addString('titre', 'Titre');
    $m->addConstraintRequired($i);

    $m->addFile('photo', "Photo");
    $m->addString('commentaire', 'Votre commentaire');
    $m->addBool('envoyer', "J'ai d'autres photos à envoyer", true);
    $m->addNewSubmission('envoyer', "Envoyer");

    $t = new Photos;
    if ($m->validate()) {
      $p = new Photo;
      $p->titre = $m->titre;
      $p->slug = $t->createSlug(wtk_strtoid($m->titre));
      $p->activite = $this->activite->id;

      $action = $m->envoyer ? 'envoyer' : 'consulter';

      $c = new Commentaire;
      $c->auteur = $individu->id;
      $c->message = $m->commentaire;

      $db = $t->getAdapter();
      $db->beginTransaction();

      try {
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
			    $this->controller->_helper->Url('voir', 'photos', null,
							    array('photo' => $p->slug),
							    true));

	$db->commit();
      }
      catch(Exception $e) {
	$db->rollBack();
	throw $e;
      }

      $this->controller->redirectSimple($action, null, null, array('album' => $this->activite->slug));
    }

    $photos = $this->activite->findPhotos($t->select()->order('date'));
    return array('unite' => $this->unite,
		 'annee' => $annee,
		 'model' => $m,
		 'activite' => $this->activite,
		 'photos' => $photos,
		 );
  }
}
