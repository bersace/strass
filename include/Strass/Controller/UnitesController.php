<?php

require_once 'Strass/Unites.php';
require_once 'Strass/Activites.php';
require_once 'Strass/Progression.php';
require_once 'Image/Transform.php';


class UnitesController extends Strass_Controller_Action
{
  public function indexAction()
  {
    $this->view->unite = $u = $this->_helper->Unite();
    $this->view->annee = $a = $this->_helper->Annee();
    $this->view->model = new Strass_Pages_Model_AccueilUnite($u, $a,
							     $this->_helper->Annee->cetteAnnee(),
							     $this->assert(null, $u, 'calendrier'));

    $this->metas(array('DC.Title' => wtk_ucfirst($u->getFullname()).' '.$a));
    
    $this->connexes->append("Photos",
			    array('controller' => 'photos',
				  'action' => 'index'));
    /* $this->liensEffectifs($u, $a); */
    $this->view->profils = (bool) Zend_Registry::get('individu');
    
    $this->connexes->append("Nouveaux",
			    array('action' => 'nouveaux'),
			    array(null, $u, 'nouveaux'));
    $this->connexes->append("Liste personnalisée",
			    array('action' => 'liste'),
			    array(null, $u, 'lister'));
    $this->connexes->append("Non enregistré",
			    array('action'  => 'nonenregistres'),
					array(null, $u, 'nonenregistres'));
  }

	// LES VUES DES EFFECTIFS
	function listeAction()
	{
		$this->view->unite = $unite = $this->_helper->Unite();
		$this->view->annee = $annee = $this->_helper->Annee();

		$this->assert(null, $unite, null,
			      "Vous n'avez pas le droit de voir les contacts de l'unité");

		$m = new Wtk_Form_Model('liste');
		$enum = array(// contact
			      'adelec'		=> 'Adélec',
			      'fixe'		=> 'Fixe',
			      'portable'	=> 'Portable',
			      'telephone'	=> 'Téléphone',
			      'adresse'		=> 'Adresse',
			      // infos perso
			      'naissance'	=> 'Naissance',
			      'age'		=> 'Âge',
			      'situation'	=> 'Situation',
			      'origine'		=> 'Unité d\'origine',
			      // progression,
			      'perespi'		=> 'Père spi',
			      'parrain'		=> 'Parrain',
			      'totem'		=> 'Totem',
			      'etape'		=> 'Étape',
			      'numero'		=> 'N°adh',
			      // formation
			      'cep1'		=> 'CEP1', // branche ????
			      'cep2'		=> 'CEP2',
			      'formation'	=> 'Formation');
		$m->addEnum('existantes', "Colonnes préremplis", array('telephone'), $enum, TRUE);
		$t = $m->addTable('supplementaires', "Colonnes supplémentaires",
				  array('nom' => array('String', 'Nom')));
		$t->addRow(array('nom' => ""));

		$fmts = array('xhtml'	=> 'XHTML',
			      'ods'	=> 'Tableur');
		$m->addEnum('format', "Format", 'xhtml', $fmts);
		$m->addNewSubmission('lister', 'Lister');

		if ($m->validate()) {
			$this->formats('ods');
			$this->getRequest()->setParam('format', $m->get('format'));
			$this->view->model = null;
			$acl = Zend_Registry::get('acl');
			$i = Zend_Registry::get('individu');
			$this->view->terminale = $unite->isTerminale();
			$this->view->supplementaires = $m->get('supplementaires');

			$existantes = $m->get('existantes');
			$this->view->existantes = array();
			foreach($existantes as $k) {
				$this->view->existantes[$k] = $enum[$k];
			}


			// critère de sélection par année
			$this->view->annees = $unite->getAnneesOuverte();
			$this->view->annee = $annee = $this->_helper->Annee();

			$this->metas(array('DC.Title' => 'Effectifs '.$annee,
					   'DC.Title.alternate' => 'Effectifs '.$annee.' – '.
					   wtk_ucfirst($unite->getFullname())));

			// si l'individu est connecté, on propose le lien.
			$this->view->profils = (bool) $i;
			$this->view->apps = $apps = $unite->getApps($annee);

			// de même pour les sous-unités
			$this->view->sousunites = $unite->getSousUnites(false, $annee);
			$this->view->sousapps = $this->_helper->SousApps($this->view->sousunites, $annee);
		}
		else {
			$this->view->model = $m;
		}

		$this->liensEffectifs($unite, $annee);
	}

