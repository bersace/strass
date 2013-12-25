<?php

require_once 'Strass/Unites.php';
require_once 'Strass/Activites.php';
require_once 'Strass/Formation.php';
require_once 'Strass/Progression.php';

class InscriptionController extends Strass_Controller_Action
{
	function indexAction()
	{
		$this->redirectSimple('index', 'unites');
	}

	function nouveauAction()
	{
		$u = $this->view->unite = $this->_helper->Unite();
		$ind = $this->view->individu = $this->_helper->Individu->param(false);
		$annee = $this->_helper->Annee(false);

		$this->assert(null, $u, 'inscrire',
			      "Vous n'avez pas le droit d'inscrire un membre dans cette unité.");

		$this->metas(array('DC.Title' => 'Inscrire dans '.$u->getFullname()));

		$t = $u->findParentTypesUnite();

		$s = $t->sexe;
		$sexes = array('h' => 'Masculin', 'f' => 'Féminin');
		switch ($s) {
		case 'h':
			unset($sexes['f']);
			break;
		case 'f':
			unset($sexes['h']);
			break;
		}
    
    
		if ($annee) {
			// Lister les poste uniques occupés
			$s = $u->getTable()->select()
				// poste occupé
				->where('debut < ?', $annee.'-12-31')
				->where('fin IS NULL or fin > ?', ($annee+1).'-01-01')
				// le chef est toujours unique,
				// ainsi que les roles des unités terminales
				// de mineur (patrouille, sizaine, etc.)
				->where("role = 'chef'".
					" OR "."(".(intval($u->isTerminale()).
						    " AND ".
						    intval($u->findParentTypesUnite()->age_min < 17)).
					")");

			$as = $u->findAppartenances($s);
		}
		else
			$as = array();

		$occupes = array();
		foreach($as as $a)
			array_push($occupes, $a->role);

		// listage des roles inoccupés.
		$r = $t->findRoles();
		$roles = array();
		$preselected_role = null;
		foreach($r as $role) {
			if (!$preselected_role && !in_array($role->id, $occupes))
				$preselected_role = $role->id;
			$roles[$role->id] = ucfirst($role->titre);
		}
		if (!$preselected_role)
			$preselected_role = key($roles);
    
		$values = $roles;
		foreach($as as $app) {
			unset($values[$app->role]);
		}
    

		if (!count($values)
		    && in_array($t->id,
				array('patrouille', 'equipe', 'sizloup',
				      'sizjeannette'))) {
			throw new
				Strass_Controller_Action_Exception("L'unité est complète pour l'année ".$annee." !");
		}

    

		$min = $u->findParentTypesUnite()->age_min;

		$this->view->model = $m = new Wtk_Form_Model('nouveau');
		if (!$ind) {
			$m->addString('prenom', "Prénom");
			$m->addString('nom', "Nom");
			$naissance = ($annee-$min).'-01-01';
			$m->addDate('naissance', "Date de naissance", $naissance, "%Y-%m-%d");
			$m->addEnum('sexe', "Sexe", key($sexes), $sexes);
			if ($this->assert(null, null, 'totem')) {
				$m->addString('totem', 'Totem');
			}
			$m->addString('adelec', "Adélec");
			$m->addString('fixe', "Téléphone fixe");
			$m->addString('portable', "Téléphone portable");
			$m->addConstraintRequired('prenom');
			$m->addConstraintRequired('nom');
		}

		$g = $m->addGroup('appartenance');

		$g->addEnum('role', "Role", $preselected_role, $roles);

		$annee = $this->_getParam('annee');
		$annee = $annee ? $annee : date('Y');

		$g->addDate('debut', "Début effectif", $annee."-10-08");
		$r = $g->addBool('accompli', "Cette personne n'a plus ce role", (bool) $this->_getParam('annee'));
		$i = $g->addDate('fin', "Fin du mandat", ($annee+1)."-10-08");

		$m->addConstraintDepends($i, $r);

		$m->addNewSubmission('nouveau', 'Inscrire');

		if ($m->validate()) {
			$db = Zend_Registry::get('db');
			$db->beginTransaction();
			try {
				$data = $m->get();
				if (!$ind) {
					unset($data['appartenance']);
					$indid = $data['id'] = wtk_strtoid($data['prenom'].' '.$data['nom']);
					$individus = new Individus();
					$individus->insert($data);
					$ind = $individus->find($indid)->current();
				}
				else {
					$indid = $ind->id;
				}

				$data = array_merge(array('individu'	=> $indid,
							  'unite'    	=> $u->id,
							  'type'	=> $t->id),
						    array('debut'	=> $m->get('appartenance/debut'),
							  'role'	=> $m->get('appartenance/role')));
				if ($m->get('appartenance/accompli'))
					$data['fin'] = $m->get('appartenance/fin');
				$appartenances = new Appartenances();
				$appartenances->insert($data);

				$this->_helper->Log("Nouvelle inscription", array($ind, $u),
						    $this->_helper->Url('voir', 'individus', null, array('individu' => $indid)),
						    (string) $ind);

				$db->commit();

				$this->redirectSimple('index', 'unites', null,
						      array('unite' => $u->id),
						      true);
			}
			catch (Exception $e) {
				$db->rollBack();
				throw $e;
			}
		}
    
		$this->branche->append('Nouveau membre');
	}

