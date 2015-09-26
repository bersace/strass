<?php

require_once 'Strass/Individus.php';
require_once 'Strass/Unites.php';

class IndividusController extends Strass_Controller_Action
{
    public $_afficherMenuUniteRacine = true;

    function indexAction()
    {
        $this->metas(array('DC.Title' => "Annuaire"));

        $this->assert(null, 'membres', 'voir', "Accès réservé aux membres");

        $this->view->recherche = $m = new Wtk_Form_Model('recherche');
        $m->addString('recherche', 'Recherche');
        $m->addNewSubmission(
            'chercher', 'Chercher',
            null, Wtk_Form_Model_Submission::METHOD_GET);

        $t = new Individus;
        if ($m->validate()) {
            $s = $t->selectSearch($m->recherche);
        }
        else {
            $s = $t->selectAll();
        }

        $this->view->individus = new Strass_Pages_Model_Rowset($s, 20, $this->_getParam('page'));
    }

    function ficheAction()
    {
        $individu = $this->_helper->Individu();

        $this->metas(array('DC.Title' => "Fiche d'individu"));

        $this->assert(null, $individu, 'fiche',
        "Vous n'avez pas le droit de voir cette fiche. ");

        $this->metas(array('DC.Title' => $individu->getFullname(false, false)));

        $this->formats('vcf', 'csv');

        $this->view->chef = $this->assert(null, $individu, 'progression');
        $this->view->individu = $individu;
        $this->view->etape = $individu->findParentEtapes();
        $this->view->apps = $individu->findAppartenances();
        $s = $individu->getTable()->select()->order('date DESC');
        $this->view->user = $user = $individu->findUser();

        $this->actions->append("Inscription",
        array('action' => 'inscrire'),
        array(null, $individu, 'inscrire'));
        $this->actions->append("Éditer la fiche",
        array('action'        => 'editer'),
        array(null, $individu));

        if ($individu->isMember()) {
            $this->actions->append("Paramètres utilisateur",
            array('controller'        => 'membres',
            'action' => 'parametres',
            'membre' => $user->username,
            'individu' => null),
            array(null, null, 'admin'));
            if (!Zend_Registry::offsetExists('sudoer')
            && $this->assert(null, $individu->findUser(), 'sudo'))
                $this->actions->append("Prendre l'identité",
                array('controller'        => 'membres',
                'action' => 'sudo',
                'username' => $user->username),
                array(null, null, 'admin'));
        }

        $this->actions->append("Supprimer",
        array('action'        => 'supprimer'),
        array(null, $individu));
    }

