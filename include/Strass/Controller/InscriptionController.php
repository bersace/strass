<?php

require_once 'Strass/Unites.php';
require_once 'Strass/Activites.php';

class InscriptionController extends Strass_Controller_Action
{
  function indexAction()
  {
    $this->redirectSimple('index', 'unites');
  }

  function administrerAction()
  {
    $this->view->individu = $individu = $this->_helper->Individu();

    $this->assert(null, $individu, 'admin',
		  "Vous n'avez pas le droit d'administrer ".
		  "l'inscription de cet individu.");

    $this->metas(array('DC.Title' => 'Administrer '.$individu->getFullname()));

    $this->actions->append("Éditer la fiche",
			   array('controller' => 'individus', 'action' => 'editer'),
			   array(null, $individu));

    $as = $individu->findAppartenances(null, 'debut DESC');
    if (!$as->count()) {
      $this->view->apps = null;
    }
    else {
      // Éditer les appartenances
      $this->view->apps = $m = new Wtk_Form_Model('apps');

      $tu = new Unites;
      $us = $tu->fetchAll(null);
      $eu = array();
      foreach($us as $u)
	$eu[$u->id] = wtk_ucfirst($u->getFullName());

      $tr = new Roles;
      $rs = $tr->fetchAll(null, 'ordre');
      $er = array();
      foreach($rs as $r)
	$er[$r->id] = $r->id;

      $t = $m->addTable('appartenances', "Appartenances",
			array('unite'	=> array('Enum',	'Unité',$eu),
			      'role'	=> array('Enum',	'Role',	$er),
			      'debut'	=> array('Date',	'Début'),
			      'clore'	=> array('Bool',	'Clore', false),
			      'fin'	=> array('Date',	'Fin')));

      foreach($as as $a)
	$t->addRow($a->unite, $a->role, $a->debut, (bool) $a->fin, $a->fin);

      $m->addNewSubmission('enregistrer', 'Enregistrer');

      if ($m->validate()) {
	$db = $individu->getTable()->getAdapter();
	$db->beginTransaction();
	try {
	  foreach($as as $a)
	    $a->delete();

	  foreach($t as $app) {
	    $u = $tu->find($app->get('unite'))->current();
	    $data = array('individu'	=> $individu->id,
			  'unite'		=> $u->id,
			  'type'		=> $u->type,
			  'role'		=> $app->role,
			  'debut'		=> $app->debut);
	    if ($app->get('clore'))
	      $data['fin'] = $app->fin;
	    else
	      $data['fin'] = null;
	    $as->getTable()->insert($data);
	  }

	  $this->_helper->Log("Inscription édité par un admin", array($individu),
			      $this->_helper->Url('fiche', 'individus', null, array('individu' => $individu->id)),
			      (string) $individu);

	  $db->commit();
	  $this->redirectSimple('fiche', 'individus', null, array('individu' => $individu->id));
	}
	catch (Exception $e) {
	  $db->rollBack();
	  throw $e;
	}
      }
    }
  }

