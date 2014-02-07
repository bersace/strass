<?php

require_once 'Image/Transform.php';
require_once 'Strass/Activites.php';

class PhotosController extends Strass_Controller_Action
{
  function indexAction()
  {
    $this->view->unite = $this->_helper->Unite();
    $annee = $this->_helper->Annee();

    $this->metas(array('DC.Title' => 'Albums photos '.$annee,
		       'DC.Subject' => 'albums,photos,'.$annee));

    $this->view->model = new Strass_Pages_Model_Photos($this->view->unite, $annee);

    $this->actions->append("Envoyer une photo",
			   array('action' => 'envoyer',
				 'annee' => null));
  }

  function consulterAction()
  {
    $this->view->activite = $a = $this->_helper->Album();
    $this->metas(array('DC.Title' => $a->getIntitule(),
		       'DC.Subject' => 'photos'));
    $photos = new Photos();
    $s = $photos->select()->order('date');
    $this->view->photos = $a->findPhotos($s);

    $this->connexes->append("Chaîne",
			    array('controller' => 'activites',
				  'action'  => 'consulter'));
    $this->actions->append("Envoyer une photo",
			   array('action' => 'envoyer'),
			   array(null, $a,'envoyer-photo'));
  }

  function envoyerAction()
  {
    $ta = new Activites;
    $a = $activite = $this->_helper->Activite(null, false);

    $enum = array();
    if (!$activite) {
      $i = Zend_Registry::get('user');
      if (!$i)
	throw new Strass_Controller_Action_Exception_Forbidden("Vous devez être identifé pour envoyer des photos.");

      $annee = $this->_helper->Annee(false);
      $debut = $annee ? $this->_helper->Annee->dateDebut($annee) : null;
      $fin = $annee ? $this->_helper->Annee->dateFin($annee) : null;
      $as = $this->_helper->Activite->pourIndividu(Zend_Registry::get('individu'), $debut, $fin);
      if (!$as)
	throw new Strass_Controller_Action_Exception_Forbidden("Vous ne pouvez envoyer de photos dans aucune activités.");
      foreach($as as $a)
	if ($this->assert(null, $a, 'envoyer-photo'))
	  $enum[$a->id] = wtk_ucfirst($a->getIntitule());
    }
    else {
      $this->assert(null, $activite, 'envoyer-photo',
		    "Vous n'avez pas le droit d'envoyer de photos de l'activité ".
		    $a.". ");
      $enum[$a->id] = wtk_ucfirst($activite->getIntitule());
    }

    $this->view->activite = $activite ? $activite->getIntitule() : current($enum);

    $this->metas(array('DC.Title' => "Envoyer une photo",
		       'DC.Subject' => 'envoyer,photos'));
    $this->view->model = $m = new Wtk_Form_Model('envoyer');
    $i = $m->addString('titre', 'Titre');
    $m->addConstraintRequired($i);
    $m->addEnum('activite', "Activité", key($enum), $enum);
    $m->addFile('photo', "Photo");
    $m->addString('commentaire', 'Votre commentaire');
    $m->addBool('envoyer', "J'ai d'autres photos à envoyer", true);
    $m->addNewSubmission('envoyer', "Envoyer");


    if ($m->validate()) {
      $db = $ta->getAdapter();
      $db->beginTransaction();

      try {
	$data = $m->get();
	unset($data['photo']);

	$activite = $ta->find($data['activite'])->current();
	// id
	$data['id'] = wtk_strtoid($data['titre']);
	if (!$data['id']) {
	  throw new Exception("Le titre n'est pas suffisant");
	}

	$action = $data['envoyer'] ? 'envoyer' : 'consulter';
	unset($data['envoyer']);
	unset($data['commentaire']);

	$tmp = $m->getInstance('photo')->getTempFilename();

	// date
	$exif = exif_read_data($tmp);
	if (array_key_exists('DateTimeOriginal', $exif)) {
	  preg_match("`(\d{4})[:-](\d{2})[:-](\d{2}) (\d{2}):(\d{2}):(\d{2})`",
		     $exif['DateTimeOriginal'], $match);
	  $date = $match[1].'-'.$match[2].'-'.$match[3].' '.
	    $match[4].':'.$match[5].':'.$match[6];
	}
	else
	  $date = null;

	if (!$date || $date < $activite->debut || $activite->fin < $activite->debut)
	  $date = $activite->fin;
	$data['date'] =  $date;

	// fichier
	$tr = Image_Transform::factory('GD');
	if (PEAR::isError($tr))
	  Orror::kill($tr);

	$tr->load($tmp);

	$dossier = $activite->getDossierPhoto();
	$suffixe = '.jpeg';
	$fichier = $dossier.$data['id'].$suffixe;

	list($w, $h) = $tr->getImageSize();

	// image
	if (!file_exists($dossier))
	  mkdir($dossier, 0755, true);
	$max = 1280;
	$ratio = max($h/$max, $w/$max);
	$ratio = max($ratio, 1);
	$w /= $ratio;
	$h /= $ratio;
	$tr->resize(intval($w), intval($h));
	if (Pear::isError($e = @$tr->save($fichier, 'jpeg')))
	  throw new Strass_Controller_Action_Exception_Internal(null,
								"Impossible d'enregistrer le fichier ".$fichier." : ".
								"« ".$e->getMessage()." »");
	$tr->free();

	// vignette
	$mini = $dossier.$data['id'].'-vignette'.$suffixe;
	$tr->load($fichier);
	list($w, $h) = $tr->getImageSize();
	$hv = 256;
	$ratio = $h / $hv;
	$w /= $ratio;
	$tr->resize(intval($w), $hv);
	if (Pear::isError($e = @$tr->save($mini, 'jpeg')))
	  throw new Strass_Controller_Action_Exception_Internal(null,
								"Impossible d'enregistrer le fichier ".$mini." : ".
								"« ".$e->getMessage()." »");
	$tr->free();

	$photos = new Photos();
	$key = $photos->insert($data);

	if ($m->get('commentaire')) {
	  $tc = new Commentaires;
	  $data = array('activite' => $activite->id,
			'photo'	=> $key['id'],
			'individu' => Zend_Registry::get('user')->id,
			'commentaire' => $m->get('commentaire'),
			'date' => strftime('%Y-%m-%d %T'));
	  $tc->insert($data);
	}

	$this->_helper->Log("Photo envoyée", array($activite),
			    $this->_helper->Url->url(array('action' => 'voir',
							   'activite' => $activite->id,
							   'photo' => $key['id'])),
			    $activite." – ".$m->titre);

	$db->commit();
	$this->redirectSimple($action, 'photos', null,
			      array('activite' => $activite->id));
      }
      catch(Exception $e) {
	$db->rollBack();
	throw $e;
      }
    }

    if ($activite) {
      $this->actions->append("Revenir à l'album",
			     array('action' => 'consulter',
				   'photo' => null));
    }
    $this->actions->append("Nouvelle activité",
			   array('controller' => 'activites',
				 'action' => 'prevoir'));
  }