    function editerAction()
    {
        $this->view->individu = $individu = $this->_helper->Individu();
        $this->assert(null, $individu, 'editer',
        "Vous n'avez pas le droit d'éditer la fiche de cet individu.");

        $this->metas(array('DC.Title' => 'Éditer '.$individu->getFullname()));

        $this->view->model = $m = new Wtk_Form_Model('editer');

        if ($this->assert(null, $individu, 'editer-id')) {
            $m->addConstraintRequired($m->addString('prenom', 'Prénom', $individu->prenom));
            $m->addConstraintRequired($m->addString('nom', 'Nom', $individu->nom));
            $m->addDate('naissance', 'Date de naissance', $individu->naissance);
            $m->addEnum('sexe', 'Sexe', $individu->sexe, array('h' => 'Masculin', 'f' => 'Féminin'));
        }

        $m->addFile('image', 'Photo');

        $sachem = $this->assert(null, $individu, 'totem');
        if ($sachem)
            $m->addString('totem', 'Totem', $individu->totem);

        $m->addString('notes', "Notes", $individu->notes);
        $p = $individu->findParentEtapes();
        $i = $m->addEnum('etape', "Progression", $p ? $p->id : null, array(null => 'Aucune'));
        foreach($individu->findEtapesCanditates() as $e)
            $i->addItem($e->id, $e->titre);

        // contacts;
        if (!$individu->isMember())
            $m->addString('adelec', "Adélec", $individu->adelec);
        $m->addString('portable', "Téléphone portable", $individu->portable);
        $m->addString('fixe', "Téléphone fixe", $individu->fixe);
        $m->addString('adresse', "Adresse", $individu->adresse);

        $m->addNewSubmission('valider', 'Valider');

        if ($m->validate()) {
            $t = $individu->getTable();
            $db = $t->getAdapter();
            $db->beginTransaction();
            try {
                // contacts
                $champs = array('nom', 'prenom', 'naissance', 'portable', 'sexe',
                'fixe', 'adresse', 'notes', 'etape');
                try {
                    $m->getInstance('adelec');
                    array_push($champs, 'adelec');
                }
                catch(Exception $e) {}

                if ($sachem)
                    $champs[] = 'totem';

                if ($this->assert(null, $individu, 'progression')) {
                    $champs[] = 'numero';
                }

                foreach($champs as $champ) {
                    try {
                        $m->getInstance($champ);
                        $individu->$champ = trim($m->$champ);
                    }
                    catch (Exception $e) {}
                }

                $individu->fixe = $this->_helper->Telephone($individu->fixe);
                $individu->portable = $this->_helper->Telephone($individu->portable);
                $individu->slug = $t->createSlug($individu->getFullname(false, false), $individu->slug);
                $individu->save();

                $image = $m->getInstance('image');
                if ($image->isUploaded()) {
                    $tmp = $image->getTempFilename();
                    try {
                        $individu->storeImage($tmp);
                    }
                    catch (ImagickException $e) {
                        throw new Wtk_Form_Model_Exception($e->getMessage(), $image);
                    }
                }

                $this->logger->info("Fiche individu mis-à-jour",
                $this->_helper->Url('fiche', 'individus', null,
                array('individu' => $individu->slug)));

                $db->commit();
                $this->redirectSimple('fiche', 'individus', null,
                array('individu' => $individu->slug));

            }
            catch (Wtk_Form_Model_Exception $e) {
                $db->rollBack();
                $m->errors[] = $e;
                return;
            }
            catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
        }

        $this->actions->append("Inscription",
        array('controller'        => 'individus',
        'action'        => 'inscrire'),
        array(null, $individu, 'inscrire'));
        $this->actions->append("Paramètres utilisateur",
        array('controller'        => 'membres',
        'action'        => 'parametres'),
        array(null, $individu, 'admin'));
    }