  function editerAction()
  {
    $this->view->individu = $individu = $this->_helper->Individu();
    $this->assert(null, $individu, 'editer',
		  "Vous n'avez pas le droit d'éditer l'inscription de cet individu");

    $this->metas(array('DC.Title' => "Évoluer l'inscription de ".$individu->getFullname()));

    $this->actions->append("Éditer la fiche",
			   array('controller' => 'individus', 'action' => 'editer'),
			   array(null, $individu));

    $this->view->model = $m = new Wtk_Form_Model('editer');
    $m->addNewSubmission('valider', 'Valider');

    $ttu = new TypesUnite;
    $db = $ttu->getAdapter();
    $tu = new Unites;
    $age = $individu->getAge();
    if (is_null($age))
      throw new Strass_Controller_Action_Exception_Notice("La date de naissance de ".
							  $individu->getFullName()." est inconnue.");
    $ta = new Appartenances();
    $s = $ta->select()->where('fin IS NULL');
    $apps = $individu->findAppartenances($s);
    $curr = $apps->current();

    // sélectionner les types auxquel peut participer l'individu
    $where = $db->quoteInto('age_min <= ? AND ? < age_max', $age);
    $where.= $db->quoteInto(' AND (sexe = ? OR sexe = \'m\')', $individu->sexe);
    $types = $ttu->fetchAll($where);

    $wheres = array();
    foreach($types as $type) {
      $wheres[] = $db->quoteInto('type = ?', $type->id);
    }
    $where = implode(' OR ', $wheres);
    $unites = $tu->fetchAll($where);

    // éditer l'inscription courante
    if ($curr) {
      $g = $m->addGroup('actuel', "Actuellement");
      $i = $g->addDate('fin', "N'est plus ".$curr->findParentRoles()->getAccronyme()." depuis");
      if ($unites->count())
	$i = $g->addBool('promouvoir',
			 "Promouvoir à un poste supérieur ou inscrire dans une autre unité",
			 true);
    }

    $m->validate();

    // si le groupe dispose d'unité pouvant indexlir l'individu,
    // les proposer.
    if (($m->get('actuel/promouvoir') || !$curr) && $unites->count()) {

      // NOUVELLE UNITÉ
      $g = $m->addGroup('unite', "Unité");

      $enum = array();
      $selected = NULL;
      foreach($unites as $unite) {
	$enum[$unite->id] = ucfirst($unite->getFullName());
	if ($curr && $unite->id == $curr->unite) {
	  $selected = $curr->findParentUnites();
	}
      }

      if (!$selected) {
	$selected = $tu->find(key($enum))->current();
      }

      $i = $g->addEnum('unite', "Nouvelle unité", $selected->id, $enum);

      $m->validate();

      // ROLE
      $g = $m->addGroup('appartenance');
      $type = $tu->find($m->get('unite/unite'))->current()->findParentTypesUnite();
      // configurer l'inscription dans la nouvelle unité.
      $enum = array();
      $roles = $type->findRoles();
      foreach($roles as $role) {
	$enum[$role->id] = wtk_ucfirst($role->titre);
      }
      $selected = $curr ? $curr->role : key($enum);
      $i = $g->addEnum('role', "Nouveau role", $selected, $enum);

      if (!$m->get('actuel/fin'))
	$g->addDate('debut', "Début effectif");
    }

    $this->view->model = new Wtk_Pages_Model_Form($m);

    if (!$this->view->model->pagesCount())
      throw new Strass_Controller_Action_Exception_Notice("Impossible d'inscrire cet individu ".
							  "dans notre groupe. ".
							  "Aucune unité ne peut l'accueillir !");

    // VALIDATION
    if ($m->validate()) {
      $db = $individu->getTable()->getAdapter();
      $db->beginTransaction();
      try {
	// appartenances
	if ($m->get('actuel')) {
	  $curr->fin = $m->get('actuel/fin');
	  $curr->save();
	}

	if ($m->get('actuel/promouvoir') || !$m->get('actuel')) {
	  $u = $tu->find($m->get('unite/unite'))->current();
	  $debut = $m->get('appartenance/debut');
	  $debut = $debut ? $debut : $m->get('actuel/fin');
	  $data= array('individu'	=> $individu->slug,
		       'unite'	=> $u->id,
		       'debut'	=> $debut,
		       'role'	=> $m->get('appartenance/role'),
		       'type'	=> $u->type
		       );
	  $ta->insert($data);
	}

	$this->_helper->Log("Inscription modifiée", array($individu),
			    $this->_helper->Url('fiche', 'individus', null, array('individu' => $individu->slug)),
			    (string) $individu);

	$db->commit();
	$this->redirectUrl(array('controller' => 'individus',
				 'action'	=> 'fiche',
				 'individu' => $individu->slug),
			   null, false);
      }
      catch (Exception $e) {
	$db->rollBack();
	throw $e;
      }
    }

    $this->actions->append("Administrer",
			   array('controller'	=> 'inscription',
				 'action'	=> 'administrer'),
			   array(null, $individu));
  }

