<?php

require_once 'Strass/Unites.php';

class MembresController extends Strass_Controller_Action implements Zend_Acl_Resource_Interface
{
  function init()
  {
    parent::init();

    $acl = Zend_Registry::get('acl');
    if (!$acl->has($this))
      $acl->add($this);
    try {
      $racine = $this->_helper->Unite->racine();
      $acl->allow($racine->getRoleId('chef'), $this);
    }
    catch (Strass_Db_Table_NotFound $e) {}
  }

  function getResourceID()
  {
    return 'membres';
  }

  function indexAction()
  {
    $this->assert(null, $this, 'fiche', "Vous devez être identifié.");
    $this->redirectSimple('index', 'index');
  }

  function inscriptionAction()
  {
    $this->metas(array('DC.Title' => "Fiche d'inscription"));
    $this->branche->append();

    $m = new Wtk_Form_Model('inscription');

    // FICHE INDIVIDU
    $g = $m->addGroup('fiche');
    $i = $g->addString('prenom', "Prénom");
    $m->addConstraintRequired($i);

    $i = $g->addString('nom', "Nom");
    $m->addConstraintRequired($i);

    $enum = array('h' => 'Masculin', 'f' => 'Féminin');
    $i = $g->addEnum('sexe', 'Sexe', null, $enum);
    $m->addConstraintRequired($i);

    $i = $g->addDate('naissance', "Date de naissance", 0);
    $m->addConstraintRequired($i);

    // COMPTE
    $g = $m->addGroup('compte');
    $i = $g->addString('adelec', "Adresse électronique");
    $m->addConstraintEMail($i);

    $i0 = $g->addString('motdepasse', "Mot de passe");
    $m->addConstraintLength($i0, 6);
    $i1 = $g->addString('confirmer', "Confirmer");
    $m->addConstraintEqual($i1, $i0);

    $m->addString('presentation', "Présentation");

    $this->view->model = $pm = new Wtk_Pages_Model_Form($m);
    if ($pm->validate()) {
      $data = $m->get('fiche');
      $data['adelec'] = $m->get('compte/adelec');
      $data['password'] = Users::hashPassword($m->get('compte/adelec'),
					      $m->get('compte/motdepasse'));
      $data['presentation'] = $m->get('presentation');

      $t = new Inscriptions;
      $db = $t->getAdapter();
      $db->beginTransaction();
      try {
	$k = $t->insert($data);
	$i = $t->findOne($k);

	$this->logger->info("Nouvelle inscription",
			    $this->_helper->Url('valider', 'membres', null, array('adelec' => $i->adelec)));
	$this->_helper->Flash->info("Inscription en modération");

	$mail = new Strass_Mail_Inscription($i);
	$mail->send();

	$db->commit();
      }
      catch(Exception $e) {
	$db->rollBack();
	throw $e;
      }

      $this->redirectSimple('index', 'index');
    }
  }

  function inscriptionsAction()
  {
    $this->metas(array('DC.Title' => "Inscriptions en attente",
		       'DC.Title.alternative' => "Inscriptions"));
    $this->branche->append();

    $t = new Inscriptions;
    $this->assert(null, $t, 'valider',
		  "Vous n'avez pas le droit de voir les inscriptions en attente.");

    $this->view->inscriptions = new Strass_Pages_Model_Rowset($t->select());
  }

