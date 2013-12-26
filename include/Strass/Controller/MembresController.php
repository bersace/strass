<?php

require_once 'Strass/Unites.php';

class MembresController extends Strass_Controller_Action implements Zend_Acl_Resource_Interface
{
  static $cotisation = 'private/cotisation.wiki';

	function init()
	{
		parent::init();
		$acl = Zend_Registry::get('acl');
		if (!$acl->has($this))
			$acl->add($this);
		$racine = $this->_helper->UniteRacine();
		$acl->allow($racine->getRoleRoleId('chef'), $this);
		$acl->allow('individus', $this, 'voir');
	}

	function getResourceID()
	{
		return 'membres';
	}

	function indexAction()
	{
		$this->assert(null, $this, 'voir', "Vous devez être identifié.");
		$this->redirectSimple('index', 'index');
	}

	function editerAction()
	{
		$this->assert(null, $this, 'editer',
			      "Vous n'avez pas le droit d'éditer la fiche d'inscription");
		$this->metas(array('DC.Title' => "Éditer la fiche d'inscription",
				   'DC.Subject' => 'livre,or'));

		$this->view->model = $m = new Wtk_Form_Model('fiche');
		$c = is_readable($this::$cotisation) ? file_get_contents($this::$cotisation) : '';
		$m->addString('cotisation', "Cotisation", $c);
		$conf = $this->_helper->Config('strass');
		$m->addBool('scoutisme', "Demander l'historique du scoutisme de la personne dans le groupe ?", $conf->inscription->scoutisme);
		$m->addNewSubmission('valider', 'Valider');

		if ($m->validate()) {
			file_put_contents($c, $m->cotisation);
			file_put_contents($e, $m->envoi);
			$conf->inscription->scoutisme = $m->scoutisme;
			$conf->write();

			$this->_helper->Log("Formulaire d'inscription édité", array(),
					    $this->_helper->Url('inscription', 'membres'),
					    "Formulaire d'inscription");
			$this->redirectSimple('inscription');
		}
	}