    function inscrireAction()
    {
        $this->view->individu = $individu = $this->_helper->Individu();
        $this->metas(array('DC.Title' => 'Inscription'));
        $this->branche->append();

        $this->assert(null, $individu, 'inscrire',
        "Vous n'avez pas le droit d'inscrire cet individu dans une unité.");

        $this->view->apps = $individu->findAppartenances();    /* CV scout */
        $apps = $individu->findInscriptionsActives();
        $unites = $individu->findUnitesCandidates();

        $m = new Wtk_Form_Model('inscrire');
        $g = $m->addGroup('actuel');
        $g->addDate('date', "Date d'inscription");

        $gg = $g->addGroup('apps');
        $default_next = null;

        if ($apps->count()) {
            $default_next = $apps->rewind()->current()->unite;

            foreach ($apps as $app)
                $gg->addBool($app->id, "N'est plus ".$app->getShortDescription(), true);
        }

        if ($unites->count()) {
            $i0 = $g->addBool('inscrire', "Inscrire dans une autre unité ou promouvoir", true)
                /* Pour un nouveau, on viens forcément pour inscrire */
                    ->setReadonly((bool) $apps->count() == 0);
            $i1 = $g->addEnum('unite', "Unité", $default_next);
            foreach($unites as $u)
                $i1->addItem($u->id, $u->getFullname());
            if ($apps->count()) {
                $m->addConstraintDepends($i1, $i0);
            }
        }
        else {
            $message = "Aucune unité pour ".$individu->getFullname()." !";
            $aide = "Les contraintes d'âge et de sexe ne permettent pas ".
                "d'inscrire {$individu->getFullname()} dans une unité.";
            throw new Strass_Controller_Action_Exception_Notice($message, 500, $aide);
        }

        $g = $m->addGroup('role');
        $g->addEnum('role', 'Rôle');
        $i0 = $g->addBool('clore', "Ne l'est plus depuis", $apps->count() > 0);
        $i1 = $g->addDate('fin', "Date de fin", $m->get('actuel/date'));
        $m->addConstraintDepends($i1, $i0);

        $this->view->model = $pm = new Wtk_Pages_Model_Form($m);

        $tu = new Unites;
        $tr = new Roles;

        $page = $pm->partialValidate();

        if ($pm->pageCmp($page, 'role') == 0 && !$m->get('actuel/inscrire')) {
            $page = $pm->gotoEnd();
        }

        /* si on veut inscrire, et qu'on connait l'unité */
        if ($pm->pageCmp($page, 'role') >= 0 && $m->get('actuel/inscrire')) {
            $g = $m->getInstance('role');

            /* Sélections des rôles ou on peut l'inscrire */
            $unite = $tu->findOne($m->get('actuel/unite'));
            $roles = $unite->findParentTypesUnite()->findRoles();
            $i = $g->getChild('role');
            foreach ($roles as $role) {
                $i->addItem($role->id.'__', $role->titre);
                foreach ($role->findTitres() as $titre) {
                    $i->addItem($role->id.'__'.$titre->nom, $titre->nom);
                }
            }
        }

        /* Ne préremplir le role que si la page role va etre affichée */
        if ($pm->pageCmp($page, 'role') == 0) {
            $g = $m->getInstance('role');
            $i = $g->getChild('role');
            /* Préselection du role */
            $candidats = $individu->findRolesCandidats($unite);
            if ($candidats->count())
                $i->set($candidats->current()->id);

            /* Présélection de la date */
            $annee = intval(strtok($m->get('actuel/date'), '/'));
            if ($app = $individu->findInscriptionSuivante($annee)) {
                /* on a trouvé un successeur, donc potentiellement on clot */
                $m->getInstance('role/clore')->set(TRUE);
                $m->getInstance('role/fin')->set($app->debut);
            }
            else {
                $i = $m->getInstance('actuel/date');
                $fin = $i->getDateArray();
                $fin['year'] += 1;
                $future = $fin['year'] > date('%Y');
                $m->getInstance('role/clore')->set(!$future);
                $m->getInstance('role/fin')->set($fin);
            }
        }

        if ($pm->validate()) {
            $t = new Appartenances;

            $db = $t->getAdapter();
            $db->beginTransaction();
            try {
                foreach($m->get('actuel/apps') as $k => $clore) {
                    if (!$clore)
                        continue;
                    $app = $t->findOne($k);
                    $app->fin = $m->get('actuel/date');
                    $app->save();
                }

                if ($m->get('actuel/inscrire')) {

                    $app = new Appartient;
                    $app->individu = $individu->id;
                    $app->unite = $m->get('actuel/unite');
                    list($role, $titre) = explode('__', $m->get('role/role'));
                    $app->role = intval($role);
                    $app->titre = $titre;
                    $app->debut = $m->get('actuel/date');

                    if ($m->get('role/clore'))
                        $app->fin = $m->get('role/fin');
                    $app->save();
                }

                $this->logger->info("Inscription éditée", $this->_helper->Url('fiche'));
                $db->commit();
            }
            catch (Exception $e) { $db->rollBack(); throw $e; }

            $this->redirectSimple('fiche');
        }
    }

    function reinscrireAction()
    {
        $this->view->app = $app = $this->_helper->Inscription();
        $this->metas(array('DC.Title' => "Éditer l'inscription"));
        $this->view->individu = $individu = $app->findParentIndividus();
        $this->assert(null, $individu, 'inscrire',
        "Vous n'avez pas le droit d'inscrire ".$individu->getFullname()." dans une unité.");

        $this->view->model = $m = new Wtk_Form_Model('inscription');
        $this->view->unite = $unite = $app->findParentUnites();
        $i = $m->addEnum('role', 'Rôle', $app->role);
        $roles = $individu->findRolesCandidats($unite, false);
        foreach($roles as $role)
            $i->addItem($role->id, $role->titre);
        $m->addString('titre', 'Titre', $app->titre);
        $m->addDate('debut', 'Début', $app->debut);
        $i0 = $m->addBool('clore', 'Clore', (bool) $app->fin);
        $i1 = $m->addDate('fin', 'Fin', $app->fin);
        $m->addConstraintDepends($i1, $i0);
        $m->addNewSubmission('enregistrer', "Enregistrer");

        if ($m->validate()) {
            $app->role = $m->role;
            $app->titre = $m->titre;
            $app->debut = $m->debut;
            if ($m->clore)
                $app->fin = $m->fin;
            else
                $app->fin = null;

            $db = $app->getTable()->getAdapter();
            $db->beginTransaction();
            try {
                $app->save();
                $this->logger->info("Inscription éditée");
                $this->_helper->Flash->info("Inscription éditée");
                $db->commit();
            }
            catch (Exception $e) { $db->rollBack(); throw $e; }

            $this->redirectSimple('fiche', 'individus', null, array('individu' => $individu->slug), true);
        }
    }