  function validerAction()
  {
    $this->metas(array('DC.Title' => "Valider une inscription"));
    $this->branche->append("Inscriptions", array('action' => 'inscriptions', 'adelec' => null));

    $t = new Inscriptions;
    $this->assert(null, $t, 'valider',
		  "Vous n'avez pas le droit de valider les inscriptions en attente.");

    $adelec = $this->_getParam('adelec');
    if ($adelec) {
      try {
	$ins = $t->findByEMail($adelec);
      }
      catch (Strass_Db_Table_NotFound $e)  {
	throw new Strass_Controller_Action_Exception_NotFound("Inscription déjà validée");
      }
    }
    else if (!$ins = $t->fetchAll()->current())
      throw new Strass_Controller_Action_Exception_Notice("Aucune inscription à valider.");

    $this->view->individu = $ind = $ins->findIndividus();
    $this->view->inscription = $ins;
    $this->branche->append($ins->getFullname());

    $this->view->model = $m = new Wtk_Form_Model('valider');
    $i = $m->addString('prenom', 'Prénom', $ins->prenom);
    $i->setReadonly((bool) $ind);
    $m->addConstraintRequired($i);
    $i = $m->addString('nom', 'Nom', $ins->nom);
    $i->setReadonly((bool) $ind);
    $m->addConstraintRequired($i);

    if ($ind) {
      $enum = array($ind->id => "Rattacher à ".$ind->getFullname(),
		    '$$nouveau$$' => "Créer une nouvelle fiche",
		    );
      $m->addEnum('fiche', null, $ind->id, $enum);
    }
    $m->addString('message', "Message à ".$ins->getFullname());

    $m->addNewSubmission('accepter', 'Accepter');
    $m->addNewSubmission('refuser', 'Spam !');

    if ($s = $m->validate()) {
      $tu = new Users;
      $ti = new Individus;
      $db = $ti->getAdapter();

      if ($s->id == 'accepter') {
	$creer = !$ind || $m->get('fiche') == '$$nouveau$$';
	if ($creer) {
	  $ituple = array('slug' => $ti->createSlug(wtk_strtoid($ins->getFullname())));
	  $ituple['prenom'] = $m->get('prenom');
	  $ituple['nom'] = $m->get('nom');
	  $ituple['sexe'] = $ins->sexe;
	  $ituple['naissance'] = $ins->naissance;
	  $ituple['adelec'] = $ins->adelec;
	}

	$utuple = array('username' => $ins->adelec,
			'password' => $ins->password,
			);

	$db->beginTransaction();
	try {
	  if ($creer) {
	    $k = $ti->insert($ituple);
	    $ind = $ti->findOne($k);
	  }
	  $utuple['individu'] = $ind->id;
	  $k = $tu->insert($utuple);
	  $user = $tu->findOne($k);

	  $mail = new Strass_Mail_InscriptionValide($user, $m->get('message'));
	  $mail->send();

	  $this->logger->info("Inscription acceptée",
			      $this->_helper->Url('fiche', 'individus', null,
						  array('individu' => $ind->slug)),
			      (string) $ind);
	  $ins->delete();

	  $db->commit();
	}
	catch(Exception $e) { $db->rollBack(); throw $e; }

	$this->_helper->Flash->info("Inscription acceptée");
      }
      else {
	$db->beginTransaction();
	try {
	  $this->logger->warn("Inscription de {$ins->adelec} refusée",
			      $this->_helper->Url('inscriptions', 'membres', null, null, true));
	  $mail = new Strass_Mail_InscriptionRefus($ins, $m->get('message'));
	  $mail->send();

	  $ins->delete();

	  $db->commit();
	}
	catch(Exception $e) { $db->rollBack(); throw $e; }

	$this->_helper->Flash->info("Inscription refusée");
      }

      if ($this->_getParam('adelec'))
	$this->redirectSimple('inscriptions', 'membres', null, null, true);
      else
	$this->redirectSimple('valider');
    }
  }