	function inscriptionAction()
	{
		$this->metas(array('DC.Title' => "Fiche d'inscription",
				   'DC.Subject' => 'livre,or'));
		$conf = $this->_helper->Config('strass')->inscription;

		$m = new Wtk_Form_Model('inscription');
		$m->addNewSubmission('inscrire', 'Inscrire');

		$g = $m->addGroup('fiche');

		// ÉTAT CIVIL
		$gg = $g->addGroup('etat-civil');
		$i = $gg->addString('prenom', "Prénom");
		$m->addConstraintRequired($i);

		$i = $gg->addString('nom', "Nom");
		$m->addConstraintRequired($i);

		// listage des sexes acceuillis
		$enum = array('h' => 'Masculin', 'f' => 'Féminin');
		$ttu = new TypesUnite();
		$s = $ttu->select()
			->distinct()
			->from('types_unite', array('sex' => 'sexe'))
			->distinct()
			->join('unites',
			       'unites.type = types_unite.id',
			       array());
		$default = null;
		$tuples = $ttu->fetchSelect($s);
		$sexes = array();
		foreach($tuples as $tuple) {
			$sexe = $tuple->sex;
			switch($sexe) {
			case 'm':
				$default = 'h';
				$sexes = array('h', 'f');
				break;
			case 'h':
			case 'f':
				if (!$default)
					$default = $sexe;
				array_push($sexes, $sexe);
				break;
			}
		}
		$keys = array_flip($sexes);
		$enum = array_intersect_key($enum, $keys);

		$i = $gg->addEnum('sexe', 'Sexe', $default, $enum);
		$m->addConstraintRequired($i);

		$i = $gg->addDate('naissance', "Date de naissance", 0);
		$m->addConstraintRequired($i);
		$gg->addString('situation', "Situation");


		// CONTACT
		$gg = $g->addGroup('contact');
		$gg->addString('adresse', "Adresse");
		$gg->addString('fixe', "Fixe");
		$gg->addString('portable', "Portable");

		$i = $gg->addString('adelec', "Adresse électronique");
		$m->addConstraintMatch($i,
				       '/^[[:alnum:]\._+-]{3,}@[[:alnum:]\._-]{3,}\.[[:alnum:]]{2,6}$/');
		$m->addConstraintRequired($i);

		// PROGRESSION
		$gg = $g->addGroup('progression');
		$gg->addString('origine', "Unité d'origine");

		$td = new Diplomes();
		$ds = $td->fetchAll();
		$enum = array();
		foreach($ds as $diplome)
			$enum[$diplome->id] = $diplome->accr;

		$gg->addEnum('formation', "Diplômes", null, $enum, true); // multiple
		
		$te = new Etape();
		$es = $te->fetchAll("titre NOT LIKE '%Badge%'");
		$enum = array();
		foreach($es as $etape)
			$enum[$etape->id] = $etape->titre;

		$gg->addEnum('progression', "État scout", null, $enum, true); // multiple
		$gg->addString('perespi', "Père Spi");

		// COMPTE
		$g = $m->addGroup('compte');
		$i = $g->addString('identifiant', "Identifiant");
		$m->addConstraintRequired($i);
		// contrainte : pas d'identifiant unique
		$tu = new Users();
		$us = $tu->fetchAll();
		$forbidden = array();
		foreach($us as $u)
			array_push($forbidden, $u->username);

		$m->addConstraintForbid($i, $forbidden, "Cet %s est déjà utilisé !");

		

		$i = $g->addString('code', "Mot de passe");
		$m->addConstraintLength($i, 6);

		// MODÉRATION
		$g = $m->addGroup('moderation');
		$i = $g->addString('message', "Message à l'administrateur");

		if ($conf->scoutisme) {
			$t = $g->addTable('participations',
					  "Votre scoutisme dans notre groupe",
					  array('unite' => array ('String', 'Unité'),
						'poste' => array ('String', 'Poste'),
						'debut' => array ('Date', 'Début', '%Y/%m/%d'),
						'fin' => array ('Date', 'Fin', '%Y/%m/%d')));
			$t->addRow('', '', strtotime((intval(date('Y')) - 1).'-10-01'),
				   strtotime((intval(date('Y'))).'-10-01'));
		}

		$this->view->model = new Wtk_Pages_Model_Form($m);

		$this->view->cotisation = file_get_contents($this::$cotisation);
		$racine = $this->_helper->UniteRacine();
		$app = $racine->findAppartenances("role = 'chef' AND fin IS NULL")->current();
		$chef = $app->findParentIndividus();
		$this->view->envoi =
			"À adresser rapidement au ".$app->findParentRoles()->titre." :\n\n".
			$chef->getFullname(false)."\n".
			$chef->adresse."\n";

		if ($this->view->model->isValid()) {
			$t = new Inscriptions();
			$db = $t->getAdapter();
			$db->beginTransaction();
			try {
				$private = $m->get();
				$config = $this->_helper->Config('strass')->site;
				$realm = (string) $config->realm;
				$private = array('username' => trim($private['compte']['identifiant']),
					      'password' => md5($private['compte']['code']),
					      'ha1' => Users::ha1(trim($private['compte']['identifiant']),
								  $realm,
								  $private['compte']['code']),
					      'message' => $private['moderation']['message']."\n");

				$private['scoutisme'] = '';
				$private+= $m->get('fiche/etat-civil');
				$private+= $m->get('fiche/contact');
				$private+= $m->get('fiche/progression');
				$private['progression'] = var_export($private['progression'], true);
				$private['formation'] = var_export($private['formation'], true);
				if ($conf->scoutisme) {
					foreach($m->get('moderation/participations') as $row) {
						if ($row['poste'] && $row['unite']) {
							$private['scoutisme'].=
								"|| ".$row['unite']." || ".$row['poste']." || ".
								$row['debut']." || ".$row['fin']."||\n";
						}
					}
				}
				$t->insert($private);
				$this->sendInscriptionMail($private, $conf->scoutisme ? $m->get('moderation/participations') : null);
				$this->_helper->Log("Nouvelle inscription",
						    array('identifiant' => trim($private['username']),
							  'prenom' => $private['prenom'],
							  'nom' => $private['nom'],
							  'adelec' => $private['adelec']),
						    $this->_helper->Url('inscriptions', 'membres'), "Inscriptions");
				$db->commit();
				//$this->redirectSimple('index', 'index');
			}
			catch(Exception $e) {
				$db->rollBack();
				throw $e;
			}
		}

		$this->branche->append("Fiche d'inscription");
		$this->actions->append("Éditer la fiche d'inscription",
				       array('action' => 'editer'),
				       array(null, $this));
	}