  function voirAction()
  {
    $photo = $this->_helper->Photo();
    $s = $photo->getTable()->select()->order('date');
    $a = $photo->findParentActivites();
    $ps = $a->findPhotos($s);
    $data = array();
    foreach($ps as $p)
      $data[$p->slug] = $p;

    $m = new Wtk_Pages_Model_Assoc($data, $photo->slug);

    $this->metas(array('DC.Title' => wtk_ucfirst($photo->titre),
		       'DC.Subject' => 'photo',
		       'DC.Date.created' => $photo->date));
    $this->connexes->append("Revenir à l'album",
			    array('action' => 'consulter',
				  'photo' => null));

    // cherche le commentaire de l'individu
    if ($i = Zend_Registry::get('user')) {
      $s = $photo->getTable()->select();
      $s->join('individus',
	       'commentaire.individu = individu.id',
	       array())
	->where('individus.id = ?', $i->id);
      $c = $photo->findCommentaires($s)->current();
      $this->actions->append($c ? "Éditer votre commentaire" : "Commenter",
			     array('action' => 'commenter'),
			     array(null, $photo));
      $this->actions->append("Modifier",
			     array('action' => 'modifier',
				   'annee' => $a->getAnnee()),
			     array(null, $a));
      $this->actions->append("Supprimer",
			     array('action' => 'supprimer'),
			     array(null, $a));

    }
    $this->view->model = $m;
    $this->view->activite = $a;
    $this->view->photo = $photo;
    $this->view->commentaires = $photo->findCommentaires();
  }

  function modifierAction()
  {
    list($a, $p) = $this->_helper->Photo(true);

    $this->assert(null, $p, 'modifier',
		  "Vous n'avez pas le droit de modifier cette photo.");

    $this->metas(array('DC.Title' => "Modifier ".$p->titre,
		       'DC.Subject' => 'photo',
		       'DC.Date.created' => $p->date));

    $annee = $this->_helper->Annee(false);
    $debut = $annee ? $this->_helper->Annee->dateDebut($annee) : null;
    $fin = $annee ? $this->_helper->Annee->dateFin($annee) : null;
    $as = $this->_helper->Activite->pourIndividu(Zend_Registry::get('user'), $debut, $fin);
    if (!$as)
      throw new Strass_Controller_Action_Exception_Forbidden("Vous ne pouvez envoyer de photos dans aucune activités.");
    foreach($as as $a)
      if ($this->assert(null, $a, 'envoyer-photo'))
	$enum[$a->id] = wtk_ucfirst($a->getIntitule());

    $m = new Wtk_Form_Model('photo');
    $m->addString('titre', "Titre", $p->titre);
    $m->addEnum('activite', "Activité", $p->activite, $enum);
    $m->addNewSubmission('enregistrer', "Enregistrer");

    if ($m->validate()) {
      $db = $p->getTable()->getAdapter();
      $db->beginTransaction();
      try {
	$keys = array('titre', 'activite');
	foreach($keys as $k)
	  $p->$k = $m->get($k);
	$p->id = wtk_strtoid($p->titre);
	$p->save();
	$db->commit();
	$this->redirectSimple('voir', 'photos', null,
			      array('photo' => $p->id,
				    'activite' => $p->activite));
      }
      catch(Exception $e) {
	$db->rollBack();
	throw $e;
      }
    }

    $this->actions->append("Revenir à l'album",
			   array('action' => 'consulter',
				 'photo' => null));

    $this->view->model = $m;
    $this->view->activite = $a;
    $this->view->photo = $p;
  }