  function recouvrirAction()
  {
    $this->metas(array('DC.Title' => "Recouvrir l'accès à votre compte"));

    $token = $this->_getParam('confirmer');
    if ($token) {
      $t = new Users;
      try {
	$user = $t->findByRecoverToken($token);
      }
      catch (Strass_Db_Table_NotFound $e) {
	throw new Zend_Controller_Action_Exception("Jeton inconnu ou expiré", 404);
      }

      $this->view->set = $m = new Wtk_Form_Model('recouvrir');
      $i0 = $m->addString('nouveau', "Nouveau mot de passe");
      $i1 = $m->addString('confirmation', "Confirmer");
      $m->addConstraintRequired($i0);
      $m->addConstraintEqual($i1, $i0);
      $m->addNewSubmission('enregistrer', 'Enregistrer');

      if ($m->validate()) {
	$db = $t->getAdapter();
	$db->beginTransaction();
	try {
	  $this->view->individu = $individu = $user->findParentIndividus();
	  $user->username = $individu->adelec;
	  $user->setPassword($m->get('nouveau'));
	  $user->recover_token = null;
	  $user->save();

	  $this->logger("Recouvrement du compte",
			$this->_helper->Url('fiche', 'individus', null,
					    array('individu' => $individu->slug)));
	  $db->commit();
	}
	catch (Exception $e) {
	  $db->rollBack();
	  throw $e;
	}
      }
    }
    else {
      $this->view->send = $m = new Wtk_Form_Model('recouvrir');
      $m->addConstraintEMail($m->addString('adelec', "Votre adresse"));
      $m->addNewSubmission('envoyer', "Envoyer");

      if ($m->validate()) {
	$t = new Users;
	try {
	  $user = $t->findByEMail($m->get('adelec'));
	}
	catch (Zend_Db_Table_Exception $e) {
	  $m->errors[] = new Wtk_Form_Model_Exception('Adresse inconnue', $m->getInstance('adelec'));
	  return;
	}

	$individu = $user->findParentIndividus();

	$user->recover_token = md5(uniqid() . '-' . mt_rand(10000, 99999));
	/* Laisser une demi heure pour délivrer le message */
	$user->recover_deadline = time() + 30 * 60;
	$user->save();

	$this->view->mail = $mail = new Strass_Mail_Recover($user);
	$mail->addTo($individu->adelec, $individu->getFullname(false));
	$mail->send();

	$this->_helper->flash->info("Courriel envoyé",
				    "Un courriel vous a été envoyé avec un lien vers la page ".
				    "pour définir un nouveau mot de passe. Le lien expirera dans ".
				    "une demi heure.");
	$this->redirectSimple('index', 'index');
      }
    }
  }