	function desinscrireAction()
	{
		$i = $this->_helper->Individu();
		$this->assert(null, $i, 'desinscrire',
			      "Vous n'avez pas le droit de désinscrire cet individu.");

		$this->metas(array('DC.Title' => 'Désinscrire '.$i->getFullname()));

		$m = new Wtk_Form_Model('desinscrire');
		$m->addBool('confirmer',
			    "Je confirme la destruction de toute informations relative à ".$i->getFullName().".",
			    false);
		$m->addNewSubmission('continuer', 'Continuer');

		if ($m->validate()) {
			if ($m->get('confirmer')) {
				$db = $i->getTable()->getAdapter();
				$db->beginTransaction();
				try {
					$u = $i->findParentUsers();
					if ($u) {
						$u->delete();
					}
					$nom = $i->getFullName();
					$i->delete();
					$this->_helper->Log("Désincription de ".$nom, array(),
							    $this->_helper->Url('index', 'unites'), "Unites");
					$db->commit();
					$this->redirectSimple('index', 'unites');
				}
				catch (Exception $e) {
					$db->rollBack();
					throw $e;
				}
			}
			else {
				$this->redirectSimple('voir', 'individus', null,
						      array('individu' => $i->id));
			}
		}

		$this->view->individu = $i;
		$this->view->model = $m;
	}

	function administrerAction()
	{
		$this->view->individu = $individu = $this->_helper->Individu();

		$this->assert(null, $individu, 'admin',
			      "Vous n'avez pas le droit d'administrer ".
			      "l'inscription de cet individu.");

		$this->metas(array('DC.Title' => 'Administrer '.$individu->getFullname()));

		// Éditer les appartenances
		$this->view->apps = $m = new Wtk_Form_Model('apps');

		$tu = new Unites();
		$us = $tu->fetchAll(null);
		$eu = array();
		foreach($us as $u)
			$eu[$u->id] = wtk_ucfirst($u->getFullName());

		$tr = new Roles();
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

		$as = $individu->findAppartenances(null, 'debut DESC');
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
						    $this->_helper->Url('voir', 'individus', null, array('individu' => $individu->id)),
						    (string) $individu);