	function inscriptionsAction()
	{
		$t = new Inscriptions();
		$this->assert(null, $t, 'voir',
			      "Vous n'avez pas le droit de voir les inscriptions en attente.");
		$this->metas(array('DC.Title' => "Inscriptions en attente",
				   'DC.Subject' => 'livre,or'));

		$is = $t->fetchAll();

		$this->view->inscriptions = new Wtk_Pages_Model_Iterator($is);
	}

	function validerAction()
	{
		$t = new Inscriptions();
		$this->assert(null, $t, 'valider',
			      "Vous n'avez pas le droit de valider les inscriptions en attente.");
		$this->metas(array('DC.Title' => "Valider une inscription",
				   'DC.Subject' => 'inscription'));

		$id = $this->_getParam('id');
		if ($id)
			$ins = $t->find($id)->current();
		else
			$ins = $t->fetchAll()->current();

		if (!$ins)
			throw new Strass_Controller_Action_Exception_Notice("Aucune inscription à valider.");

		$tind = new Individus();
		$id = wtk_strtoid($ins->prenom.' '.$ins->nom);
		$ind = $tind->find($id)->current();
        
		$m = new Wtk_Form_Model('valider');
		$m->addString('nom', 'Nom', $ins->nom);
		$m->addString('prenom', 'Prénom', $ins->prenom);
		$m->addString('username', 'Identifiant', trim($ins->username));
		$m->addString('adelec', 'Adélec', $ins->adelec);
		// $v = $m->addBool('verdict', 'Accepter '.$ins->prenom.' '.$ins->nom.' ?', false);
		$v = $m->addEnum('verdict', 'Verdict',
				 'refuser',
				 array('refuser' => 'Refuser !',
				       'accepter' => 'Accepter !'));
		$m->addString('message', "Message à ".$ins->prenom." ".$ins->nom);

		if (!$ind || !$ind->getUnites()) {
			// unités susceptible d'indexlir le nouveau membre.
			$tu = new Unites();
			$s = $tu->select()
				->where("sexe = ? OR sexe = 'm'", $ins->sexe);
			$us = $tu->fetchSelect($s);
			$enum = array();
			foreach ($us as $u)
				$enum[$u->id] = wtk_ucfirst($u->getFullname());

			$i = $m->addEnum('unite', "Inscrire dans l'unité", key($enum), $enum);
			$m->addConstraintDepends($i, $v);
		}

		$m->addNewSubmission('valider', 'Valider');
        
		if ($m->validate()) {
			if ($m->get('verdict') == 'accepter') {
				$tu = new Users();
				$db = $tu->getAdapter();
				$db->beginTransaction();
				try {
					// TODO: regénérer la hash !!??
					$k = $tu->insert(array('username' => trim($m->username),
							       'password' => $ins->password,
							       'ha1' => $ins->ha1));
					$keys = array('naissance', 'situation', 'origine',
						      'adresse', 'fixe', 'portable', 'adelec',
						      'perespi');
					if ($ind) {
						// relier à une fiche existante
						$ind->username = $k;
						foreach($keys as $k)
							if ($ins->$k)
								$ind->$k = $ins->$k;

						$ind->fixe = $this->_helper->Telephone($ind->fixe);
						$ind->portable = $this->_helper->Telephone($ind->portable);

						$ind->save();
						$private = $ind->toArray();
					}
					else {
						// créer une nouvelle fiche.
						$private = array('username' => $k,
							      'id' => $id,
							      'nom' => $m->nom,
							      'prenom' => $m->prenom,
							      'sexe' => $ins->sexe);

						foreach($keys as $k)
							$private[$k] = $ins->$k;
						$private['fixe'] = $this->_helper->Telephone($ins->fixe);
						$private['portable'] = $this->_helper->Telephone($ins->portable);

						$key = $tind->insert($private);
						$ind = $tind->find($key)->current();
					}

					// progression
					$tp = new Progression();
					eval("\$progression = ".$ins->progression.";");
					foreach($progression as $etape) {
						try {
							// il arrive qu'une étape ai déjà été enregistrée !
							$tp->insert(array('individu' => $private['id'],
									  'sexe' => $private['sexe'], // à l'arrache !
									  'etape' => $etape));
						}catch(Exception $e) {}
					}

					$tf = new Formation();
					eval("\$formation = ".$ins->formation.";");
					foreach($formation as $diplome) {
						$td = new Diplomes();
						$s = $td->select()
							->where("id = ?", $diplome)
							->where("sexe = 'm' OR sexe = ?", $ind->sexe);
						$d = $td->fetchSelect($s);
						try {
							// Il arrive que la formation ai déjà été enregistrée.
							$tf->insert(array('individu' => $private['id'],
									  'diplome' => $diplome,
									  'branche' => (string)$d->branche)); // branche ???
						} catch(Exception $e) {}
					}

					$this->_helper->Log("Inscription acceptée", array($ind),
							    $this->_helper->Url('voir', 'individus', null,
										array('individu' => $ind->id)),
							    (string) $ind);

					$db->commit();
					// ne parler de la cotisation
					// qu'au nouveau membre sans
					// préinscription
					$this->sendValidationMail($private, $m->message, !$ind);
				}
				catch(Exception $e) {
					$db->rollBack();
					throw $e;
				}
			}
			else {
				$this->_helper->Log("Inscription refusée",
						    array('inscription' => $ins->toArray()),
						    $this->_helper->Url('inscriptions', 'membres'),
						    "Inscriptions");

				$this->sendValidationRefusMail($ins->toArray(),
							       $m->get('message'));
			}
			// détruire l'inscription
			$ins->delete();

			if ($m->verdict == 'refuser' || !$m->get('unite'))
				// pour valider les autres
				$this->redirectSimple('valider', 'membres', null,
						      array('precedent' => $private['id'],
							    'verdict' => $m->verdict),
						      true);
			else
				// Pour inscrire le nouvel inscrit dans une unité
				$this->redirectSimple('nouveau', 'inscription', null,
						      array('unite' => $m->get('unite'),
							    'individu' => $private['id']),
						      true);
		}

		$t = new Individus;
		$id = $this->_getParam('precedent');
		$this->view->verdict = $this->_getParam('verdict');
		if ($id)
			$this->view->precedent = $t->find($id)->current();
		else
			$this->view->precedent = null;

		$this->view->individu = $ind;
		$this->view->inscription = $ins;
		$this->view->model = $m;
	}

