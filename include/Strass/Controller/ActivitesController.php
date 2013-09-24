<?php

require_once 'Strass/Individus.php';
require_once 'Strass/Activites.php';
require_once 'Strass/Unites.php';
require_once 'Strass/Photos.php';

class ActivitesController extends Strass_Controller_Action
{
	// 	protected $_titreBranche = 'Activités';

	/**
	 * Par défaut on redirige automatiquement vers l'unité actuelle de l'individu
	 * sinon, vers les photos …
	 */
	function indexAction()
	{
		$i = Zend_Registry::get('individu');
		if (!$i)
			throw new Knema_Controller_Action_Exception_Notice("Vous devez être identifié pour voir ".
									   "le calendrier des activités.");

		$u = current($i->getUnites());

		if ($u)
			$this->redirectUrl(array('action' => 'calendrier',
						 'unite' => $u->id));
		else
			throw new Knema_Controller_Action_Exception_Notice("Vous n'appartenez à aucune ".
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


		$ta = new Activites();
		$db = $ta->getAdapter();
		$min = $this->_helper->Annee->dateDebut($annee).' 00:00';
		$max = $this->_helper->Annee->dateFin($annee).' 23:59';
		$select = $db->select()
			->from('activites')
			->join('participe',
			       'participe.activite = activites.id'.
			       ' AND '.
			       $db->quoteInto('participe.unite = ?', $u->id),
			       array())
			->where("debut >= ?", $min)
			->where("debut <= ?", $max)
			->where("fin >= ?", $min)
			->where("fin <= ?", $max)
			->order('activites.debut');
		$as = $ta->fetchSelect($select);


		/* sélectionner les années où l'unité a participé à des activités */
		$select = $db->select()
			->distinct()
			->from('activites',
			       array('annee' => 'STRFTIME("%Y", debut, "-8 months")'))
			->join('participe',
			       'participe.activite = activites.id'.
			       ' AND '.
			       $db->quoteInto('participe.unite = ?',$u->id),
			       array())
			->order('annee DESC');

		$this->view->annees = $db->query($select->__toString())->fetchAll();

		// CONNEXES
		$this->connexes->append(wtk_ucfirst($u->getFullname()),
					array('controller' => 'unites',
					      'action' => 'accueil'));
		$this->connexes->append("Rapports d'activités",
					array('controller' => 'activites',
					      'action' => 'rapports'));

		$this->actions->append("Nouvelle activité",
				       array('action' => 'prevoir',
					     'unite' => null),
				       array(Zend_Registry::get('individu'), $u));

		$this->view->activites = $as;
		$this->view->annee = $annee;
		$this->view->unite = $u;
		$this->view->future = $future;

		$this->formats('ics');
	}

	/* prévoir une nouvelle activité pour une ou plusieurs unités */
	function prevoirAction()
	{
		$individu = Zend_Registry::get('individu');
		if (!$individu)
			throw new Knema_Controller_Action_Exception_Notice("Vous devez être inscrit et identifié ".
									   "pour prévoir une nouvelle activité");
		$this->view->model = $m = new Wtk_Form_Model('prevoir');

		$this->metas(array('DC.Title' => 'Prévoir une nouvelle activité'));

		$unites = $this->getUnitesProgrammable();

		$annee = $this->_getParam('annee');
		$annee = $annee ? $annee : date('Y'); // on ne décale
						      // pas en
						      // arrière afin
						      // de réserver
						      // pour la date
						      // actuel si
						      // possible.

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
		$m->addString('depart', 'Départ', "au local");
		$m->addString('retour', 'Retour', "au local");

		$m->addInteger('cotisation', 'Cotisation', 0);
		$i = $m->addTable('apporter', "Apporter",
				  array('item' => array('String', "Apporter")));
		// un champ vide, au cas où le client ne supporte pas la
		// javascript, qu'il puisse ajouter au moins deux rubriques :)
		$i->addRow(array('item' => ""));

		$m->addString('message', 'Message complémentaire');

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
				$intitule = $intitule ? $intitule : Activite::generateIntitule($tuple, $unites, false);
				$tuple['intitule'] = $intitule.Activite::generateDate(Activite::findType($tuple['debut'], $tuple['fin']),
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
		$i = Zend_Registry::get('individu');

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

			if (!$i)
				return;

			// Sélectionner l'unité où l'individu à le
			// droit de rapporter dans le rapports d'activités.
			$db = Zend_Registry::get('db');
			$select = $db->select()
				->from('appartient')
				->join('participe',
				       $db->quoteInto('participe.activite = ?', $a->id).
				       ' AND '.
				       'participe.unite = appartient.unite',
				       array())
				->where('individu = ?', $i->id)
				->where('fin IS NULL');
			$ta = new Appartenances();
			$app = $ta->fetchSelect($select)->current();
			$unite = null;
			if ($app)
				$unite = $app->findParentUnites();

			if ($unite)
				$this->actions->append('Reporter',
						       array('action' => 'reporter',
							     'unite' => $unite->id),
						       array(null, $unite));
		}
		else {
			$this->actions->append('Envoyer la chaîne',
					       array('action' => 'chaine'),
					       array(null, $a));
		}

		$this->actions->append('Modifier',
				       array('action' => 'modifier',
					     'activite' => $a->id),
				       array(null, $a));
		$this->actions->append('Annuler',
				       array('action' => 'annuler',
					     'activite' => $a->id),
				       array(null, $a));

		$upe = $a->getUnitesParticipantesExplicites();
		if ($upe->count() == 1) {
			$u = $upe->current();
			$this->connexes->append('Calendrier',
						array('action' => 'calendrier',
						      'unite' => $u->id,
						      'annee' => $a->getAnnee()),
						array(null, $u));
		}

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

	function chaineAction()
	{
		$this->view->activite = $a = $this->_helper->Activite();

		$this->assert(null, $a, 'chaine',
			      "Vous n'avez pas le droit d'envoyer la chaîne de cette activité.");

		$this->metas(array('DC.Title' => 'Envoyer la chaîne de '.$a->getIntitule()));



		$this->view->model = $m = new Wtk_Form_Model('message');
		$m->addString('intro', "Intro", "Chers scouts,");
		$m->addString('message', 'Message supplémentaire');
		$moi = Zend_Registry::get('individu');
		$m->addString('signature', "Signature", "FSS,\n".$moi->getFullname(false));
		$m->addNewSubmission('envoyer', 'Envoyer');

		$this->view->intro = "Cette activité se déroul".($a->isFuture()? 'er' : '')."a ".
			$a->getDate().($a->lieu ? " à ".$a->lieu : "").". ".
			"Rendez-vous à ".
			strftime("%Hh%M", strtotime($a->debut)).
			($a->depart ? " ".$a->depart : "").", ".
			"retour ".strftime("vers %Hh%M", strtotime($a->fin)).
			($a->retour ? " ".$a->retour : "").". ";
		$this->view->warn = "**La présence de chacun est primordiale** ".
			"pour le bon déroulement de cette activité. ".
			"**Répondez à ce courriel pour confirmer votre présence**.";

		// À APPORTER
		$this->view->apporter = $l = new Wtk_List();

		if ($a->cotisation)
			$l->addItem()->addRawText($a->cotisation." € de cotisation ;");

		foreach($a->findApports() as $apport)
			$l->addItem()->addInline($apport->item." ;");

		// ENVOI
		if ($m->validate()) {

			$annee = $a->getAnnee();
			$us = $a->findUnitesViaParticipations();
			$is = array();
			foreach($us as $u) {
				$as = $u->getApps($annee == $this->_helper->Annee() ? null : $annee);
				foreach($as as $app) {
					$i = $app->findParentIndividus();
					if ($i->adelec)
						$is[$i->adelec] = $i->getFullname(false);
				}
			}

			$mail = new Strass_Mail(wtk_ucfirst($a->getIntitule()));
			$mail->replyTo($moi->adelec, $moi->getFullname(false));

			foreach($is as $adelec => $nom)
				$mail->addTo($adelec, $nom);

			$d = $mail->getDocument();
			$s = $d->addSection($a->getIntitule());
			$plus = false;
			// INTRO
			$intro = $s->addText();
			if ($message = $m->get('intro'))
				$intro->append($message."\n\n");
			$intro->append($this->view->intro);
			

			// APPORTER
			if ($this->view->apporter->count()) {
				$ss = $s->addSection('apporter', 'À apporter');
				$ss->addChild($this->view->apporter);
				$plus = true;
			}


			// DÉTAILS
			$ss = $s->addSection('message', "Détails");
			if ($a->message)
				$ss->addText($a->message);

			if ($message = $m->get('message'))
				$ss->addText($message);
			
			if (!$ss->count())
				$s->removeChild($ss);
			else
				$plus = true;

			// s'il y a des détails, demander explicitement de lire attentivement … :/
			if ($plus)
				$intro->append("**Veuillez lire attentivement les informations qui suivent.**");
			
			$s->addParagraph()->addFlags('warn')
				->addInline($this->view->warn);
			$s->addText($m->get('signature'));

			// pièces-jointes
			$das = $a->findDocsActivite();
			foreach($das as $da) {
				$doc = $da->findParentDocuments();
				$at = $mail->createAttachment(file_get_contents($doc->getFichier()),
							      $doc->type_mime);
				$at->filename = $doc->id.'.'.$doc->suffixe;
				$at->description = $doc->titre;
			}

			$mail->send();
			$this->redirectSimple('consulter');
		}

		$this->branche->append(ucfirst($a->intitule),
				       array('action' => 'consulter'));

		$this->actions->append('Modifier',
				       array('action' => 'modifier',
					     'activite' => $this->view->activite->id),
				       array(null, $a));
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
		// laisser la génération d'intitulé se mettre à jour.
		$intitule = Activite::generateIntitule($a->toArray(), $participantes);
		if ($a->intitule != $intitule)
			$intitule = $a->intitule;
		else
			$intitule = '';

		$m->addString('intitule', 'Intitulé explicite', $intitule);
		$m->addString('lieu', 'Lieu', $a->lieu);
		$m->addString('depart', 'Départ', $a->depart);
		$m->addDate('debut', 'Début', $a->debut, '%Y-%m-%d %H:%M');
		$m->addString('retour', 'Retour', $a->retour);
		$m->addDate('fin', 'Fin', $a->fin, '%Y-%m-%d %H:%M');

		$m->addInteger('cotisation', 'Cotisation', intval($a->cotisation));
		$apports = $a->findApports();
		$i = $m->addTable('apporter', "Apporter",
				  array('item' => array('String', "Apporter")));
		foreach($apports as $apport) {
			$i->addRow(array('item' => $apport->item));
		}
		$i->addRow(array('item' => ""));
		
		$m->addString('message', 'Informations complémentaires', $a->message);

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
				$champs = array('debut', 'fin', 'lieu', 'depart', 'retour', 'cotisation', 'message');
				foreach($champs as $champ)
					$a->$champ = $m->get($champ);

				$data = $m->get();
				$unites = $tu->find($data['unites']);
				unset($data['unites']);
				unset($data['apporter']);
				$intitule = $m->get('intitule');
				$annee = intval(date('Y', strtotime($data['debut']) - 243 * 24 * 60 * 60));
				$a->intitule = $intitule ? $intitule : Activite::generateIntitule($data, $unites);
				$a->id = wtk_strtoid($a->getIntitule());
				$a->save();

				// mettre à jour les participations
				$unites = $tu->getIdSousUnites((array) $m->get('unites'),
							       $a->getAnnee());
				$this->updateParticipations($a, $unites);
				$this->updateApports($a, $m->get('apporter'));

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
		$this->assert(Zend_Registry::get('individu'), $a, 'annuler',
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
					$unites = $a->getUnitesParticipantesExplicites();
					$intitule = (string) $a;
					$a->delete();
					$this->_helper->Log("Activité annulé", array(),
							    $this->_helper->url->Url(array('action' => 'calendrier',
											   'unite' => $unites[0]->id)),
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

	function rapportsAction()
	{
		$u = $this->_helper->Unite();
		$this->branche->append("Rapports d'activités",
				       array('annee' => null));
		$annee = $this->_helper->Annee();

		$this->metas(array('DC.Title' => "Rapports d'activités ".$annee,
				   'title.alternative.append' => wtk_ucfirst($u->getFullname())));

		$ps = new Participations();
		$db = $ps->getAdapter();
		$min = $this->_helper->Annee->dateDebut($annee).' 00:00';
		$max = $this->_helper->Annee->dateFin($annee).' 23:59';
		$on = "'".$min."' <= debut AND debut <= '".$max."'".
			' AND '.
			"'".$min."' <= fin AND fin <= '".$max."'".
			' AND '.
			// N'afficher que les activités passées
			' fin <= "'.strftime('%Y-%m-%d 23:59').'"';

		$select = $db->select()
			->distinct()
			->from('participe')
			->join('activites',
			       'participe.activite = activites.id'.' AND '.$on, array())
			->where('unite = ?', $u->id)
			->order('debut');
		$ps = $ps->fetchSelect($select);

		$this->view->unite = $u;
		$this->view->participations = $ps;

		$photos = array();
		foreach($ps as $p) {
			$photos[$p->activite] =
				$p->findParentActivites()->getPhotoAleatoire();
		}
		$this->view->photos = $photos;

		$this->connexes->append("Calendrier ".$annee,
					array('action' => 'calendrier'));
	}

	function rapportAction()
	{
		list($u, $p, $a) = $this->getParticipation(false);

		$this->metas(array('DC.Title' => "Rapport de ".$a,
				   'title.alternative.append' => wtk_ucfirst($u->getFullname())));


		$this->view->unite = $u;
		$this->view->activite = $p->findParentActivites();
		$this->view->participation = $p;
		$s = $p->getTable()->select()->order('RANDOM()')->limit(1);
		$this->view->photo =
			$this->view->activite->findPhotos($s)->current();


		// BRANCHES
		$this->branche->insert(-1,
				       "Rapports",
				       array('action' => 'rapports',
					     'activite' => null));

		$this->connexes->append("Revoir la chaîne",
					array('action' => 'consulter',
					      'activite' => $a->id,
					      'unite' => null));

		$this->connexes->append("Photos de l'activité",
					array('action' => 'consulter',
					      'controller' => 'photos',
					      'unite' => null));

		$this->connexes->append("Tout les rapports",
					array('action' => 'rapports',
					      'controller' => 'activites',
					      'annee'	=> $a->getAnnee()));

		$this->actions->append("Enregistrer la progression",
				       array('controller' => 'unites',
					     'action' => 'progression',
					     'activite' => $a->id),
				       array(null, $u));
		$this->actions->append("Éditer le rapport",
				       array('action' => 'reporter'),
				       array(null, $p));

		$this->actions->append("Envoyer une photo",
				       array('action' => 'envoyer',
					     'controller' => 'photos'),
				       array(null, $a, 'envoyer-photo'));

	}

	function reporterAction()
	{
		list($u, $p, $a) = $this->getParticipation();
		$this->assert(Zend_Registry::get('individu'), $p, 'reporter',
			      "Vous n'avez pas le droit d'éditer le rapports d'activités");

		$m = new Wtk_Form_Model('rapport');
		$m->addInstance('String', 'boulet', "Boulet", $p->boulet);
		$m->addInstance('String', 'rapport', "Rapport", $p->rapport);
		$m->addNewSubmission('enregistrer', "Enregistrer");

		if ($m->validate()) {
			$db = $p->getTable()->getAdapter();
			$db->beginTransaction();
			try {
				$ks = array('boulet', 'rapport');
				foreach($ks as $k)
					$p->$k = $m->$k;
				$p->derniere_edition = strftime('%Y-%m-%d %T');
				$p->save();
				$db->commit();
				$this->redirectSimple('rapport', null, null,
						      array('activite' => $a->id,
							    'unite' => $u->id));
			}
			catch(Exception $e) {
				$db->rollBack();
				throw $e;
			}
		}

		$this->view->model = $m;
		$this->view->unite = $u;
		$this->view->participation = $p;
		$this->view->activite = $a;
	}


	function getParticipation($reset = true)
	{
		$u = $this->_helper->Unite();
		$a = $this->_helper->Activite(null, true, $reset);
		$ps = new Participations();
		$p = $a && $u ? $ps->find($a->id, $u->id)->current() : null;
		if (!$p && $throw)
			throw new Knema_Controller_Action_Exception_Notice(wtk_ucfirst($u->getFullname()).	
									   " ne participe pas à l'activité ".
									   $a->getIntitule());
		return array($u, $p, $a);
	}

	// HELPER
	function getUnitesProgrammable($assert = TRUE) {
		$unites = array();
		$individu = Zend_Registry::get('individu');
		if (!$this->assert($individu)) {
			// Sélectionner les unité où l'individu est actuellement inscrit
			// et dont il a le droit de prévoir une activité.
			$us = $individu->getUnites(true, true);
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
			throw new Knema_Controller_Action_Exception
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

	function updateApports($activite, $items)
	{
		$ti = new Apports();

		// suppression de l'ancienne liste.
		$anciennes = $activite->findApports();
		foreach($anciennes as $item)
			$item->delete();

		// re création de la nouvelle.
		foreach($items as $i => $item)
			if ($item['item']) {
				$tuple = array('activite'	=> $activite->id,
					       'item'		=> $item['item'],
					       'ordre'		=> $i);
				$ti->insert($tuple);
			}
	}
}