	function contactsAction()
	{
		$this->view->unite = $unite = $this->_helper->Unite();
		$this->view->annee = $annee = $this->_helper->Annee();
		$this->assert(null, $unite, null,
			      "Vous n'avez pas le droit de voir les contacts de l'unité");
		$this->view->model = new Strass_Pages_Model_Contacts($unite, $annee);

		$i = Zend_Registry::get('individu');
		// si l'individu est connecté, on propose le lien.
		$this->view->profils = (bool) $i;

		// critère de sélection par année
		$this->metas(array('DC.Title' => 'Effectifs '.$annee,
				   'DC.Title.alternate' => 'Effectifs '.$annee.' – '.
				   wtk_ucfirst($unite->getFullname())));

		$this->formats('vcf', 'ods', 'csv');
		$this->liensEffectifs($unite, $annee);
	}

	function progressionAction()
	{
		// on sélectionne soit les unites participant à une
		// activité, soit une unité spécifique.
		$activite = $this->_helper->Activite(null, false);
		if ($activite) {
			$unites = array();
			$participantes = $activite->getUnitesParticipantes();
			foreach($participantes as $unite)
				$unites[] = $unite;
		}
		else
			$unites = array($this->_helper->Unite());

		$this->assert(null, $unites, 'progression',
			      "Vous n'avez pas le droit d'enregistrer ".
			      "la progression des membres de cette unité.");

		$this->metas(array('DC.Title' => 'Enregistrer la progression'));

		$m = new Wtk_Form_Model('progression');
		$m->addNewSubmission('valider', "Valider");

		// ÉTAPE 1: sélectionner l'étape de progression
		$g = $m->addGroup('etape');

		// sélection des étapes disponibles
		$te = new Etape();
		$ptu = current($unites)->findParentTypesUnite();

		// extraire l'age min et l'âge max de l'unité et des sous-unités.
		$s = $te->getAdapter()->select()
			->from('types_unite',
			       new Zend_Db_Expr('MIN(age_min) AS age_min, MAX(age_max) AS age_max'))
			->where('age_min > 0');
		if ($ptu->parent)
			$s->where('id = ? OR parent = ?', $ptu->id);

		extract(current($te->getAdapter()->fetchAll($s)));

		// sélection des étapes valide pour cette unité et ses sous-unités.
		$s = $te->select()
			->where("age_min >= ?", (int)$age_min)
			->order('ordre');

		if ($ptu->sexe != 'm')
			$s->where("sexe = ? OR sexe = 'm'", $ptu->sexe);
		else
			$s->where('sexe = ?', $ptu->sexe);

		$etapes = $te->fetchSelect($s);
		if (!count($etapes))
			throw new Knema_Controller_Action_Exception("Aucune étape de progression disponible pour cette unité.");

		// création des valeurs possibles.
		$enum = array();
		foreach($etapes as $etape)
			$enum[$etape->id] = $etape->titre;
		$g->addEnum('etape', "Étape", key($enum), $enum);

		// date de la progression (du rasso),
		$date = $activite ? $activite->fin : strftime('%Y-%m-%d');
		$g->addDate('date', "Date", $date);

		// lieu
		$lieu = $activite ? $activite->lieu : "";
		$g->addString('lieu', "Lieu", $lieu);

		// ÉTAPE 2 : sélectionner les individus ayant progressé
		$g = $m->addGroup('individus');
		if ($m->validate()) {
			$s = $te->select()->where('id = ?', $m->get('etape/etape'));
			$etape = $te->fetchAll($s)->current();

			$enum = array();
			foreach($unites as $unite) {
				$ti = new Individus();
				$s = $ti->select()
					->distinct()
					->from('individus')
					// individu de cette unité
					->join('appartient',
					       'appartient.individu = individus.id'.
					       ' AND '.
					       "appartient.unite = '".$unite->id."'",
					       array())
					// n'ayant pas déjà fait cette progression
					->joinLeft('progression',
						   'progression.individu = individus.id'.
						   ' AND '.
						   "progression.etape = '".$etape->id."'",
						   array())
					->where('progression.etape IS NULL');
				if ($etape->depend)
					// ayant passé l'étape précédente.
					$s->join('progression',
						 'progression_2.individu = individus.id'.
						 ' AND '.
						 "progression_2.etape = '".$etape->depend."'",
						 array());
				if ($activite) {
					$s->where('appartient.debut < ?', $activite->debut);
					$s->where('appartient.fin > ? OR appartient.fin IS NULL', $activite->fin);
				}
				$individus = $ti->fetchSelect($s);
				foreach($individus as $individu)
					$enum[$individu->id] = $individu->getFullname(false);
			}

			if (!count($enum)) {
				$dep = $etape->findParentEtape();
				throw new Knema_Controller_Action_Exception("Aucun individus ne peut avoir ".
									    $etape->participe_passe." ".$etape->titre.", ".
									    "assurez-vous d'avoir bien enregistrer chaque individu ayant ".
									    $dep->participe_passe." ".$dep->titre.".");
			}
			ksort($enum);
			$g->addEnum('individus', "Individus", null, $enum, true);
		}

		$this->view->model = new Wtk_Pages_Model_Form($m);

		if ($m->validate()) {
			$db = $te->getAdapter();
			$db->beginTransaction();
			try {
				// construction du tuple par défaut
				$tuple = $m->get('etape');
				$s = $te->select()->where('id = ?', $tuple['etape']);
				$tuple['sexe'] = $te->fetchAll($s)->current()->sexe;

				// insertion pour chaque individu d'un tuple
				$tp = new Progression();
				$is = $m->get('individus/individus');
				foreach ($is as $i) {
					$tuple['individu'] = $i;
					$tp->insert($tuple);
				}

				$db->commit();

				if ($activite) {
					$this->_helper->Log("Progression enregistrée", array_merge($unites, array($activite)),
							    $this->_helper->Url('consulter', 'activites', null, array('activite' => $activite->id)),
							    (string) $activite);
					$this->redirectSimple('consulter', 'activites');
				}
				else {
					$u = reset($unites);
					$this->_helper->Log("Progression enregistrée", array($u),
							    $this->_helper->Url('index', 'unites', null, array('unite' => $u->id)),
							    (string) $u);
					$this->redirectSimple('index', 'unites');
				}
			}
			catch(Exception $e) {
				$db->rollback();
				throw $e;
			}
		}
	}