    function desinscrireAction()
    {
        $this->view->app = $app = $this->_helper->Inscription();
        $this->metas(array('DC.Title' => "Annuler l'inscription"));
        $this->view->individu = $individu = $app->findParentIndividus();
        $this->assert(null, $individu, 'inscrire',
        "Vous n'avez pas le droit de désinscrire ".$individu->getFullname()." d'une unité.");

        $this->view->model = $m = new Wtk_Form_Model('annuler');
        $m->addBool('confirmer', "Je confirme la suppression de cette inscription dans l'historique");
        $m->addNewSubmission('continuer', "Continuer");

        if ($m->validate()) {
            if ($m->confirmer) {
                $db = $app->getTable()->getAdapter();
                $db->beginTransaction();
                try {
                    $app->delete();
                    $this->logger->info("Inscription supprimée",
                    $this->_helper->Url('fiche', 'individus', null,
                    array('individu' => $individu->slug), true));
                    $this->_helper->Flash->warn("Inscription supprimée");
                    $db->commit();
                }
                catch (Exception $e) { $db->rollBack(); throw $e; }
            }
            else {
                $this->_helper->Flash->info("Suppression annulée");
            }

            $this->redirectSimple('fiche', 'individus', null, array('individu' => $individu->slug), true);
        }
    }

    function adminAction()
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
            $this->view->apps = $m = new Wtk_Form_Model('apps');

            $tu = new Unites;
            $us = $tu->fetchAll(null);
            $eu = array();
            foreach($us as $u)
                $eu[$u->id] = mb_substr($u->getFullName(), 0, 32);

            $tr = new Roles;
            $rs = $tr->fetchAll(null, 'ordre');
            $er = array();
            foreach($rs as $r)
                $er[$r->id] = substr($r->slug, 0, 7);

            $i = $m->addTable('appartenances', "Appartenances",
            array('unite'        => array('Enum',        'Unité',$eu),
            'role'        => array('Enum',        'Role',        $er),
            'titre'        => array('String',        'Titre'),
            'debut'        => array('Date',        'Début'),
            'clore'        => array('Bool',        'Clore', false),
            'fin'        => array('Date',        'Fin')));

            foreach($as as $a)
                $i->addRow($a->unite, $a->role, $a->titre, $a->debut, (bool) $a->fin, $a->fin);

            $m->addNewSubmission('enregistrer', 'Enregistrer');

            if ($m->validate()) {
                $t = new Appartenances;
                $db = $t->getAdapter();
                $db->beginTransaction();
                try {
                    foreach($as as $a)
                        $a->delete();

                    foreach($i as $row) {
                        $data = array('individu' => $individu->id,
                        'unite' => $row->unite,
                        'role' => $row->role,
                        'titre' => $row->titre,
                        'debut' => $row->debut);
                        if ($row->clore)
                            $data['fin'] = $row->fin;
                        else
                            $data['fin'] = null;
                        $t->insert($data);
                    }

                    $this->logger->info("Inscription éditée",
                    $this->_helper->Url('fiche', 'individus', null,
                    array('individu' => $individu->slug), true));

                    $db->commit();
                }
                catch (Exception $e) { $db->rollBack(); throw $e; }

                $this->redirectSimple('fiche', 'individus', null, array('individu' => $individu->slug));
            }
        }
    }

    function supprimerAction()
    {
        $this->view->individu = $i = $this->_helper->Individu();
        $this->assert(null, $i, 'supprimer',
        "Vous n'avez pas le droit de supprime cette fiche.");

        $this->metas(array('DC.Title' => 'Supprimer '.$i->getFullname()));

        $this->view->model = $m = new Wtk_Form_Model('desinscrire');
        $m->addBool('confirmer',"Je confirme la destruction de cette fiche.", false);
        $m->addNewSubmission('continuer', 'Continuer');

        if ($m->validate()) {
            if ($m->get('confirmer')) {
                $db = $i->getTable()->getAdapter();
                $db->beginTransaction();
                try {
                    $this->logger->warn("Suppression de ".$i->getFullname(),
                    $this->_helper->Url('individus', 'admin'));
                    $i->delete();
                    $db->commit();
                }
                catch (Exception $e) { $db->rollBack(); throw $e; }

                $this->_helper->Flash->info("Fiche supprimée");
                $this->redirectSimple('individus', 'admin');
            }
            else {
                $this->redirectSimple('fiche', 'individus', null, array('individu' => $i->slug));
            }
        }
    }
}