	function profilAction()
	{
	  $moi = Zend_Registry::get('user');
	  $id = $this->_getParam('individu');

	  if ($id) {
	    $t = new Individus();
	    $ind = $t->findBySlug($id);
	  }
	  else
	    $ind = $moi;

	  if (!$moi)
	    throw new Strass_Controller_Action_Exception_Notice("Vous n'être pas identifié.");

	  if (!$ind)
	    throw new Strass_Controller_Action_Exception
	      ("Aucun individu ne correspond à //".$id."//.");

	  $this->assert($moi, $ind, 'profil',
			"Vous n'avez pas le droit de modifier le profils de cet utilisateurs.");

	  $this->metas(array('DC.Title' => "Éditer l'utilisateur ".$ind->username));

	  $m = new Wtk_Form_Model('profil');
	  $g = $m->addGroup('mdp', "Change le mot de passe");
	  if (!$this->assert(null) || $moi->username == $ind->username)
	    $m->addConstraintRequired($g->addString('ancien', 'Ancien'));

	  $m->addConstraintRequired($g->addString('nouveau', 'Nouveau'));
	  $m->addConstraintRequired($g->addString('confirmation', "Confirmation"));
	  $m->addNewSubmission('valider', 'Valider');

	  $a = new Wtk_Form_Model('admin');
	  if ($this->assert($moi, $ind, 'admin') && $moi->username != $ind->username) {
	    $a->addBool('admin',
			"Accorder tout les privilèges sur le site à ".$ind->getFullName(),
			$ind->isAdmin());
	  }
	  $a->addNewSubmission('valider', 'Valider');

	  if ($m->validate()) {
	    $db = $ind->getTable()->getAdapter();
	    $db->beginTransaction();
	    try {
	      $mdp = $m->get('mdp');
	      $old = Individu::hashPassword($ind->username, $mdp['ancien']);
	      if (!$this->assert($moi, $ind, 'admin') || $ind->password != $old){
		throw new Wtk_Form_Model_Exception("Ancien mot de passe erroné.",
						   $m->getInstance('mdp/ancien'));
	      }

	      if ($mdp['nouveau'] != $mdp['confirmation']) {
		throw new
		  Wtk_Form_Model_Exception("Le mot de passe de confirmation n'est pas identique au nouveau proposé");
	      }

	      $config = $this->_helper->Config('strass')->site;
	      $ind->username = $ind->adelec;
	      $ind->password = Individu::hashPassword($ind->username, $mdp['nouveau']);
	      $ind->save();

	      $this->_helper->Log("Profil mis-à-jour", array($ind),
				  $this->_helper->Url('voir', 'individus', null,
						      array('individu' => $ind->id)),
				  (string) $ind);

	      $db->commit();
	      $this->redirectSimple('voir', 'individus', null,
				    array('individu' => $ind->slug),
				    true);
	    }
	    catch(Wtk_Form_Model_Exception $e) {
	      $db->rollBack();
	      $m->errors[] = $e;
	    }
	    catch(Exception $e) {
	      $db->rollBack();
				throw $e;
	    }
	  }

	  if ($a->validate()) {
	    $db = $t->getAdapter();
	    $db->beginTransaction();
	    try {
	      $ind->admin = $a->get('admin');
	      $ind->save();
	      $db->commit();
	    }
	    catch(Exception $e) {
	      $db->rollBack();
	      throw $e;
	    }
 
	    $this->redirectSimple('voir', 'individus', null,
				  array('individu' => $ind->id),
				  true);
	  }

	  $this->view->model = $m;
	  $this->view->admin = $a;
	}