	function fonderAction()
	{
		$this->view->parente = $unite = $this->_helper->Unite(null, false);
		$this->assert(null, $unite, 'fonder',
			      "Pas le droit de fonder une sous-unité !");

		// sous types possibles
		if ($unite) {
			$soustypes = $unite->getSousTypes();
		}
		else {
			$ttu = new TypesUnite;
			$soustypes = $ttu->fetchAll();
		}

		$ens = array();
		$enum = array();
		foreach($soustypes as $type) {
			$en = $type->getExtraName();
			if ($en)
				array_push($ens, $en);

			$enum[$type->id] = wtk_ucfirst($type->nom);
		}
		$ens = array_unique($ens);
		$types = $enum;



		$m = new Wtk_Form_Model('fonder');
		$m->addEnum('type', 'Type', key($enum), $enum);

		if (key($enum) == 'sizloup') {
			// préselectionner les couleurs des loups.
			$couleurs =
				array('noir', 'gris', 'brun', 'blanc', 'fauve', 'tacheté');
			$enum = array();
			foreach($couleurs as $couleur) {
				// ne pas permettre de recréer une sizaine.
				$ex = $unite->findUnites("unites.nom = '".$couleur."'")->current();
				if (!$ex)
					$enum[wtk_strtoid($couleur)] = wtk_ucfirst($couleur);
			}
			$m->addEnum('nom', 'Nom', null, $enum);
		}
		else
			$m->addString('nom', "Nom");

		$m->addString('extra', current($ens));
		$m->addNewSubmission('fonder', 'Fonder');

		if ($m->validate()) {
			$db = Zend_Registry::get('db');
			$db->beginTransaction();
			try {
				$unites = new Unites();

				extract($m->get());
				$data = array('id' => wtk_strtoid($types[$type].'-'.$nom),
					      'nom' => $nom,
					      'type' => $type,
					      'extra' => $extra,
					      'parent' => $unite ? $unite->id : null);
				$unites->insert($data);

				//      $tmp = $m->getInstance('image')->getTempFilename();
				//      $type = $m->image['type'];
				//      $image = 'data/strass/unites/'.$m->id.'.png';
				//      if (!move_uploaded_file($tmp, $image)) {
				//        throw new Exception("Impossible de récupérer l'image.");
				//      }

				$u = $unites->find($data['id']);
				$this->_helper->Log("Nouvelle unité", array($u),
						    $this->_helper->Url('index', 'unites', null,
									array('unite' => $data['id'])),
						    (string) $u);
				$db->commit();
				$this->redirectSimple('index', 'unites', null,
						      array('unite' => $data['id']));
			}
			catch(Exception $e) {
				$db->rollBack();
				throw $e;
			}
		}

		$st = $soustypes->count() > 1 ? 'sous-unité' : $soustypes->rewind()->current();
		if ($unite)
			$this->metas(array('DC.Title' => 'Fonder une '.$st.' de '.$unite->getFullname()));
		else
			$this->metas(array('DC.Title' => 'Fonder une unité'));
		$this->view->model = $m;
	}