				$db->commit();
				$this->redirectSimple('voir', 'individus', null, array('individu' => $individu->id));
			}
			catch (Exception $e) {
				$db->rollBack();
				throw $e;
			}
		}


		// Éditer la progression
		$this->view->progression = $m = new Wtk_Form_Model('progression');

		$etapes = $individu->getEtapesDisponibles(true);
		$ee = array();
		foreach($etapes as $e)
			$ee[$e->id] = $e->titre;
		$t = $m->addTable('progression', "Progression",
				  array('etape' 	=> array('Enum', "Étape", $ee),
					'lieu'		=> array('String', "Lieu"),
					'date'		=> array('Date', "Date"),
					'details'	=> array('String', "Détails")));

		$s = $etapes->getTable()->select()->order('date ASC');
		$ps = $individu->findProgression($s);
		foreach($ps as $p)
			$t->addRow($p->etape, $p->lieu, $p->date, $p->details);

		$m->addNewSubmission('enregistrer', 'Enregistrer');

		if ($m->validate()) {
			$db = $individu->getTable()->getAdapter();
			$db->beginTransaction();
			try {
				foreach($ps as $p)
					$p->delete();
				
				$te = new Etape;
				$tp = new Progression;
				foreach($t as $p) {
					$e = $te->fetchAll("id = '".$p->get('etape')."'".
							   " AND ".
							   "(sexe = 'm' OR sexe = '".$individu->sexe."')")->current();
					$tuple = $p->get();
					$tuple['individu'] = $individu->id;
					$tuple['sexe'] = $e->sexe;
					$tp->insert($tuple);
				}

				$this->_helper->Log("Progression édité par un admin", array($individu),
						    $this->_helper->Url('voir', 'individus', null, array('individu' => $individu->id)),
						    (string) $individu);

				$db->commit();
				$this->redirectSimple('voir', 'individus', null, array('individu' => $individu->id));
			}
			catch (Exception $e) {
				$db->rollBack();
				throw $e;
			}
		}

		// Éditer la formation

		$this->view->formation = $m = new Wtk_Form_Model('formation');
		$diplomes = $individu->getDiplomesDisponibles(true);
		$ed = array();
		foreach($diplomes as $d)
			$ed[$d->id.'#'.$d->branche] = $d->accr;

		$t = $m->addTable('formation', "Formation",
				  array('diplome' =>	array('Enum', "Diplôme", $ed),
					'date' =>	array('Date', "Date")));
		$fs = $individu->findFormation();
		foreach($fs as $f)
			$t->addRow($f->diplome.'#'.$f->branche, $f->date);

		$m->addNewSubmission('enregistrer', 'Enregistrer');

		if ($m->validate()) {
			$db = $individu->getTable()->getAdapter();
			$db->beginTransaction();
			try {
				foreach($fs as $f)
					$f->delete();

				$tf = new Formation;

				foreach($t as $r) {
					list($diplome, $branche) = explode('#', $r->diplome);
					$tuple['diplome'] = $diplome;
					$tuple['branche'] = (string)$branche;
					$tuple['date']	= $r->date;
					$tuple['individu'] = $individu->id;
					$tf->insert($tuple);
				}

				$this->_helper->Log("Formation édité par un admin", array($individu),
						    $this->_helper->Url('voir', 'individus', null, array('individu' => $individu->id)),
						    (string) $individu);

				$db->commit();
				$this->redirectSimple('voir', 'individus', null, array('individu' => $individu->id));
			}
			catch (Exception $e) {
				$db->rollBack();
				throw $e;
			}
		}
	}

	function editerAction()
	{
	  $this->view->individu = $individu = $this->_helper->Individu();
	  $this->assert(null, $individu, 'editer',
			"Vous n'avez pas le droit d'éditer l'inscription de cet individu");

	  $this->metas(array('DC.Title' => "Évoluer l'inscription de ".$individu->getFullname()));
		
	  $this->view->model = $m = new Wtk_Form_Model('editer');
	  $m->addNewSubmission('valider', 'Valider');

	  $ttu = new TypesUnite();
	  $db = $ttu->getAdapter();
	  $tu = new Unites();
	  $age = $individu->getAge();
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
								"Aucune unité ne peut l'indexlir !");

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
				  $this->_helper->Url('voir', 'individus', null, array('individu' => $individu->slug)),
				  (string) $individu);
 
	      $db->commit();
	      $this->redirectUrl(array('controller' => 'individus',
				       'action'	=> 'voir',
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

		$m = new Wtk_Form_Model('editer');
		$m->addNewSubmission('valider', "Valider");

		$ttu = new TypesUnite();
		$db = $ttu->getAdapter();
		$tu = new Unites();
		$tr = new Roles();
		$age = $ind->getAge();

		// ajouter une étape de l'historique.
		// sélectionner les unités où l'individu a pu participé :
		$where = $db->quoteInto('age_min < ? AND age_min <> 0', $age);
		$where.= ' AND ';
		$where.= $db->quoteInto('(sexe = ? OR sexe = \'m\')', $ind->sexe);
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
				$action = $m->get('appartenance/continuer') ? 'historique' : 'voir';

				$this->_helper->Log("Historique complété", array($ind, $unite),
						    $this->_helper->Url('voir', 'individus', null, array('individu' => $ind->id)),
						    (string) $ind);

				$db->commit();
				$this->redirectUrl(array('controller'	=> $action == 'voir' ? 'individus' : 'inscription',
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
