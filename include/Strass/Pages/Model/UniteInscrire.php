<?php

class Strass_Pages_Model_UniteInscrire extends Strass_Pages_Model_Historique
{
  function __construct($controller, $unite, $annee)
  {
    /* forcer la validité de l'année */
    parent::__construct($unite, $annee, true);
    $this->controller = $controller;
  }

  function calculerDates($annee)
  {
    $u = $this->unite;
    $a = $annee;

    $fin = strftime('%Y-%m-%d',
		    strtotime(Strass_Controller_Action_Helper_Annee::dateFin($annee).
			      ' next saturday -4 weeks'));
    $calendrier = $u->findActivites($a);
    if ($calendrier->count()) {
      $debut = substr($calendrier->current()->debut, 0, 10);
      $min_fin = strftime('%Y-%m-%d',
			  strtotime(Strass_Controller_Action_Helper_Annee::dateFin($annee).
				    ' next saturday -6 weeks'));
      $max_fin = strftime('%Y-%m-%d',
			  strtotime(Strass_Controller_Action_Helper_Annee::dateFin($annee).
				    ' next saturday'));
      while ($calendrier->valid()) {
	$fin = substr($calendrier->current()->fin, 0, 10);
	$calendrier->next();
      }

      if ($min_fin > $fin)
	$fin = $min_fin;

      if ($max_fin < $fin)
	$fin = $max_fin;
    }
    else
      $debut = strftime('%Y-%m-%d',
			strtotime(Strass_Controller_Action_Helper_Annee::dateDebut($annee).
				  ' next saturday +4 weeks'));

    return array($debut, $fin);
  }

  function fetch($annee = NULL)
  {
    $u = $this->unite;
    $a = $annee;

    $ti = new Individus;
    $individu = $app_active = null;
    $db = $ti->getAdapter();

    $m = new Wtk_Form_Model('inscrire');
    /* Pagination dans la pagination :-) */
    $pm = new Wtk_Pages_Model_Form($m);

    /* Sélection de l'individu à inscrire */
    $g = $m->addGroup('inscription');
    $candidats = $u->findCandidats($a);
    $enum = array();
    $enum['$$nouveau$$'] = 'Inscrire un nouveau';
    foreach($candidats as $candidat)
      $enum[$candidat->id] = $candidat->getFullname(false, false);
    $i = $g->addEnum('individu', 'Individu', null, $enum);
    $m->addConstraintRequired($i);

    $roles = $u->findParentTypesUnite()->findRoles();
    $enum = array();
    foreach ($roles as $role) {
      $enum[$role->id.'__'] = $role->titre;
      foreach ($role->findTitres() as $titre) {
	$enum[$role->id.'__'.$titre->nom] = $titre->nom;
      }
    }
    $default = $u->findRolesCandidats($a)->current();
    $g->addEnum('role', 'Rôle', $default ? $default->id.'__' : end(array_keys($enum)), $enum);

    list($debut, $fin) = $this->calculerDates($annee);
    $g->addDate('debut', 'Début', $debut);
    $i0 = $g->addBool('clore', 'Se termine le', false);
    $i1 = $g->addDate('fin', 'Fin', $fin);
    $m->addConstraintDepends($i1, $i0);
    $g->addBool('continuer', "J'ai d'autres inscriptions à enregistrer", false);

    /* Enregistrement d'un nouvel individu */
    $g = $m->addGroup('fiche');
    $m->addConstraintRequired($g->addString('prenom', 'Prénom'));
    $m->addConstraintRequired($g->addString('nom', 'Nom'));
    $tu = $u->findParentTypesUnite();
    if ($tu->sexe == 'm')
      $g->addEnum('sexe', 'Sexe', null, array('h' => 'Masculin', 'f' => 'Féminin'));
    else
      $g->addString('sexe', null, $tu->sexe)->setReadonly();
    $g->addString('portable', "Mobile");
    $g->addString('adelec', "Adélec");

    /* Clore une inscription active */
    $g = $m->addGroup('cloture');
    $g->addBool('clore', "Ne l'est plus depuis", true);
    $g->addDate('fin', "Fin", $debut);

    $page = $pm->partialValidate();

    if ($m->get('inscription/individu') != '$$nouveau$$'
	&& $page == 'fiche') {
      if ($m->sent_submission->id == 'continuer') {
	$i = $individu = $ti->findOne($m->get('inscription/individu'));
	$app_active = $i->findInscriptionsActives()->current();
	if ($app_active
	    && $app_active->fin === null
	    && $app_active->debut < $m->get('inscription/fin')) {
	  /* Proposer de clore une inscription déjà active */
	  $pm->gotoPage('cloture');
	}
	else
	  /* Sauter l'étape fiche si l'individu est déjà en base et libre */
	  $pm->gotoEnd();
      }
      else if ($m->sent_submission->id == 'precedent')
	$pm->gotoPage('inscription');
    }
    else if($m->get('inscription/individu') == '$$nouveau$$'
	    && $page == 'cloture') {
      /* Ne pas présenter la page cloture pour un nouveau ! */
      $pm->gotoEnd();
    }

    if ($pm->validate()) {
      $t = new Appartenances;
      $db->beginTransaction();
      try {
	if ($m->get('inscription/individu') == '$$nouveau$$') {
	  $i = new Individu;
	  $i->prenom = $m->get('fiche/prenom');
	  $i->nom = $m->get('fiche/nom');
	  $i->sexe = $m->get('fiche/sexe');
	  $i->naissance = ($a - $tu->age_min) . '-01-01';
	  $i->portable = $m->get('fiche/portable');
	  $i->adelec = $m->get('fiche/adelec');
	  $i->slug = $i->getTable()->createSlug(wtk_strtoid($i->getFullname(false, false)));
	  $i->save();
	}
	else {
	  $i = $ti->findOne($m->get('inscription/individu'));
	  $app_active = $i->findInscriptionsActives()->current();
	  if ($app_active) {
	    if ($m->get('cloture/clore')) {
	      $app_active->fin = $m->get('cloture/fin');
	      $app_active->save();
	    }
	  }
	}

	$app = new Appartient;
	$app->unite = $u->id;
	$app->individu = $i->id;
	$app->debut = $m->get('inscription/debut');
	list($role, $titre) = explode('__', $m->get('inscription/role'));
	$app->role = intval($role);
	$app->titre = $titre;
	if ($m->get('inscription/clore'))
	  $app->fin = $m->get('inscription/fin');
	$app->save();

	$message = $i->getFullname(false, false)." inscrit.";
	$this->controller->logger->info($message);
	$this->controller->_helper->Flash->info($message);
	$db->commit();
      }
      catch (Exception $e) {
	$db->rollBack();
	throw $e;
      }

      if ($m->get('inscription/continuer'))
	$this->controller->redirectSimple();
      else
	$this->controller->redirectSimple('effectifs');
    }

    $parente = $this->unite->findParentUnites();
    return array('unite' => $this->unite,
		 /* récursion=0 : uniquement la maîtrise */
		 'apps' => $u->findAppartenances($a, 0),
		 'parente' => $parente,
		 'apps_parente' => $parente ? $parente->findAppartenances($a, 0) : array(),
		 'individu' => $individu,
		 'cv' => $individu ? $individu->findAppartenances() : null,
		 'app_active' => $app_active,
		 'model' => $pm);
  }
}
