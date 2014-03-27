<?php

require_once 'Strass/Photos.php';

class PhotosController extends Strass_Controller_Action
{
  function indexAction()
  {
    $this->view->unite = $this->_helper->Unite();
    $this->view->model = new Strass_Pages_Model_Photos($this->view->unite, $this->_helper->Annee());
    $this->_helper->Annee->setBranche($annee = $this->view->model->current);
    $this->metas(array('DC.Title' => 'Albums photos '.$annee,
		       'DC.Subject' => 'albums,photos,'.$annee));

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
				  'action'  => 'consulter',
				  'activite' => $a->slug,
				  'album' => null));
    $this->actions->append("Éditer la chaîne",
			    array('controller' => 'activites',
				  'action'  => 'editer',
				  'activite' => $a->slug,
				  'album' => null));
    $this->actions->append("Envoyer une photo",
			   array('action' => 'envoyer'),
			   array(null, $a,'envoyer-photo'));
  }

  /* On peut envoyer une photo pour une année, une activité ou une
     photo existante */
  function envoyerAction()
  {
    $this->metas(array('DC.Title' => "Envoyer une photo",
		       'DC.Subject' => 'envoyer,photos'));

    $this->connexes->append("Nouvelle activité",
			    array('controller' => 'activites',
				  'action'  => 'prevoir'));

    $ta = new Activites;

    $this->view->photo = $p = $photo = $this->_helper->Photo(false);
    if ($p) {
      $this->view->activite = $activite = $p->findParentActivites();
      $this->view->unite = $unite = $activite->findUnitesParticipantesExplicites()->current();
      $annee = $this->_helper->Annee(false);
      $annee = $annee ? $annee : $activite->getAnnee();
    }
    else if ($activite = $this->_helper->Album(false)) {
      $this->view->activite = $activite;
      $this->view->unite = $unite = $activite->findUnitesParticipantesExplicites()->current();
      $annee = $activite->getAnnee();
    }
    else {
      $this->view->unite = $unite = $this->_helper->Unite();
      $annee = $this->_helper->Annee();
      $activite = null;
    }

    if (!$ta->countRows())
      throw new Strass_Controller_Action_Exception_Notice("Aucune activité enregistrée");

    $this->view->model = new Strass_Pages_Model_PhotosEnvoyer($this, $unite, $annee, $activite, $photo);
  }

  function voirAction()
  {
    $this->view->photo = $photo = $this->_helper->Photo();
    $this->view->activite = $a = $photo->findParentActivites();
    $this->metas(array('DC.Title' => $photo->titre,
		       'DC.Subject' => 'photo',
		       'DC.Date.created' => $photo->date));

    $this->actions->append("Éditer",
			   array('action' => 'envoyer'),
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