	function modifierAction()
	{
		// init
		$u = $this->_helper->Unite();
		$this->assert(null, $u, 'modifier',
			      "Vous n'avez pas le droit de modifier cette unité");

		$this->metas(array('DC.Title' => 'Modifier '.$u->getFullname()));

		$m = new Wtk_Form_Model('unite');
		$m->addString('nom', "Nom", $u->nom);
		$m->addString('extra',
			      $u->findParentTypesUnite()->getExtraName(),
			      $u->extra);
		$m->addFile('image', "Image");
		$w = $u->getWiki(null, false);
		$m->addString('presentation', "Message d'index", is_readable($w) ? file_get_contents($w) : '');
		$m->addNewSubmission('enregistrer', "Enregistrer");

		// métier;
		if ($m->validate()) {
			$db = Zend_Registry::get('db');
			$db->beginTransaction();
			try {
				$u->nom = $m->get('nom');
				$u->id = wtk_strtoid($u->getFullname());
				$u->extra = $m->get('extra');
				$u->save();

				// photos
				$i = $m->getInstance('image');
				if ($i->isUploaded()) {
					$tmp = $i->getTempFilename();
					if ($fichier = $u->getImage())
						unlink($fichier);

					$fichier = $u->getImage(null, false);
					$dossier = dirname($fichier);
					if (!file_exists($dossier))
						mkdir($dossier, 0755, true);
					$tr = Image_Transform::factory('GD');
					$tr->load($tmp);
					list($w, $h) = $tr->getImageSize();
					// largeur max de 256;
					$max = 256;
					$ratio = max($w/$max, $h/$max);

					if ($ratio > 1 || $i->getMimeType() != 'image/png') {
						if ($ratio > 1) {
							$w /= $ratio;
							$h /= $ratio;
							$tr->resize(intval($w), intval($h));
						}
						$tr->save($fichier, 'png');
					}
					else {
						copy($tmp, $fichier);
					}
					$tr->free();
				}

				// wiki
				$w = $u->getWiki(null, false);
				$d = dirname($w);
				if (!file_exists($d))
					mkdir($d, 0755, true);

				file_put_contents($w, trim($m->get('presentation')));

				$this->_helper->Log("Unité modifiée", array($u),
						    $this->_helper->Url('index', 'unites', null,
									array('unite' => $data['id'])),
						    (string) $u);

				$db->commit();
				$this->redirectSimple('index', 'unites', null,
						      array('unite' => $u->id));
			}
			catch(Exception $e) {
				$db->rollBack();
				throw $e;
			}
		}

		// vue
		$this->view->unite = $u;
		$this->view->model = $m;
	}