  function commenterAction()
  {
    list($a, $p) = $this->_helper->Photo();

    $i = Zend_Registry::get('user');
    $this->assert(null, $p, 'commenter',
		  "Vous n'avez pas le droit de commenter cette photos.");

    $tc = new Commentaires;
    $c = $tc->find($a->id, $p->id, $i->id)->current();

    $this->view->model = $m = new Wtk_Form_Model('commentaire');
    $in = $m->addString('commentaire', "Commentaire", $c ? $c->commentaire : null);
    if ($c)
      $s = $m->addBool('supprimer', "Supprimer le commentaire", false);

    $m->addNewSubmission('commenter', "Commenter");

    if ($m->validate()) {
      $db = $p->getTable()->getAdapter();
      $db->beginTransaction();
      try {
	if (!$c) {
	  // création à la volée.
	  $data = array('activite' => $a->id,
			'photo'	=> $p->id,
			'individu' => $i->id);
	  $k = $tc->insert($data);
	  $c = call_user_func_array(array($tc, 'find'), $k)->current();
	}

	if ($m->commentaire && !$m->supprimer) {
	  $c->commentaire = $m->commentaire;
	  $c->date	= strftime('%Y-%m-%d %T');
	  $c->save();
	  $this->_helper->Log("Commentaire modifié", array($a, $p, $i),
			      $this->_helper->Url->url(array('action' => 'voir')),
			      $a." – ".$p);
	}
	else {
	  // on supprime les commentaire vide
	  $c->delete();
	  $this->_helper->Log("Commentaire supprimé", array($a, $p, $i),
			      $this->_helper->Url('voir', 'photos', null,
						  array('activite'	=> $a->id,
							'photo'	=> $p->id)).'#photo',
			      (string)$p);
	}

	$db->commit();
      }
      catch(Exception $e) {
	$db->rollBack();
	throw $e;
      }

      $this->redirectSimple('voir', 'photos', null,
			    array('activite'	=> $a->id,
				  'photo'	=> $p->id)).'#photo';
    }

    $this->view->photo = $p;
  }

  function deplacerAction()
  {
    list($a, $p) = $this->_helper->Photo();
    $this->assert(null, $p, 'deplacer',
		  "Vous n'avez pas le droit de déplacer cette photo.");
    $this->metas(array('DC.Title' => "Déplacer ".$p->titre,
		       'DC.Subject' => 'photo',
		       'DC.Date.created' => $p->date));



  }

  function supprimerAction()
  {
    list($a, $p) = $this->_helper->Photo();

    $this->assert(null, $p, 'supprimer',
		  "Vous n'avez pas le droit de supprimer la photo ".$p->titre.".");

    $this->metas(array('DC.Title' => "Supprimer ".$p->titre,
		       'DC.Subject' => 'photo',
		       'DC.Date.created' => $p->date));

    $m = new Wtk_Form_Model('supprimer');
    $m->addBool('confirmer',
		"Je confirme la suppression de la photo ".$p->titre.".",
		false);
    $m->addNewSubmission('continuer', 'Continuer');

    if ($m->validate()) {
      if ($m->get('confirmer')) {
	$db = $p->getTable()->getAdapter();
	$db->beginTransaction();
	try {
	  $p->delete();
	  $this->_helper->Log("Photo supprimée", array($a),
			      $this->_helper->Url->url(array('action' => 'consulter',
							     'photo' => null)),
			      (string) $a);
	  $db->commit();
	  $this->redirectSimple('consulter', 'photos', null,
				array('activite' => $a->id));
	}
	catch(Exception $e) {
	  $db->rollBack();
	  throw $e;
	}
      }
      else {
	$this->redirectSimple('voir', 'photos', null,
			      array('activite' => $a->id,
				    'photo' => $p->id)).'#photo';
      }
    }

    $this->view->photo = $p;
    $this->view->model = $m;
  }
}
