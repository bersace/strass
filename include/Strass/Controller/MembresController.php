<?php

require_once 'Strass/Unites.php';

class MembresController extends Strass_Controller_Action implements Zend_Acl_Resource_Interface
{
    public $_afficherMenuUniteRacine = true;

    function init()
    {
        parent::init();

        $acl = Zend_Registry::get('acl');
        if (!$acl->has($this))
            $acl->add($this);
        try {
            $t = new Unites;
            $racines = $t->findRacines();
            foreach($racines as $racine)
                $acl->allow($racine->getRoleId('chef'), $this);
        }
        catch (Strass_Db_Table_NotFound $e) {}
    }

    function getResourceId()
    {
        return 'membres';
    }

    function indexAction()
    {
        $this->assert(null, $this, 'fiche', "Vous devez être identifié.");
        $this->redirectSimple('index', 'unites', null, null, true);
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

        $t = new Unites;
        $sexes = $t->findSexesAccueillis();
        if (in_array('m', $sexes) || count($sexes) > 1) {
            $enum = array('h' => 'Masculin', 'f' => 'Féminin');
            $i = $g->addEnum('sexe', 'Sexe', null, $enum);
            $m->addConstraintRequired($i);
        }
        else {
            $i = $g->addString('sexe', 'Sexe', $sexes[0])->setReadonly(true);
        }

        $i = $g->addDate('naissance', "Date de naissance", 0);
        $m->addConstraintRequired($i);

        // COMPTE
        $g = $m->addGroup('compte');
        $i = $g->addEMail('adelec', "Adresse électronique");
        $t = new Inscriptions;
        $m->addConstraintForbid(
            $i, $t->findAllEMails(),
            "Cette adresse électronique est déjà utilisée");

        $i0 = $g->addString('motdepasse', "Mot de passe");
        $m->addConstraintLength($i0, 6);
        $i1 = $g->addString('confirmer', "Confirmer");
        $m->addConstraintEqual($i1, $i0);

        $i = $g->addString('presentation', "Présentation");
        $m->addConstraintRequired($i);

        $this->view->model = $pm = new Wtk_Pages_Model_Form($m);
        if ($pm->validate()) {
            $data = $m->get('fiche');
            $data['adelec'] = strtolower($m->get('compte/adelec'));
            $data['password'] = Users::hashPassword($m->get('compte/adelec'),
            $m->get('compte/motdepasse'));
            $data['presentation'] = $m->compte->presentation;

            $db = $t->getAdapter();
            $db->beginTransaction();
            try {
                $k = $t->insert($data);
                $i = $t->findOne($k);

                $this->logger->info("Nouvelle inscription",
			    $this->_helper->Url('valider', 'membres', null, array(
                    'adelec' => $i->adelec)));
                $this->_helper->Flash->info("Inscription en modération");

                $mail = new Strass_Mail_Inscription($i);
                try {
                    $mail->send();
                }
                catch (Zend_Mail_Transport_Exception $e) {
                    $this->logger->error(
                        "Échec de l'envoi de mail aux admins", null, $e);
                }

                $db->commit();
            }
            catch(Exception $e) {
                $db->rollBack();
                throw $e;
            }

            $this->redirectSimple('index', 'unites', null, array(), true);
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
        $this->branche->append("Inscriptions", array(
            'action' => 'inscriptions', 'adelec' => null));

        $t = new Inscriptions;
        $this->assert(null, $t, 'valider',
        "Vous n'avez pas le droit de valider les inscriptions en attente.");

        $adelec = $this->_getParam('adelec');
        if ($adelec) {
            try {
                $ins = $t->findByEMail($adelec);
            }
            catch (Strass_Db_Table_NotFound $e)  {
                $this->view->model = null;
                return;
            }
        }
        else if (!$ins = $t->fetchAll()->current()) {
            $this->view->model = null;
            return;
        }

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
            $enum = array(
                $ind->id => "Oui, rattacher à ".$ind->getFullname(),
                '$$nouveau$$' => "Non, c'est un homonyme, créer une nouvelle fiche",
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
                    $ind = new Individu;
                    $ind->slug = $ti->createSlug(wtk_strtoid($ins->getFullname()));
                    $ind->prenom = $m->prenom;
                    $ind->nom = $m->nom;
                    $ind->sexe = $ins->sexe;
                    $ind->naissance = $ins->naissance;
                    $ind->adelec = $ins->adelec;
                }

                $db->beginTransaction();
                try {
                    if ($creer)
                        $ind->save();

                    $user = $ind->findUser();
                    if (!$user->isMember())
                        $user = new User;

                    $user->individu = $ind->id;
                    $user->username = $ins->adelec;
                    $user->password = $ins->password;
                    $user->save();

                    $mail = new Strass_Mail_InscriptionValide($user, $m->get('message'));
                    $mail->send();

                    $this->logger->info(
                        "Inscription acceptée",
                        $this->_helper->Url(
                            'fiche', 'individus', null,
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

                    $this->logger->info("Recouvrement du compte",
                    $this->_helper->Url('fiche', 'individus', null,
                    array('individu' => $individu->slug)));
                    $db->commit();
                }
                catch (Exception $e) {
                    $db->rollBack();
                    throw $e;
                }
                $this->redirectSimple('index', 'unites');
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
                $fn = trim(wtk_ucfirst($individu->prenom)." ".$individu->capitalizedLastname());
                $mail->addTo($individu->adelec, $fn);
                $mail->send();

                $this->_helper->flash->info("Courriel envoyé",
                "Un courriel vous a été envoyé avec un lien vers la page ".
                "pour définir un nouveau mot de passe. Le lien expirera dans ".
                "une demi heure.");
                $this->redirectSimple('index', 'unites');
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

                    $this->logger->info("Migration du compte",
                    $this->_helper->Url(
                        'fiche', 'individus', null,
                        array('individu' => $individu->slug)));
                    $db->commit();

                    $auth = Zend_Auth::getInstance();
                    $id = $auth->getIdentity();
                    $id['username'] = $user->username;
                    $auth->getStorage()->write($id);
                } catch(Wtk_Form_Model_Exception $e) {
                    $db->rollBack();
                    $m->errors[] = $e;
                } catch (Exception $e) {
                    $db->rollBack();
                    throw $e;
                }

                $this->redirectSimple('fiche', 'individus', null, array('individu' => $individu->slug), true);
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

                    $this->logger->info("Changement d'adélec",
                    $this->_helper->Url(
                        'fiche', 'individus', null,
                        array('individu' => $individu->slug)));
                    $db->commit();
                } catch(Wtk_Form_Model_Exception $e) {
                    $db->rollBack();
                    $m->errors[] = $e;
                } catch (Exception $e) {
                    $db->rollBack();
                    throw $e;
                }

                $this->redirectSimple('fiche', 'individus', null, array('individu' => $individu->slug), true);
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
                        throw new Wtk_Form_Model_Exception(
                            "Ancien mot de passe erroné.",
                            $m->getInstance('mdp/ancien'));
                    }
                }