	function historiqueAction()
	{
		$u = $this->_helper->Unite();
		$a = $this->_helper->Annee(false);
		$m = new Wtk_Form_Model('historique');

		$this->assert(null, $u, 'inscrire',
			      "Vous n'avez pas le droit d'inscrire un membre dans cette unité.");

		// sélectionner les individus pouvant avoir fait partie de cette unité
		$db = $u->getTable()->getAdapter();
		$t = $u->findParentTypesUnite();
		$select = $db->select()
			->from('individus')
			->order('naissance');

		if ($a) {
			$select->joinLeft('appartient',
					  "appartient.individu = individus.id".
					  " AND ".
					  "debut < '".$a."-08-31'".
					  " AND ".
					  "(fin > '".($a+1)."-09-01' OR fin IS NULL)\n",
					  array())
				->where('naissance <= "'.($a - $t->age_min).'-12-31"'.
					' AND '.'naissance >= "'.($a - $t->age_max).'-01-01"')
				->where('appartient.individu IS NULL');
		}

		if ($t->sexe != 'm')
			$select->where('sexe = ?', $t->sexe);

		$this->actions->append(array('label' => "Inscrire un nouveau"),
				       array('controller' => 'inscription',
					     'action' => 'nouveau',
					     'unite' => $u->id,
					     'annee' => $a),
				       array(Zend_Registry::get('individu'), $u));


		$ti = new Individus;
		$is = $ti->fetchSelect($select);
		if (!$is->count())
			throw new Knema_Controller_Action_Exception("Aucun individu n'est disponible ".
								    "pour cette unité pour l'année ".
								    $a."-".($a+1).". Inscrivez un nouveau membre.");

		$enum = array();
		foreach($is as $i)
			$enum[$i->id] = $i->getFullname(true, false);

		ksort($enum);
		$m->addEnum('individu', "Individu", key($enum), $enum);

		// sélectionner les postes libre
		$rs = $t->findRoles(null, 'ordre');
		$enum = array();
		foreach($rs as $r)
			$enum[$r->id] = ucfirst($r->titre);

		// on cherche les poste indisponible pour l'année courante
		if ($a) {
			$where = 'debut < "'.$a.'-12-31"'.
				' AND '.
				'fin > "'.($a + 1).'-08-31"'.
				' OR '.
				'fin IS NULL';
			$s = $ti->select()->where($where);
			$as = $u->findAppartenances($s);
		}
		else
			$as = array();

		$values = $enum;
		foreach($as as $app)
			unset($values[$app->role]);

		// unités avec une personne par poste :
		if (!count($values)
		    && in_array($t->id, array('patrouille', 'equipe', 'sizloup', 'sizjeannette')))
			throw new Knema_Controller_Action_Exception("L'unité est complète pour l'année ".$a." !");

		// on sélectionne le premier poste disponible.
		$m->addEnum('role', 'Poste', key($values), $enum);
		$m->addDate('debut', 'Début', $a.'-10-08');
		$i = $m->addBool('clore', 'Mendat terminé', true);
		$j = $m->addDate('fin', 'Fin', ($a + 1).'-10-08');
		$m->addConstraintDepends($j, $i);
		$m->addNewSubmission('valider', 'Valider');

		if ($m->validate()) {
			$ta = new Appartenances();
			$db->beginTransaction();
			try {
				$data = $m->get();
				$data['unite'] = $u->id;
				$data['type'] = $u->type;
				if (!$data['clore'])
					$data['fin'] = NULL;
				unset($data['clore']);
				$ta->insert($data);

				$ind = $ti->find($data['individu'])->current();
				$this->_helper->Log("Effectifs complétés", array($u, $ind),
						    $this->_helper->Url('index', 'unites', null, array('unite' => $u->id)),
						    (string) $u);

				$db->commit();
				$this->redirectSimple('index', null, null, null, false);
			}
			catch(Exception $e) {
				$db->rollBack();
				throw $e;
			}
		}

		$this->view->model = $m;
		$this->view->unite = $u;
		$this->view->annee = $a;
		$this->metas(array('DC.Title' => "Compléter l'effectif de ".$u->getFullname()));
		$this->branche->append("Compléter l'effectif");

	}

