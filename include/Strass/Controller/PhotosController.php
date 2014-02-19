<?php

require_once 'Strass/Photos.php';

class PhotosController extends Strass_Controller_Action
{
  function indexAction()
  {
    $this->view->unite = $this->_helper->Unite();
    $annee = $this->_helper->Annee();

    $this->metas(array('DC.Title' => 'Albums photos '.$annee,
		       'DC.Subject' => 'albums,photos,'.$annee));

    $this->view->model = new Strass_Pages_Model_Photos($this->view->unite, $annee);

    if (Zend_Registry::get('user')->isMember())
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
    $a = $activite = $this->_helper->Album(false);

    $individu = Zend_Registry::get('individu');
    if ($activite) {
      $this->view->activite = $activite;
      $as = array($activite);
    }
    else {
      $annee = $this->_helper->Annee(false);
      $as = $individu->findActivites($annee);

      if (count($as) == 1) {
	$this->view->activite = current($as);
	$this->_helper->Album->setBranche($as);
      }
    }

    $this->metas(array('DC.Title' => "Envoyer une photo",
		       'DC.Subject' => 'envoyer,photos'));

    $this->connexes->append("Nouvelle activité",
			    array('controller' => 'activites',
				  'action'  => 'prevoir'));

    if (!$ta->countRows())
      throw new Strass_Controller_Action_Exception_Notice("Aucune activité enregistrée");

    $this->view->model = $m = new Wtk_Form_Model('envoyer');
    $i = $m->addString('titre', 'Titre');
    $m->addConstraintRequired($i);

    $enum = array();
    foreach($as as $a)
      if ($this->assert(null, $a, 'envoyer-photo'))
	$enum[$a->id] = wtk_ucfirst($a->getIntitule());
    if (!$enum)
      throw new Strass_Controller_Action_Exception_Forbidden("Vous ne pouvez envoyer de photos ".
							     "dans aucune activité.");

    $m->addEnum('activite', "Activité", key($enum), $enum);
    $m->addFile('photo', "Photo");
    $m->addString('commentaire', 'Votre commentaire');
    $m->addBool('envoyer', "J'ai d'autres photos à envoyer", true);
    $m->addNewSubmission('envoyer', "Envoyer");

    if ($m->validate()) {
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

	$tmp = $m->getInstance('photo')->getTempFilename();
	$photo->storeFile($tmp);

	$this->_helper->Flash->info("Photo envoyée");
	$this->logger->info("Photo envoyée",
			    $this->_helper->Url('voir', null, null, array('photo' => $photo->slug)));

	$db->commit();
      }
      catch(Exception $e) {
	$db->rollBack();
	throw $e;
      }

      $this->redirectSimple($action, null, null, array('album' => $activite->slug));
    }
  }

  function voirAction()
  {
    $this->view->photo = $photo = $this->_helper->Photo();
    $this->view->activite = $a = $photo->findParentActivites();
    $this->metas(array('DC.Title' => wtk_ucfirst($photo->titre),
		       'DC.Subject' => 'photo',
		       'DC.Date.created' => $photo->date));

    $this->connexes->append("Revenir à l'album",
			    array('action' => 'consulter', 'photo' => null, 'album' => $a->slug));
    $this->actions->append("Éditer",
			   array('action' => 'editer'),
			   array(null, $photo));
    $this->actions->append("Supprimer",
			   array('action' => 'supprimer'),
			   array(null, $photo));

    $ps = $a->findPhotos($photo->getTable()->select()->order('date'));
    $data = array();
    foreach($ps as $p)
      $data[$p->slug] = $p;

    $this->view->model = $m = new Wtk_Pages_Model_Assoc($data, $photo->slug);

    $i = Zend_Registry::get('individu');
    if ($this->assert(null, $photo, 'commenter')) {
      /* Si l'utilisateur peut commenter mais ne l'a pas fait, lui
	 présenter le formulaire */
      try {
	$photo->findCommentaire($i);
      }
      catch (Strass_Db_Table_NotFound $e) {
	$this->view->com_model = $m = new Wtk_Form_Model('commentaire');
	$m->addString('message', "Message");
	$m->addNewSubmission('commenter', "Commenter");

	if ($m->validate()) {
	  $t = new Commentaires;
	  $tuple = array('parent' => $photo->commentaires,
			 'auteur' => $i->id,
			 'message' => $m->get('message'),
			 );
	  $db = $t->getAdapter();
	  $db->beginTransaction();
	  try {
	    $t->insert($tuple);
	    $this->logger->info("Photo commentée");
	    unset($this->view->com_model);
	    $db->commit();
	  }
	  catch (Exception $e) { $db->rollBack(); throw $e; }
	}
      }
    }

    /* Lister les commentaires après l'insertion  */
    $this->view->commentaires = $photo->findCommentaires();
  }

  function editerAction()
  {
    $this->view->photo = $p = $this->_helper->Photo();
    $this->view->album = $a = $p->findParentActivites();
    $this->metas(array('DC.Title' => "Éditer ".$p->titre,
		       'DC.Subject' => 'photo',
		       'DC.Date.created' => $p->date));
    $annee = $this->_helper->Annee(false);

    $this->connexes->append("Revenir à l'album",
			   array('action' => 'consulter', 'photo' => null, 'album' => $a->slug));

    $this->assert(null, $p, 'editer',
		  "Vous n'avez pas le droit de editer cette photo.");

    $individu = Zend_Registry::get('individu');
    $as = $individu->findActivites($annee);
    if (!$as)
      throw new Strass_Controller_Action_Exception_Forbidden("Vous ne pouvez envoyer de photos dans aucune activités.");

    $this->view->model = $m = new Wtk_Form_Model('photo');
    $enum = array();
    foreach($as as $a)
      if ($this->assert(null, $a, 'envoyer-photo'))
	$enum[$a->id] = wtk_ucfirst($a->getIntitule());

    $m->addEnum('activite', "Album", $p->activite, $enum);
    $m->addFile('photo', "Photo");
    $m->addString('titre', "Titre", $p->titre);

    $m->addNewSubmission('enregistrer', "Enregistrer");

    if ($m->validate()) {
      $t = $p->getTable();
      $keys = array('titre', 'activite');
      foreach($keys as $k)
	$p->$k = $m->get($k);
      $p->slug = $t->createSlug(wtk_strtoid($p->titre), $p->slug);

      $db = $t->getAdapter();
      $db->beginTransaction();
      try {
	if ($tmp = $m->getInstance('photo')->getTempFilename())
	  $p->storeFile($tmp);
	$p->save();
	$this->logger->info("Photo éditée",
			    $this->_helper->Url('voir', null, null, array('photo' => $p->slug)));
	$db->commit();
      }
      catch(Exception $e) {
	$db->rollBack();
	throw $e;
      }
      $this->redirectSimple('voir', null, null, array('photo' => $p->slug));
    }
  }

  function supprimerAction()
  {
    $this->view->photo = $p = $this->_helper->Photo();
    $a = $p->findParentActivites();

    $this->assert(null, $p, 'supprimer',
		  "Vous n'avez pas le droit de supprimer la photo ".$p->titre.".");

    $this->metas(array('DC.Title' => "Supprimer ".$p->titre,
		       'DC.Subject' => 'photo,image',
		       'DC.Date.created' => $p->date));

    $this->view->model = $m = new Wtk_Form_Model('supprimer');
    $m->addBool('confirmer', "Je confirme la suppression de cette photo.", false);
    $m->addNewSubmission('continuer', 'Continuer');

    if ($m->validate()) {
      if ($m->get('confirmer')) {
	$db = $p->getTable()->getAdapter();
	$db->beginTransaction();
	try {
	  $p->delete();
	  $this->logger->warn("Photo supprimée",
			      $this->_helper->Url('consulter', 'photos', null,
						  array('album' => $a->slug), true));
	  $db->commit();
	}
	catch(Exception $e) {
	  $db->rollBack();
	  throw $e;
	}

	$this->redirectSimple('consulter', null, null, array('album' => $a->slug, 'photo' => null));
      }
      else {
	$this->redirectSimple('voir');
      }
    }
  }
}