  function parametresAction()
  {
    $moi = Zend_Registry::get('user');
    $this->view->user = $user = $this->_helper->Membre($moi);
    $this->view->individu = $individu = $user->findParentIndividus();

    $this->assert($moi, $user, 'parametres',
		  "Vous n'avez pas le droit de modifier les paramètres de cet utilisateur.");

    $this->metas(array('DC.Title' => "Éditer l'utilisateur ".$user->username));

    $autoedit = $moi->id == $user->id;
    $db = Zend_Registry::get('db');

    /* Migration de l'identifiant */
    if ($autoedit && $user->username != $individu->adelec) {
      $this->view->migrate = $m = new Wtk_Form_Model('migrate');
      $m->addConstraintRequired($m->addString('motdepasse', 'Mot de passe'));
      $m->addNewSubmission('migrer', 'Migrer');

      if ($m->validate()) {
	$db->beginTransaction();
	try {
	  if (!$user->testPassword($m->get('motdepasse')))
	    throw new Wtk_Form_Model_Exception('Mot de passe erroné', $m->getInstance('motdepasse'));

	  $user->username = $individu->adelec;
	  $user->setPassword($m->get('motdepasse'));
	  $user->save();

	  $this->_helper->Log("Migration du compte", array($individu),
			      $this->_helper->Url('fiche', 'individus', null,
						  array('individu' => $individu->slug)),
			      (string) $individu);
	  $db->commit();

	  $auth = Zend_Auth::getInstance();
	  $id = $auth->getIdentity();
	  $id['username'] = $user->username;
	  $auth->getStorage()->write($id);

	  $this->redirectSimple('fiche', 'individus', null,
				array('individu' => $individu->slug),
				true);
	} catch(Wtk_Form_Model_Exception $e) {
	  $db->rollBack();
	  $m->errors[] = $e;
	} catch (Exception $e) {
	  $db->rollBack();
	  throw $e;
	}
      }
    }

    /* Changement d'adélec */
    if ($autoedit) {
      $this->view->adelec = $m = new Wtk_Form_Model('adelec');
      $i = $m->addString('adelec', 'Adelec', $individu->adelec);
      $m->addConstraintRequired($i);
      $m->addConstraintEMail($i);
      $m->addConstraintRequired($m->addString('motdepasse', 'Mot de passe'));
      $m->addNewSubmission('enregistrer', 'Enregistrer');

      if ($m->validate()) {
	$db->beginTransaction();
	try {
	  if (!$user->testPassword($m->get('motdepasse')))
	    throw new Wtk_Form_Model_Exception('Mot de passe erroné', $m->getInstance('motdepasse'));

	  if ($user->username == $individu->adelec) {
	    $user->username = $m->get('adelec');
	    $user->setPassword($m->get('motdepasse'));
	    $user->save();

	    $auth = Zend_Auth::getInstance();
	    $id = $auth->getIdentity();
	    $id['username'] = $user->username;
	    $auth->getStorage()->write($id);
	  }

	  $individu->adelec = $m->get('adelec');
	  $individu->save();

	  $this->_helper->Log("Changement d'adélec", array($individu),
			      $this->_helper->Url('fiche', 'individus', null,
						  array('individu' => $individu->slug)),
			      (string) $individu);
	  $db->commit();

	  $this->redirectSimple('fiche', 'individus', null,
	  			array('individu' => $individu->slug),
	  			true);
	} catch(Wtk_Form_Model_Exception $e) {
	  $db->rollBack();
	  $m->errors[] = $e;
	} catch (Exception $e) {
	  $db->rollBack();
	  throw $e;
	}
      }
    }

    /* Changement de mot de passe */
    $this->view->change = $m = new Wtk_Form_Model('chpass');
    $g = $m->addGroup('mdp', "Change le mot de passe");
    if (!$this->assert(null) || $autoedit)
      $m->addConstraintRequired($g->addString('ancien', 'Ancien'));
    $m->addConstraintRequired($g->addString('nouveau', 'Nouveau'));
    $m->addConstraintRequired($g->addString('confirmation', "Confirmation"));
    $m->addNewSubmission('valider', 'Valider');

    if ($m->validate()) {
      $db->beginTransaction();
      try {
	$mdp = $m->get('mdp');
	if (array_key_exists('ancien', $mdp)) {
	  if (!$user->testPassword($mdp['ancien'])) {
		throw new Wtk_Form_Model_Exception("Ancien mot de passe erroné.",
						   $m->getInstance('mdp/ancien'));
	    }
	}

	if ($mdp['nouveau'] != $mdp['confirmation']) {
	  throw new
	    Wtk_Form_Model_Exception("Le mot de passe de confirmation n'est pas identique au nouveau.");
	}

	$user->setPassword($mdp['nouveau']);
	$user->save();

	$this->_helper->Log("Mot de passe changé", array($individu),
			    $this->_helper->Url('fiche', 'individus', null,
						array('individu' => $individu->slug)),
			    (string) $individu);

	$db->commit();
	$this->redirectSimple('fiche', 'individus', null,
			      array('individu' => $individu->slug),
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

    /* Promotion à l'administration */
    if ($this->assert($moi, $user, 'admin') && !$autoedit) {
      $this->view->admin = $m = new Wtk_Form_Model('admin');
      $m->addBool('admin',
		  "Accorder tout les privilèges sur le site à ".$user->findParentIndividus()->getFullName(),
		  $user->admin);
      $m->addNewSubmission('valider', 'Valider');

      if ($m->validate()) {
	$db->beginTransaction();
	try {
	  $user->admin = $m->get('admin');
	  $user->save();
	  $db->commit();

	  $msg = $user->admin ? "Privilèges accordés" : "Privilèges refusés";
	  $this->_helper->Log($msg, array($individu),
			      $this->_helper->Url('fiche', 'individus', null,
						  array('individu' => $individu->slug)),
			      (string) $individu);

	  $this->redirectSimple('fiche', 'individus', null,
				array('individu' => $individu->slug),
				true);
	}
	catch(Exception $e) {
	  $db->rollBack();
	  throw $e;
	}
      }
    }
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
    $is = $ti->fetchAll($s);
    $p = $this->_getParam('page');
    $p = $p ? $p : 1;
    $this->view->individus = new Wtk_Pages_Model_Iterator($is, 20, $p);
    $this->view->fiches = (bool) Zend_Registry::get('user');
    $this->branche->append('Membres');
  }

  function sudoAction()
  {
    $i = $this->_helper->Individu();

    $moi = Zend_Registry::get('user');
    $acl = Zend_Registry::get('acl');
    $this->assert(null, null, null,
		  "Vous n'avez pas le droit de prendre l'identité de cet individu.");

    $this->_helper->Auth->sudo($i->findUser());

    $this->redirectSimple('fiche', 'individus', null,
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
}