	function fermerAction()
	{
		$u = $this->_helper->Unite();
		$m = new Wtk_Form_Model('fermer');
		$m->addDate('fin', 'Date de fermeture');
		$m->addNewSubmission('continuer', 'Continuer');
		$this->metas(array('DC.Title' => 'Fermer '.$u->getFullname()));

		if ($m->validate()) {
			$db = $u->getTable()->getAdapter();
			$db->beginTransaction();
			try {
				$u->fermer($m->get('fin'));
				$this->_helper->Log("Fermeture de l'unité ".$u, array($u),
						    $this->_helper->Url('index', 'unites', null, array('unite' => $u->id)),
						    (string) $u);
				$db->commit();
				$this->redirectSimple('index');
			}
			catch(Exception $e) {
				$db->rollBack();
				throw $e;
			}
		}

		$this->view->unite = $u;
		$this->view->model = $m;
	}

	function detruireAction()
	{
		$u = $this->_helper->Unite();
		$this->assert(null, $u, 'detruire',
			      "Vous n'avez pas le droit de détruire cette unité.");

		$this->metas(array('DC.Title' => 'Détruire '.$u->getFullname()));

		$m = new Wtk_Form_Model('detruire');
		$m->addBool('confirmer',
			    "Je confirme la destruction de toute informations relative à l'unité ".
			    $u->getFullName().".", false);
		$m->addNewSubmission('continuer', 'Continuer');

		if ($m->validate()) {
			if ($m->get('confirmer')) {
				$db = $u->getTable()->getAdapter();
				$db->beginTransaction();
				try {
					$nom = (string) $u;
					$u->delete();
					$this->_helper->Log("Desctruction de l'unité ".$nom, array('nom' => $nom),
							    $this->_helper->Url('index', 'unites'),
							    "Unités");
					$db->commit();
					$this->redirectSimple('index', 'unites');
				}
				catch(Exception $e) {
					$db->rollBack();
					throw $e;
				}
			}
			else
				$this->redirectSimple('index', 'unites', null,
						      array('unite' => $u->id));
		}

		$this->view->unite = $u;
		$this->view->model = $m;
	}

	function nouveauxAction()
	{
		$t = new Individus();
		$s = $t->select()
			->from('individus')
			->joinLeft('appartient',
				   'appartient.individu = individus.id',
				   array())
			->where('appartient.individu IS NULL');
		$is = $t->fetchSelect($s);
		$p = $this->_getParam('page');
		$p = $p ? $p : 1;
		$this->view->individus = new Wtk_Pages_Model_Iterator($is, 20, $p);
		$this->view->profils = (bool) Zend_Registry::get('individu');
		$this->branche->append('Nouveaux');
	}

