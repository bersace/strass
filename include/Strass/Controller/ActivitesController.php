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
		$i = Zend_Registry::get('user');
		if (!$i)
			throw new Strass_Controller_Action_Exception_Notice("Vous devez être identifié pour voir ".
									   "le calendrier des activités.");

		$u = current($i->getUnites());

		if ($u)
			$this->redirectUrl(array('action' => 'calendrier',
						 'unite' => $u->id));
		else
			throw new Strass_Controller_Action_Exception_Notice("Vous n'appartenez à aucune ".
									   "unité, impossible de vous ".
									   "présenter vos activités !");
	}

	/* Afficher les activités d'une unité pour une année. On affiche aussi un
	 liens vers chacune des années où l'unité a participé à des activités. */
	function calendrierAction()
	{
		$u = $this->_helper->Unite();
		$annee = $this->_helper->Annee();
		$future = $annee >= date('Y', time()-243*24*60*60);

		$this->metas(array('DC.Title' => 'Calendrier '.$annee,
				   'DC.Title.alternative' => 'Calendrier '.$annee.
				   ' – '.wtk_ucfirst($u->getFullname())));

		// restreindre l'accès aux calendrier futur.
		if ($future)
			$this->assert(null, $u, 'calendrier',
				      "Vous n'avez pas le droit de voir le calendrier de cette unité.");

		$this->view->model = new Strass_Pages_Model_Calendrier($u, $annee);
		$this->view->unite = $u;
		$this->view->annee = $annee;

		$this->actions->append("Nouvelle activité",
				       array('action' => 'prevoir',
					     'unite' => null),
				       array(Zend_Registry::get('user'), $u));

		$this->formats('ics');
	}

	/* prévoir une nouvelle activité pour une ou plusieurs unités */
	function prevoirAction()
	{
		$individu = Zend_Registry::get('user');
		if (!$individu)
			throw new Strass_Controller_Action_Exception_Notice("Vous devez être inscrit et identifié ".
									   "pour prévoir une nouvelle activité");
		$this->view->model = $m = new Wtk_Form_Model('prevoir');

		$this->metas(array('DC.Title' => 'Prévoir une nouvelle activité'));

		$unites = $this->getUnitesProgrammable();
		$annee = $this->_getParam('annee');

		if (count($unites) == 1) {
		  $uid = array_shift(array_keys($unites));
		  $unite = $this->_helper->Unite($uid);
		  
		  $urlOptions = array('controller'=> 'activites',
				      'action'	=> 'calendrier',
				      'unite'	=> $unite->id);
		  $this->branche->append('Calendrier',
					 $urlOptions,
					 array(),
					 true);
		  
		  if ($annee) {
		    $urlOptions = array('controller'=> 'activites',
					'action'	=> 'calendrier',
					'unite'	=> $unite->id,
					'annee' => $annee);
		    $this->branche->append($urlOptions['annee'],
					   $urlOptions,
					   array(),
					   true);
		  }
		}

		// on ne décale pas en arrière afin de réserver pour
		// la date actuel si possible.
		$annee = $annee ? $annee : date('Y');

		$m->addEnum('unites', 'Unités participantes',
				key($unites), $unites, true);        // multiple
		$m->addDate('debut', 'Début',
				$annee.date('-m-d').' 14:30',
				'%Y-%m-%d %H:%M');
		$m->addDate('fin', 'Fin',
				$annee.date('-m-d', time()+60*60*24).'17:00',
				'%Y-%m-%d %H:%M');
		$m->addString('intitule', 'Intitulé explicite', "");
		$m->addString('lieu', 'Lieu');

		// attacher
		$td = new Documents;
		$docs = $td->fetchAll();
		$enum = array('NULL' => 'Aucun');
		foreach($docs as $doc)
			$enum[$doc->id] = $doc->titre;
		$ta = $m->addTable('attacher', "Attacher",
				  array('document'	=> array('Enum', null, $enum)));
		$ta->addRow();


		// envoyer
		$tn = $m->addTable('documents', "Pièces-jointe",
				  array('fichier' 	=> array('File', "Fichier"),
					'titre'	=> array('String', "Titre")));
		$tn->addRow();
		$m->addBool('prevoir', "J'ai d'autres activités à prévoir", true);
		$m->addNewSubmission('ajouter', 'Ajouter');
		$m->addConstraint(new Wtk_Form_Model_Constraint_Required($m->getInstance('unites')));

		if ($m->validate()) {
			$db = Zend_Registry::get('db');
			$db->beginTransaction();
			try {
				$tu = new Unites();
				$data = $m->get();
				$unites = $data['unites'];
				$apporter = $data['apporter'];
				$keys = array('debut', 'fin', 'lieu', 'cotisation', 'depart', 'retour','message');
				$tuple = array();
				foreach($keys as $k)
					$tuple[$k] = $m->$k;

				// Sélectionner les sous unités des unités sélectionné à l'année de l'activité
				$annee = intval(date('Y', strtotime($m->get('debut')) - 243 * 24 * 60 * 60));
				$participantes = $tu->getIdSousUnites((array) $unites, $annee);


				// génération de l'intitulé
				$unites = $tu->find(array_values($participantes));
				$intitule = $m->get('intitule');
				$tuple['intitule'] = $intitule;
				// on génère l'intitulé pour d'id
				if (!$intitule)
				  $intitule = Activite::generateIntitule($tuple, $unites, false);
				$intitule .= Activite::generateDate($intitule,
								    Activite::findType($tuple['debut'],
										       $tuple['fin']),
								    strtotime($tuple['debut']),
								    strtotime($tuple['fin']));
				$tuple['id'] = $id = wtk_strtoid($tuple['intitule']);

				// enregistrement de l'activité
				$activites = new Activites();
				$id = $activites->insert($tuple);
				$a = $activites->find($id)->current();

				$this->updateParticipations($a, $participantes);

				// Stocker les item à apporter
				$this->updateApports($a, $apporter);


				// Pièces-jointes
				$tda = new DocsActivite;
				// attacher existants
				foreach($ta as $row) {
					if (!$row->document)
						continue;

					$data = array('document' => $row->document,
						      'activite' => $a->id);
					$tda->insert($data);
				}

				// ajouter nouveau
				$td = new Documents;
				foreach($tn as $row) {
					if (!$row->titre)
						continue;

					$i = $row->getChild('fichier');
					$data = array('id' 	=> wtk_strtoid($row->titre),
						      'titre'	=> $row->titre,
						      'suffixe'	=> strtolower(end(explode('.', $row->fichier['name']))),
						      'date'	=> strftime('%Y-%m-%d'),
						      'type_mime'=> $i->getMimeType());
					$k = $td->insert($data);
					$doc = $td->find($k)->current();
					$fichier = $doc->getFichier();
					if (!move_uploaded_file($i->getTempFilename(), $fichier)) {
						throw new Zend_Controller_Exception
							("Impossible de copier le fichier !");
					}

					$data = array('document' => $doc->id,
						      'activite' => $a->id);
					$tda->insert($data);
				}

				$this->_helper->Log("Nouvelle activité", array_merge(array($a), $unites),
						    $this->_helper->Url->url(array('action' => 'consulter',
										   'activite' => $a->id)),
						    (string) $a);

				$db->commit();
				if ($m->get('prevoir')) {
					$this->redirectSimple('prevoir', null, null, null, false);
				}
				else {
					$this->redirectSimple('consulter', null, null,
							      array('activite' => $id));
				}
			}
			catch(Exception $e) {
				$db->rollBack();
				throw $e;
			}
		}
	}

	function consulterAction()
	{
		$this->view->activite = $a = $this->_helper->Activite();
		$this->assert(null, $a, 'consulter',
			      "Vous n'avez pas le droit de consulter les détails de cette activité");

		$this->metas(array('DC.Title' => wtk_ucfirst($a->getIntitule())));

		$this->view->documents = $a->findDocsActivite();
		$i = Zend_Registry::get('user');

		if (!$a->isFuture()) {
			$this->connexes->append('Photos',
						array('controller' => 'photos'));

			$this->actions->append("Enregistrer la progression",
					       array('controller' => 'unites',
						     'action' => 'progression',
						     'activite' => $a->id),
					       // beuark
					       array(null, $a->getUnitesParticipantesExplicites()->current()));
			$this->actions->append("Envoyer une photo",
					       array('action' => 'envoyer',
						     'controller' => 'photos'),
					       array(null, $a, 'envoyer-photo'));
		}

		$this->actions->append('Modifier',
				       array('action' => 'modifier',
					     'activite' => $a->id),
				       array(null, $a));
		$this->actions->append('Annuler',
				       array('action' => 'annuler',
					     'activite' => $a->id),
				       array(null, $a));

		if ($a->getType() == 'camp') {
			$this->connexes->append('Participants',
						array('action'	=> 'participants'),
						array(null, $a, 'dossier'));

			$this->connexes->append('Maîtrise',
						array('action'	=> 'maitrise'),
						array(null, $a, 'dossier'));

		}

		$this->formats('ics');
	}

	function participantsAction()
	{
		$this->view->activite = $a = $this->_helper->Activite();
		$this->branche->append('Participants');

		$this->assert(null, $a, 'dossier',
			      "Vous n'avez pas le droit d'accéder au dossier de camp");
		$us = $a->getUnitesParticipantes();
		$apps = array();
		foreach ($us as $u) {
			if (!$u->abstraite)
				$apps[] = $u->getApps($a->getAnnee());
		}
		$this->view->apps = $apps;

		$this->connexes->append('Maîtrise',
					array('action'	=> 'maitrise'),
					array(null, $a, 'dossier'));
	}

	function maitriseAction()
	{
		$this->view->activite = $a = $this->_helper->Activite();
		$this->branche->append('Maîtrise');

		$this->assert(null, $a, 'dossier',
			      "Vous n'avez pas le droit d'accéder au dossier de camp");
		$us = $a->getUnitesParticipantesExplicites();
		$apps = array();
		foreach ($us as $u) {
			if (!$u->abstraite) {
				$ssapps = $u->getApps($a->getAnnee());
				foreach($ssapps as $app) {
					if ($this->assert($app->findParentIndividus(), $a, 'dossier'))
						$apps[] = $app;
				}
			}
		}
		// todo: aumônier
		$this->view->apps = array($apps);

		$this->connexes->append('Participants',
					array('action'	=> 'participants'),
					array(null, $a, 'dossier'));

	}

	function modifierAction()
	{
		$a = $this->_helper->Activite();
		$this->assert(null, $a, 'modifier',
			      "Vous n'avez pas le droit de modifier cettes activités");

		$this->metas(array('DC.Title' => 'Modifier '.$a->getIntitule()));

		$unites = $this->getUnitesProgrammable();

		$participantes = $a->findUnitesViaParticipations();
		$explicites =
			Activites::getUnitesParticipantesExplicites($participantes);

		$m = new Wtk_Form_Model('activite');
		$i = $m->addEnum('unites', 'Unités participantes', $explicites, $unites, true);    // multiple
		$m->addConstraint(new Wtk_Form_Model_Constraint_Required($i));

		$m->addString('intitule', 'Intitulé explicite', $a->intitule);
		$m->addString('lieu', 'Lieu', $a->lieu);
		$m->addDate('debut', 'Début', $a->debut, '%Y-%m-%d %H:%M');
		$m->addDate('fin', 'Fin', $a->fin, '%Y-%m-%d %H:%M');

		// pièces-jointes
		$g = $m->addGroup('documents', "Pièces-jointes");

		$td = new Documents;
		$docs = $td->fetchAll();
		$enum = array('NULL' => 'Aucun');
		foreach($docs as $doc)
			$enum[$doc->id] = $doc->titre;

		// existants - attaché
		$das = $a->findDocsActivite();
		if ($das->count()) {
			$t = $g->addTable('existants', "Actuels",
					  array('id'	=> array('String'),
						'titre'	=> array('String')),
					  false, false);

			foreach($das as $da) {
				$doc = $da->findParentDocuments();
				$t->addRow($doc->id, $doc->titre);
				unset($enum[$doc->id]);
			}
		}

		// existants - détaché
		$ta = $g->addTable('attacher', "Attacher",
				  array('document'	=> array('Enum', null, $enum)));
		$ta->addRow();

		// nouveau
		$tn = $g->addTable('envois', "Nouveaux",
				  array('fichier'	=> array('File'),
					'titre'	=> array('String')));
		$tn->addRow();

		$m->addNewSubmission('enregistrer', 'Enregistrer');

		// métier
		if ($m->validate()) {
			$db = $a->getTable()->getAdapter();
			$db->beginTransaction();

			try {
				$tu = new Unites();
				// mettre à jour l'activité elle-même.
				$champs = array('debut', 'fin', 'lieu');
				foreach($champs as $champ)
					$a->$champ = $m->get($champ);

				$data = $m->get();
				$unites = $tu->find($data['unites']);
				unset($data['unites']);
				$intitule = $m->get('intitule');
				$annee = intval(date('Y', strtotime($data['debut']) - 243 * 24 * 60 * 60));
				$a->intitule = $intitule;
				$a->id = wtk_strtoid($a->getIntitule());
				$a->save();

				// mettre à jour les participations
				$unites = $tu->getIdSousUnites((array) $m->get('unites'),
							       $a->getAnnee());
				$this->updateParticipations($a, $unites);

				// PIÈCES-JOINTES
				$td = new Documents;
				if (array_key_exists('existants', $data['documents'])) {
					// renomer
					$done = array();
					foreach($m->get('documents/existants') as $d) {
						// vider la case "titre" -> supprimer
						if (!$d['titre'])
							continue;

						$id = wtk_strtoid($d['titre']);
						$done[] = $d['id'];

						// pas besoin de renomer.
						if ($id == $d['id'])
							continue;

						// renommage
						$doc = $td->find($d['id'])->current();
						$doc->titre = $d['titre'];
						$doc->id = $id;
						$doc->save();
					}

					// supprimer
					foreach($das as $i => $da) {
						if (!in_array($da->document, $done))
							$da->findParentDocuments()->delete();
					}
				}

				$tda = new DocsActivite;
				// attacher existants
				foreach($ta as $row) {
					if (!$row->document)
						continue;

					$data = array('document' => $row->document,
						      'activite' => $a->id);
					$tda->insert($data);
				}

				// attacher nouveaux
				foreach($tn as $row) {
					if (!$row->titre)
						continue;

					$i = $row->getChild('fichier');
					$data = array('id' 	=> wtk_strtoid($row->titre),
						      'titre'	=> $row->titre,
						      'suffixe'	=> strtolower(end(explode('.', $row->fichier['name']))),
						      'date'	=> strftime('%Y-%m-%d'),
						      'type_mime'=> $i->getMimeType());
					$k = $td->insert($data);
					$doc = $td->find($k)->current();
					$fichier = $doc->getFichier();
					if (!move_uploaded_file($i->getTempFilename(), $fichier)) {
						throw new Zend_Controller_Exception
							("Impossible de copier le fichier !");
					}
					$data = array('document' => $doc->id,
						      'activite' => $a->id);
					$tda->insert($data);
				}

				$this->_helper->Log("Activité mise-à-jour", array($a),
						    $this->_helper->Url('consulter', 'activites', null,
									array('activite' => $a->id)),
						    (string) $a);

				$db->commit();

				$this->redirectSimple('consulter', null, null,
						      array('activite' => $a->id));
			}
			catch(Exception $e) {
				$db->rollBack();
				throw $e;
			}
		}

		$upe = $a->getUnitesParticipantesExplicites();
		if ($upe->count() == 1) {
			$u = $upe->current();
			$this->connexes->append('Calendrier',
						array('action' => 'calendrier',
						      'unite' => $u->id,
						      'annee' => $a->getAnnee()),
						array(null, $u));
		}

		// vue
		$this->view->activite = $a;
		$this->view->model = $m;
	}


	function annulerAction()
	{
		$a = $this->_helper->Activite();
		$this->assert(Zend_Registry::get('user'), $a, 'annuler',
			      "Vous n'avez pas le droit d'annuler cette activités");

		$this->metas(array('DC.Title' => 'Annuler '.$a->getIntitule()));


		$m = new Wtk_Form_Model('annuler');
		$m->addBool('confirmer',
			    "Je confirme la destruction de toute informations relative à l'activite ".
			    $a->intitule.".", false);
		$m->addNewSubmission('continuer', 'Continuer');

		if ($m->validate()) {
			if ($m->get('confirmer')) {
				$db = $a->getTable()->getAdapter();
				$db->beginTransaction();
				try {
					// desctruction des documents
					// liés *uniquement* à cette
					// activité.
					$das = $a->findDocsActivite();
					foreach($das as $da) {
						$doc = $da->findParentDocuments();
						if ($doc->countLiaisons() == 1)
							$doc->delete();
					}
					// destruction de l'activité.
					$unite = $a->getUnitesParticipantesExplicites()->current();
					$intitule = (string) $a;
					$a->delete();
					$this->_helper->Log("Activité annulé", array(),
							    $this->_helper->url->Url(array('action' => 'calendrier',
											   'unite' => $unite->id)),
							    $intitule);
							    
					$db->commit();
					$this->redirectSimple('index', 'activites');
				}
				catch(Exception $e) {
					$db->rollBack();
					throw $e;
				}
			}
			else {
				$this->redirectSimple('consulter', 'activites', null,
						      array('activite' => $a->id));
			}
		}

		$this->branche->append(ucfirst($a->intitule),
				       array('action' => 'consulter'));

		$this->view->activite = $a;
		$this->view->model = $m;
	}


	function getParticipation($reset = true)
	{
		$u = $this->_helper->Unite();
		$a = $this->_helper->Activite(null, true, $reset);
		$ps = new Participations();
		$p = $a && $u ? $ps->find($a->id, $u->id)->current() : null;
		if (!$p && $throw)
			throw new Strass_Controller_Action_Exception_Notice(wtk_ucfirst($u->getFullname()).	
									   " ne participe pas à l'activité ".
									   $a->getIntitule());
		return array($u, $p, $a);
	}

	// HELPER
	function getUnitesProgrammable($assert = TRUE) {
		$unites = array();
		$individu = Zend_Registry::get('user');
		if (!$this->assert($individu)) {
			// Sélectionner les unité où l'individu est ou a été inscrit
			// et dont il a le droit de prévoir une activité.
			$us = $individu->getUnites(false, true);
			foreach($us as $u)
				if ($this->assert($individu, $u, 'prevoir-activite'))
					$unites[$u->id] = wtk_ucfirst($u->getFullname());
		}
		else {
			// Sélectionner toute les unites !
			$table = new Unites();
			$rows = $table->fetchAll();
			foreach($rows as $row)
				$unites[$row->id] = wtk_ucfirst($row->getFullname());
		}

		if (!count($unites) && $assert) {
			throw new Strass_Controller_Action_Exception
				("Vous n'avez le droit de prévoir d'activité pour aucune unités !");
		}
		return $unites;
	}

	function updateParticipations($activite, $participantes)
	{
		$participations = new Participations();
		$table = new Unites();
		$rows = $table->fetchAll();

		// boucler sur *toutes* les unités existantes pour ajout ou
		// suppression de la participation.
		foreach($rows as $unite) {
			$p = $participations->find($activite->id, $unite->id)->current();
			if (in_array($unite->id, $participantes)) {
				// ajouter un unités nouvellement participante
				if (!$p)
					$id = $participations->insert(array ('activite' => $activite->id,
									     'unite' => $unite->id));
			}
			// supprimer une unité anciennement participante
			else if ($p)
				$p->delete();
		}
	}
}
