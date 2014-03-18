<?php

class Strass_Pages_Model_UniteInscrire extends Strass_Pages_Model_Historique
{
  function __construct($controller, $unite, $annee)
  {
    parent::__construct($unite, $annee);
    $this->controller = $controller;
  }

  function fetch($annee = NULL)
  {
    $u = $this->unite;
    $a = $annee;

    $ti = new Individus;
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
    $g->addEnum('individu', 'Individu', null, $enum);
    $roles = $u->findParentTypesUnite()->findRoles();
    $enum = array();
    foreach ($roles as $role) {
      $enum[$role->id.'__'] = $role->titre;
      foreach ($role->findTitres() as $titre) {
	$enum[$role->id.'__'.$titre->nom] = $titre->nom;
      }
    }
    $default = $u->findRolesCandidats($a)->current();
    $g->addEnum('role', 'Rôle', $default ? $default->id.'__' : null, $enum);
    $g->addDate('debut', 'Début', $a.'-10-08');
    $i0 = $g->addBool('clore', 'Clore', false);
    $i1 = $g->addDate('fin', 'Fin', ($a+1).'-10-08');
    $m->addConstraintDepends($i1, $i0);

    /* Enregistrement d'un nouvel individu */
    $g = $m->addGroup('fiche');
    $g->addString('prenom', 'Prénom');
    $g->addString('nom', 'Nom');
    $tu = $u->findParentTypesUnite();
    if ($tu->sexe == 'm')
      $g->addEnum('sexe', 'Sexe', null, array('h' => 'Masculin', 'f' => 'Féminin'));
    else
      $g->addString('sexe', null, $tu->sexe)->setReadonly();

    $g->addDate('naissance', 'Date de naissance', ($a - $tu->age_min) . '-01-01');

    $page = $pm->partialValidate();

    if ($m->get('inscription/individu') != '$$nouveau$$' && $page == 'fiche') {
      /* Sauter l'étape fiche si l'individu est déjà en base */
      if ($m->sent_submission->id == 'continuer')
	$pm->gotoEnd();
      else if ($m->sent_submission->id == 'precedent')
	$pm->gotoPage('inscription');
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
	  $i->naissance = $m->get('fiche/naissance');
	  $i->slug = $i->getTable()->createSlug(wtk_strtoid($i->getFullname(false, false)));
	  $i->save();
	}
	else {
	  $i = $ti->findOne($m->get('inscription/individu'));
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

      $this->controller->redirectSimple('effectifs');
    }

    return array('unite' => $this->unite,
		 /* récursion=0 : uniquement la maîtrise */
		 'apps' => $u->findAppartenances($a, 0),
		 'model' => $pm);
  }
}