	function nonenregistresAction()
	{
		$this->view->unite = $unite = $this->_helper->Unite();
		$annee = $this->_helper->Annee();

		$this->assert(null, $unite, 'nonenregistres',
			      "Vous n'avez pas le droit de voir les individus de ce site");

		$ti = new Individus;
		$s = $ti->select()
			->from('individus')
			->join('unites',
			       "unites.id = '".$unite->id."'".
			       " OR ".
			       "unites.parent = '".$unite->id."'",
			       array())
			->join('appartient',
			       'appartient.individu = individus.id'.
			       ' AND '.
			       "appartient.unite = unites.id".
			       ' AND '.
			       "appartient.debut < '".$annee."-10'".
			       ' AND '.
			       ("(appartient.fin > '".($annee+1)."-08'".
				' OR '.
				"appartient.fin IS NULL)"),
			       array())
			->where("individus.username IS NULL or individus.username = ''")
			->order('individus.id');
		$is = $ti->fetchSelect($s);
		$p = $this->_getParam('page');
		$p = $p ? $p : 1;
		$this->view->individus = new Wtk_Pages_Model_Iterator($is, 20, $p);
		$this->view->profils = (bool) Zend_Registry::get('individu');
	}



	// FACTORISATION POUR TROMBI ET EFFECTIFS

	// helper ?
	protected function liensEffectifs($unite, $annee)
	{
		$listes = array('index'	=> wtk_ucfirst($unite->getFullName()),
				'contacts'	=> 'Contacts',
				// 'progressions'	=> 'Progressions individuelles'
				);

		// BRANCHES
		$a = $this->_getParam('action'); 
		if ($a != 'index' and array_key_exists($a, $listes))
			$this->branche->insert(-1, $listes[$a],
					       array('annee' => null));

		// CONNEXES
		foreach($listes as $action => $etiquette)
			if ($this->_getParam('action') != $action)
				$this->connexes->append($etiquette,
							array('action'  => $action),
							array(null, $unite, $action));

		$this->connexes->append("Calendrier",
					array('controller' => 'activites',
					      'action' => 'calendrier',
					      'annee' => $annee));
		$p = $unite->findParentUnites();
		if ($p)
			$this->connexes->append("Voir ".$p->__toString(),
						array('unite' => $p->id));

		// ACTIONS
		$this->actions->append("Modifier",
				       array('unite' => $unite->id,
					     'action' => 'modifier'),
				       array(null, $unite));

		$this->actions->append("Détruire",
				       array('unite' => $unite->id,
					     'action' => 'detruire'),
				       array(null, $unite));


		$soustypename = $unite->getSousTypeName();
		if (!$this->view->terminale && $soustypename)
			$this->actions->append(array('label' => "Fonder une ".$soustypename),
					       array('action' => 'fonder',
						     'parente' => $unite->id),
					       array(null, $unite));

		if ($unite->findParentTypesUnite()->findRoles()->count() != 0) {
			$this->actions->append(array('label' => "Compléter l'effectif"),
					       array('controller' => 'unites',
						     'action' => 'historique',
						     'unite' => $unite->id,
						     'annee' => $annee),
					       array(null, $unite));

			$this->actions->append(array('label' => "Inscrire un nouveau"),
					       array('controller' => 'inscription',
						     'action' => 'nouveau',
						     'unite' => $unite->id,
						     'annee' => $annee),
					       array(null, $unite));
		}

		$this->actions->append("Enregistrer la progression",
				       array('action' => 'progression',
					     'unite' => $unite->id),
				       array(null, $unite));

		if (!$unite->isFermee())
			$this->actions->append("Fermer l'unité",
					       array('action' => 'fermer'),
					       array(null, $unite));

		// journal d'unité
		$journal = $unite->findJournaux()->current();
		if ($journal)
			$this->connexes->append(wtk_ucfirst($journal->__toString()),
						array('controller' => 'journaux',
						      'action' => 'lire',
						      'journal' => $journal->id),
						array(), true);

		else if (!$this->view->terminale)
			$this->actions->append("Fonder le journal d'unité",
					       array('controller' => 'journaux',
						     'action' => 'fonder'),
					       array(null, $unite, 'fonder-journal'));

	}
}
