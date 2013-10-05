<?php

require_once 'Image/Transform.php';
require_once 'Strass/Activites.php';

class PhotosController extends Strass_Controller_Action
{
	protected $_titreBranche = 'Photos';

	function indexAction()
	{
		$activites = new Activites();
		$this->view->annee = $annee = $this->_helper->Annee();
		$this->metas(array('DC.Title' => 'Albums photos '.$annee,
				   'DC.Subject' => 'photos,albums,'.$annee));

		// liste des activités ayants des photos cette $annee.
		$db = $activites->getAdapter();
		$select = $db->select()
			->distinct()
			->from('activites')
			->join('photos', 'photos.activite = activites.id', array())
			->where("activites.debut > ?", $this->_helper->Annee->dateDebut($annee))
			->where("activites.debut < ?", $this->_helper->Annee->dateFin($annee))
			->where("activites.debut < STRFTIME('%Y-%m-%d %H:%M', CURRENT_TIMESTAMP)")
			->order('fin');
            
		$page = $this->_helper->Page();

		if (in_array($this->_getParam('format'), array('rss', 'atom'))) {
			$tp = new Photos();
			$this->view->photos = $tp->fetchAll(null, 'date DESC', 50);
		}
		else {
			$this->view->activites = new Wtk_Pages_Model_Iterator($activites->fetchSelect($select),
									      30, $page);
		}


		// liste des années
		$select = $db->select()
			->distinct()
			->from('activites',
			       array('annee' => 'STRFTIME("%Y", debut, "-8 months")'))
			->join('photos',
			       'photos.activite = activites.id',
			       array())
			->order('fin');
        
		$this->view->annees = $annees = $select->query()->fetchAll();

		$this->actions->append("Envoyer une photo",
				       array('action' => 'envoyer',
					     'annee' => null));

		$this->formats('atom','rss');
	}

	function sansphotosAction()
	{
		$activites = new Activites();
		$this->metas(array('DC.Title' => 'Activités sans photos '));
		$db = $activites->getAdapter();
		$select = $db->select()
			->distinct()
			->from('activites')
			->where("activites.debut < STRFTIME('%Y-%m-%d %H:%M', CURRENT_TIMESTAMP)")
			->where($db->quoteInto('NOT EXISTS (?)',
					       new Zend_Db_Expr($db->select()
								->from('photos')
								->where('photos.activite = activites.id')
								->__toString())))
			->order('fin DESC');

		$this->view->activites = $activites->fetchSelect($select);
		$this->branche->append('Activités sans photos');
	}

	function consulterAction()
	{
		$this->view->activite = $a = $this->_helper->Activite();
		$this->metas(array('DC.Title' => 'Photos de '.$a->getIntitule(),
				   'DC.Subject' => 'photos'));
		$photos = new Photos();
		$s = $photos->select()->order('date');
		$this->view->photos = $a->findPhotos($s);

		$annee = $a->getAnnee();
		$this->branche->insert(-1,
				       $annee,
				       array('controller' => 'photos',
					     'action' => 'index',
					     'annee' => $annee), array(), true);

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
			$i = Zend_Registry::get('individu');
			if (!$i)
				throw new Knema_Controller_Action_Exception_Forbidden("Vous devez être identifé pour envoyer des photos.");

			$annee = $this->_helper->Annee(false);
			$debut = $annee ? $this->_helper->Annee->dateDebut($annee) : null;
			$fin = $annee ? $this->_helper->Annee->dateFin($annee) : null;
			$as = $this->_helper->Activite->pourIndividu(Zend_Registry::get('individu'), $debut, $fin);
			if (!$as)
				throw new Knema_Controller_Action_Exception_Forbidden("Vous ne pouvez envoyer de photos dans aucune activités.");
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
				$tr->load($tmp);

				$suffixe = '.jpeg';
				$prefixe = 'data/images/strass/photos/'.$activite->id.'/'.$data['id'];
				$fichier = $prefixe.$suffixe;

				list($w, $h) = $tr->getImageSize();

				// image
				if (!file_exists($dossier = dirname($prefixe)))
					mkdir($dossier, 0755, true);
				$max = 1280;
				$ratio = max($h/$max, $w/$max);
				$ratio = max($ratio, 1);
				$w /= $ratio;
				$h /= $ratio;
				$tr->resize(intval($w), intval($h));
				if (Pear::isError($e = @$tr->save($fichier, 'jpeg')))
					throw new Knema_Controller_Action_Exception_Internal(null,
											     "Impossible d'enregistrer le fichier ".$fichier." : ".
											     "« ".$e->getMessage()." »");
				$tr->free();

				// vignette
				$mini = $prefixe.'-vignette'.$suffixe;
				$tr->load($fichier);
				list($w, $h) = $tr->getImageSize();
				$hv = 128;
				$ratio = $h / $hv;
				$w /= $ratio;
				$tr->resize(intval($w), $hv);
				if (Pear::isError($e = @$tr->save($mini, 'jpeg')))
					throw new Knema_Controller_Action_Exception_Internal(null,
											     "Impossible d'enregistrer le fichier ".$mini." : ".
											     "« ".$e->getMessage()." »");
				$tr->free();

				$photos = new Photos();
				$key = $photos->insert($data);

				if ($m->get('commentaire')) {
					$tc = new Commentaires;
					$data = array('activite' => $activite->id,
						      'photo'	=> $key['id'],
						      'individu' => Zend_Registry::get('individu')->id,
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
		list($a, $photo) = $this->_helper->Photo();
		$s = $photo->getTable()->select()->order('date');
		$ps = $a->findPhotos($s);
		$data = array();
		foreach($ps as $p)
			$data[$p->id] = $p;

		$m = new Wtk_Pages_Model_Assoc($data, $photo->id);

		$this->metas(array('DC.Title' => $photo->titre,
				   'DC.Subject' => 'photo',
				   'DC.Date.created' => $photo->date));
		$this->connexes->append("Revenir à l'album",
					array('action' => 'consulter',
					      'photo' => null));

		// cherche le commentaire de l'individu
		if ($i = Zend_Registry::get('individu')) {
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
			$as = $this->_helper->Activite->pourIndividu(Zend_Registry::get('individu'), $debut, $fin);
			if (!$as)
				throw new Knema_Controller_Action_Exception_Forbidden("Vous ne pouvez envoyer de photos dans aucune activités.");
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

		$i = Zend_Registry::get('individu');
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