  function historiqueAction()
  {
    $ind = $this->_helper->Individu();

    $this->assert(null, $ind, 'historique',
		  "Vous n'avez pas le droit d'éditer l'historique du scoutisme de ".$ind->getFullName());

    $this->actions->append("Éditer la fiche",
			   array('controller' => 'individus', 'action' => 'editer'),
			   array(null, $ind));

    $m = new Wtk_Form_Model('editer');
    $m->addNewSubmission('valider', "Valider");

    $ttu = new TypesUnite;
    $db = $ttu->getAdapter();
    $tu = new Unites;
    $tr = new Roles;
    $age = $ind->getAge();
    if (is_null($age))
      throw new Strass_Controller_Action_Exception_Notice("La date de naissance de ".
							  $ind->getFullName()." est inconnue.");


    // ajouter une étape de l'historique.
    // sélectionner les unités où l'individu a pu participé :
    $where = $db->quoteInto('age_min < ? AND age_min <> 0', $age);
    $where.= ' AND ';
    $where.= $db->quoteInto("(sexe = ? OR sexe = 'm')", $ind->sexe);
    $types = $ttu->fetchAll($where);

    if ($types->count()) {
      // PAGE 0
      $g = $m->addGroup('unite');

      // unité
      $where = array();
      foreach($types as $type) {
	$where[] = $db->quoteInto('type = ?', $type->id);
      }
      $unites = $tu->fetchAll(implode(' OR ', $where));

      $enum = array();
      foreach($unites as $unite) {
	$enum[$unite->id] = ucfirst($unite->getFullName());
      }

      $g->addEnum('unite', 'Unité', key($enum), $enum);


      $m->validate();
      $unite = $tu->find($m->get('unite/unite'))->current();

      // PAGE 1
      $g = $m->addGroup('appartenance');
      // role
      $where = $db->quoteInto('type = ?', $unite->type);
      $roles = $tr->fetchAll($where, 'ordre');
      $enum = array();
      foreach ($roles as $role) {
	$enum[$role->id] = ucfirst($role->titre);
      }
      $amin = $unite->findParentTypesUnite()->age_min;
      $nt = strtotime(date('Y', strtotime($ind->naissance)).'-10-08');
      $g->addEnum('role', 'Poste', key($enum), $enum);
      $dt = $nt + $amin * 365 * 24 * 60 * 60;
      $g->addDate('debut', 'Début', strftime('%Y-%m-%d', $dt));
      $ft = $nt + ($amin+1) * 365 * 24 * 60 * 60;
      $g->addDate('fin', 'Fin', strftime('%Y-%m-%d', $ft));
      $g->addBool('continuer',
		  $ind->getFullName()." a fait partie d'autres unités et/ou à d'autres postes, continuer.",
		  true);
    }

    $this->view->model = new Wtk_Pages_Model_Form($m);

    if ($m->validate()) {
      $ta = new Appartenances();
      $db = $ta->getAdapter();
      $db->beginTransaction();
      try {
	$data = $m->get();
	$tuple = array('individu'	=> $ind->id,
		       'unite'	=> $unite->id,
		       'role'	=> $data['appartenance']['role'],
		       'type'	=> $unite->type,
		       'debut'	=> $data['appartenance']['debut'],
		       'fin'	=> $data['appartenance']['fin']);
	$ta->insert($tuple);
	$action = $m->get('appartenance/continuer') ? 'historique' : 'fiche';

	$this->_helper->Log("Historique complété", array($ind, $unite),
			    $this->_helper->Url('fiche', 'individus', null, array('individu' => $ind->id)),
			    (string) $ind);

	$db->commit();
	$this->redirectUrl(array('controller'	=> $action == 'fiche' ? 'individus' : 'inscription',
				 'action'	=> $action,
				 'individu'	=> $ind->id));
      }
      catch(Exception $e) {
	$db->rollBack();
	throw $e;
      }
    }


    $this->view->individu = $ind;

    $this->metas(array('DC.Title' => "Compléter l'historique de ".$ind->getFullname()));

    $this->actions->append("Administrer",
			   array('controller'	=> 'inscription',
				 'action'		=> 'administrer'),
			   array(null, $ind));
  }
}
