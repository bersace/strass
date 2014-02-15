<?php

require_once 'Strass/Unites.php';
require_once 'Strass/Activites.php';


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

    $this->view->fiches = (bool) Zend_Registry::get('user');

    $this->actions->append("Modifier",
			   array('controller' => 'unites',
				 'action' => 'modifier',
				 'unite' => $u->slug),
			   array(null, $u));

    $soustypename = $u->getSousTypeName();
    if (!$u->isTerminale() && $soustypename)
      $this->actions->append(array('label' => "Fonder une ".$soustypename),
			     array('action' => 'fonder',
				   'parente' => $u->slug),
			     array(null, $u));

    if (!$u->isFermee())
      $this->actions->append("Fermer l'unité",
			     array('action' => 'fermer'),
			     array(null, $u));

    $this->actions->append("Détruire",
			   array('controller' => 'action',
				 'action' => 'detruire',
				 'unite' => $u->slug),
			   array(null, $u));

    // journal d'unité
    $journal = $u->findJournaux()->current();
    if (!$journal)
      $this->actions->append("Fonder le journal d'unité",
			     array('controller' => 'journaux',
				   'action' => 'fonder'),
			     array(null, $u, 'fonder-journal'));
  }

  function contactsAction()
  {
    $this->view->unite = $unite = $this->_helper->Unite();
    $this->view->annee = $annee = $this->_helper->Annee();
    $this->assert(null, $unite, null,
		  "Vous n'avez pas le droit de voir les contacts de l'unité");
    $this->view->model = new Strass_Pages_Model_Contacts($unite, $annee);

    $i = Zend_Registry::get('user');
    // si l'individu est connecté, on propose le lien.
    $this->view->fiches = (bool) $i;

    $this->metas(array('DC.Title' => 'Effectifs '.$annee,
		       'DC.Title.alternate' => 'Effectifs '.$annee.' – '.
		       wtk_ucfirst($unite->getFullname())));

    if (!$unite->findParentTypesUnite()->virtuelle) {
      $this->actions->append(array('label' => "Compléter l'effectif"),
			     array('controller' => 'unites',
				   'action' => 'historique',
				   'unite' => $unite->slug),
			     array(null, $unite));

      $this->actions->append(array('label' => "Inscrire un nouveau"),
			     array('controller' => 'inscription',
				   'action' => 'nouveau',
				   'unite' => $unite->slug),
			     array(null, $unite));
    }

    /* $this->formats('vcf', 'ods', 'csv'); */
  }

  function listeAction()
  {
    $this->view->unite = $unite = $this->_helper->Unite();
    $this->view->annee = $annee = $this->_helper->Annee();

    $this->assert(null, $unite, null,
		  "Vous n'avez pas le droit de voir les contacts de l'unité");

    $m = new Wtk_Form_Model('liste');
    $enum = array('adelec'	=> 'Adélec',
		  'fixe'	=> 'Fixe',
		  'portable'	=> 'Portable',
		  'telephone'	=> 'Téléphone',
		  'adresse'	=> 'Adresse',
		  'naissance'	=> 'Naissance',
		  'age'		=> 'Âge',
		  'situation'	=> 'Situation',
		  'origine'	=> 'Unité d\'origine',
		  'totem'	=> 'Totem',
		  'numero'	=> 'N°adh');
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
      $i = Zend_Registry::get('user');
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
      $this->view->fiches = (bool) $i;
      $this->view->apps = $apps = $unite->getApps($annee);

      // de même pour les sous-unités
      $this->view->sousunites = $unite->getSousUnites(false, $annee);
      $this->view->sousapps = $this->_helper->SousApps($this->view->sousunites, $annee);
    }
    else {
      $this->view->model = $m;
    }
  }

  function fonderAction()
  {
    $this->view->parente = $unite = $this->_helper->Unite(null, false);
    $this->assert(null, $unite, 'fonder',
		  "Pas le droit de fonder une sous-unité !");

    $ttu = new TypesUnite;
    // sous types possibles
    if ($unite) {
      $soustypes = $unite->getSousTypes();
    }
    else {
      $soustypes = $ttu->fetchAll($ttu->select()->where('virtuelle = 0'));
    }

    $st = $soustypes->count() > 1 ? 'sous-unité' : $soustypes->rewind()->current();
    if ($unite)
      $this->metas(array('DC.Title' => 'Fonder une '.$st.' de '.$unite->getFullname()));
    else
      $this->metas(array('DC.Title' => 'Fonder une unité'));

    $ens = array();
    $enum = array();
    foreach($soustypes as $type) {
      $en = $type->getExtraName();
      if ($en)
	array_push($ens, $en);

      $label = wtk_ucfirst($type->nom);
      /* en cas de nom doublon (ex: équipe, sizaine), inclure le nom du type parent */
      $homonymes = $ttu->countRows($ttu->select()->where('nom = ?', $type->nom)) > 1;
      if (!$unite && $homonymes)
	$label.= ' (' .$type->findParentTypesUnite()->nom. ')';
      $enum[$type->id] =  $label;
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
	$t = new Unites;

	extract($m->get());
	$data = array('slug' => wtk_strtoid($types[$type].'-'.$nom),
		      'nom' => $nom,
		      'type' => $type,
		      'extra' => $extra,
		      'parent' => $unite ? $unite->id : null);
	$k = $t->insert($data);
	$u = $t->findOne($k);

	$this->logger->info("Nouvelle unité",
			    $this->_helper->Url('index', 'unites', null, array('unite' => $u->slug), true));

	$db->commit();
	$this->redirectSimple('index', 'unites', null, array('unite' => $u->slug), true);
      }
      catch(Exception $e) {
	$db->rollBack();
	throw $e;
      }
    }

    $this->view->model = $m;
  }


  function modifierAction()
  {
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
	$u->slug = wtk_strtoid($u->getFullname());
	$u->extra = $m->get('extra');
	$u->save();

	// photos
	$i = $m->getInstance('image');
	if ($i->isUploaded()) {
	  $u->saveImage($i->getTempFilename(), $i->getMimeType());
	}

	// wiki
	$w = $u->getWiki(null, false);
	$d = dirname($w);
	if (!file_exists($d))
	  mkdir($d, 0755, true);

	file_put_contents($w, trim($m->get('presentation')));

	$this->_helper->Log("Unité modifiée", array($u),
			    $this->_helper->Url('index', 'unites', null,
						array('unite' => $u->slug)),
			    (string) $u);

	$db->commit();
	$this->redirectSimple('index', 'unites', null,
			      array('unite' => $u->slug));
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
			   array(Zend_Registry::get('user'), $u));


    $ti = new Individus;
    $is = $ti->fetchAll($select);
    if (!$is->count())
      throw new Strass_Controller_Action_Exception("Aucun individu n'est disponible ".
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
      throw new Strass_Controller_Action_Exception("L'unité est complète pour l'année ".$a." !");

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
    $is = $t->fetchAll($s);
    $p = $this->_getParam('page');
    $p = $p ? $p : 1;
    $this->view->individus = new Wtk_Pages_Model_Iterator($is, 20, $p);
    $this->view->fiches = (bool) Zend_Registry::get('user');
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
	     "unite.parent = '".$unite->id."'",
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
    $is = $ti->fetchAll($s);
    $p = $this->_getParam('page');
    $p = $p ? $p : 1;
    $this->view->individus = new Wtk_Pages_Model_Iterator($is, 20, $p);
    $this->view->fiches = (bool) Zend_Registry::get('user');
  }
}