	function listerAction()
	{
		$ti = new Individus;
		$this->assert(null, $this, 'lister',
			      "Vous n'avez pas le droit de voir les individus de ce site");
		$s = $ti->select()
			->from('individus')
			->joinLeft('users',
				   'users.username = individus.username',
				   array())
			->where('users.username IS NOT NULL')
			->order('id');
		$is = $ti->fetchSelect($s);
		$p = $this->_getParam('page');
		$p = $p ? $p : 1;
		$this->view->individus = new Wtk_Pages_Model_Iterator($is, 20, $p);
		$this->view->profils = (bool) Zend_Registry::get('user');
		$this->branche->append('Membres');
	}

	function sudoAction()
	{
	  $i = $this->_helper->Individu();

	  $moi = Zend_Registry::get('user');
	  $acl = Zend_Registry::get('acl');
	  $this->assert(null, null, null,
			"Vous n'avez pas le droit de prendre l'identité de cet individu.");

	  $this->_helper->Auth->sudo($i);

	  $this->redirectSimple('voir', 'individus', null,
				array('individu' => $i->slug), true);
	}

	function unsudoAction()
	{
	  $user = Zend_Registry::get('actual_user');
	  $this->_helper->Auth->sudo($user);
	  $this->redirectSimple('index', 'index', null, array(), true);
	}

	function logoutAction()
	{
	  $auth = Zend_Auth::getInstance();
	  $auth->clearIdentity();
	  $this->redirectSimple('index', 'index', null, array(), true);
	}

	// ENVOI DES COURRIELS


