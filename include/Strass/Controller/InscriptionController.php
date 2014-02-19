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
}