                if ($mdp['nouveau'] != $mdp['confirmation']) {
                    throw new Wtk_Form_Model_Exception(
                        "Le mot de passe de confirmation n'est pas identique ".
                        "au nouveau.");
                }

                $user->setPassword($mdp['nouveau']);
                $user->save();

                $this->logger->info(
                    "Mot de passe changé",
                    $this->_helper->Url('fiche', 'individus', null, array(
                        'individu' => $individu->slug)));

                $db->commit();
            }
            catch(Wtk_Form_Model_Exception $e) {
                $db->rollBack();
                $m->errors[] = $e;
            }
            catch(Exception $e) {
                $db->rollBack();
                throw $e;
            }

            $this->redirectSimple('fiche', 'individus', null, array(
                'individu' => $individu->slug), true);
        }

        /* Notifications */
        $this->view->notifications = $m = new Wtk_Form_Model('notifications');
        $m->addBool(
            'send_mail',
            "Recevoir des notifications par mail",
            $user->send_mail);
        $m->addNewSubmission('valider', 'Valider');

        if ($m->validate()) {
            $db->beginTransaction();
            try {
                $user->send_mail = (bool) $m->get('send_mail');
                $user->save();
                $db->commit();

                if ($user->send_mail)
                    $msg = "Notifications activées";
                else
                    $msg = "Notifications désactivées";
                $this->logger->info(
                    $msg, $this->_helper->Url('fiche', 'individus', null, array(
                        'individu' => $individu->slug)));
            }
            catch(Exception $e) {
                $db->rollBack();
                throw $e;
            }

            $this->redirectSimple('fiche', 'individus', null, array(
                'individu' => $individu->slug), true);
        }

        /* Promotion à l'administration */
        if ($this->assert($moi, $user, 'admin') && !$autoedit) {
            $this->view->admin = $m = new Wtk_Form_Model('admin');
            $m->addBool('admin',
            "Accorder tous les privilèges sur le site à ".$user->findParentIndividus()->getFullName(),
            $user->admin);
            $m->addNewSubmission('valider', 'Valider');

            if ($m->validate()) {
                $db->beginTransaction();
                try {
                    $user->admin = $m->get('admin');
                    $user->save();
                    $db->commit();

                    $msg = $user->admin ? "Privilèges accordés" : "Privilèges refusés";
                    $this->logger->warn($msg, $this->_helper->Url(
                        'fiche', 'individus', null, array(
                            'individu' => $individu->slug)));
                }
                catch(Exception $e) {
                    $db->rollBack();
                    throw $e;
                }

                $this->redirectSimple('fiche', 'individus', null, array(
                    'individu' => $individu->slug), true);
            }
        }
    }

    function sudoAction()
    {
        $cible = $this->_helper->Individu();
        $user = $cible->findUser();

        $this->assert(null, $user, 'sudo',
        "Vous n'avez pas le droit de prendre l'identité de cet individu.");

        $this->logger->warn(
            "Sudo ". $cible->getFullName(),
            $this->_helper->Url(
                'fiche', 'individus', null,
                array('individu' => $cible->slug)));

        $this->_helper->Auth->sudo($user);

        $this->redirectSimple('fiche', 'individus', null, array(
            'individu' => $cible->slug), true);
    }

    function unsudoAction()
    {
        $this->_helper->Auth->unsudo();
        $this->redirectUrl(array(), null, true);
    }

    function logoutAction()
    {
        $auth = Zend_Auth::getInstance();
        $auth->clearIdentity();
        $this->redirectSimple(null, null, null, array(), true);
    }
}