	// envoi un courriel à tous les admins notifiant une nouvelle
	// inscription.
	function sendInscriptionMail($tuple, $scoutisme)
	{
		$site = Zend_Registry::get('site');

		// Envoi d'un courriel à tout les admins.
		// WTK
		$metas = $site->metas->toArray();
		$nc = $tuple['prenom'].' '.$tuple['nom'];
		$mail = new Strass_Mail('Inscription : '.$nc);
		$mail->setFrom($tuple['adelec'], $nc);
		$mail->addTo($site->admin, $site->title);
		$mail->notifyAdmins();
		$mail->notifyChefs();

		$d = $mail->getDocument();
		$d->addText("Chers administrateurs,\n\n".
			    $nc." (".
			    strftime('%e/%m/%Y',
				     strtotime($tuple['naissance'])).
			    ") a soumis son inscription sur ".$site->id);

		if ($tuple['message']) {
			$s = $d->addSection(null, 'Message personnel');
			$s->addText($tuple['message']);
		}

		if ($scoutisme) {
			// purge des items inutiles.
			foreach($scoutisme as $i => $s) {
				if (!$s['unite'] || !$s['poste']) {
					unset($scoutisme[$i]);
				}
			}

			if (count($scoutisme)) {
				$m = new Wtk_Table_Model_Array($scoutisme);
				$s = $d->addSection(null, 'Scoutisme');
				$t = $s->addTable($m);
				$t->addNewColumn('Unité', new Wtk_Table_CellRenderer_Text('text', 'unite'));
				$t->addNewColumn('Poste', new Wtk_Table_CellRenderer_Text('text', 'poste'));
				$t->addNewColumn('Début', new Wtk_Table_CellRenderer_Text('text', 'debut'));
				$t->addNewColumn('Fin', new Wtk_Table_CellRenderer_Text('text', 'fin'));
			}
		}

		$l = $d->addList();
		$l->addItem(new Wtk_Link($this->_helper->Url->full('valider', null, null,
								   array('id' => $tuple['username'])),
					 "Modérer cette inscription"));
		$l->addItem(new Wtk_Link($this->_helper->Url->full('inscriptions'),
					 "Voir les inscriptions en attentes"));
		$mail->send();
	}

	// envoi un courriel à tous les admins notifiant une nouvelle
	// inscription validée.
	// $cotisation indique s'il faut parler de la côtisation … !
	function sendValidationMail($tuple, $message, $cotisation)
	{
		$site = Zend_Registry::get('site');

		// Envoi d'un courriel à tout les admins.
		$nc = $tuple['prenom'].' '.$tuple['nom'];
		$mail = new Strass_Mail('Inscription validée');
		$mail->addTo($tuple['adelec'], $nc);

		// WTK
		$d = $mail->getDocument();
		$d->addText("Cher ".$nc.",\n\n".
			    "Votre inscription a été validée. ".
			    "Votre identifiant est '".$tuple['username']."'.\n");
		if ($message)
			$d->addSection(null, 'Message du modérateur :')->addText($message);

		$d->addText("Les modérateurs peuvent modifier votre identifiant. ".
			    "Dans un tel cas, votre mot de passe sera invalide. ".
			    "Demander à un administrateur de changer votre mot de ".
			    "passe manuellement pour vous donner accès au site. ".
			    "Merci de votre compréhension.");

		if ($cotisation) {
			$cotisation = "Votre inscription sera validé à la réception de votre côtisation.\n\n".
			  file_get_contents($this::$cotisation);
			$racine = $this->_helper->UniteRacine();
			$app = $racine->findAppartenances("role = 'chef' AND fin IS NULL")->current();
			$chef = $app->findParentIndividus();
			$envoi ="À adresser rapidement au ".$app->findParentRoles()->titre." :\n\n".
				$chef->getFullname(false)."\n".
				$chef->adresse."\n";

			$d->addSection('cotisation', "Côtisation")->addText($cotisation);
			$d->addSection('envoi', "Envoi")->addText($envoi);
		}

		$l = $d->addList();
		$l->addItem(new Wtk_Link($this->_helper->Url->full('index', 'index'),
					 "Accéder au site web"));
		$l->addItem(new Wtk_Link($this->_helper->Url->full('voir', 'individus', null,
								   array('individu' => $tuple['id'])),
					 "Accéder à votre profil"));
		$mail->send();
	}

	// envoi un courriel à tous les admins notifiant une nouvelle
	// inscription.
	function sendValidationRefusMail($tuple, $message)
	{
		// Envoi d'un courriel à tout les admins.
		$nc = $tuple['prenom'].' '.$tuple['nom'];
		$mail = new Strass_Mail('Inscription refusée');
		$mail->addTo($tuple['adelec'], $nc);

		// WTK
		$d = $mail->getDocument();
		$d->addText("Cher ".$nc.",\n\n".
			    "Votre inscription a été refusée.\n\n".
			    ($message ? "++ Message du modérateur :\n".
			     $message."\n" : ""));
		$mail->send();
	}
}

