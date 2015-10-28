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
        strtotime(
            Strass_Controller_Action_Helper_Annee::dateFin($annee).
            ' next saturday -4 weeks'));
        $calendrier = $u->findActivites($a);
        if ($calendrier->count()) {
            $debut = substr($calendrier->current()->debut, 0, 10);
            $min_fin = strftime('%Y-%m-%d',
            strtotime(
                Strass_Controller_Action_Helper_Annee::dateFin($annee).
                ' next saturday -6 weeks'));
            $max_fin = strftime('%Y-%m-%d',
            strtotime(
                Strass_Controller_Action_Helper_Annee::dateFin($annee).
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

    function completeData($pm)
    {
        /* Récupère les données supplémentaires en fonctions des
           différentes étapes */
        $ti = new Individus;
        $tr = new Roles;
        $u = $this->unite;
        $m = $pm->data;
        $page = $pm->partialValidate();
        $individu = $cv = $app_active = null;
        $predecesseur = $cv_predecesseur = $app_predecesseur = null;

        /* Récupérer les infos d'un individu déjà en base */
        $key = $m->get('inscription/individu');
        if ($key && $key != '$$nouveau$$') {
            $i = $individu = $ti->findOne($key);
            $app_active = $i->findInscriptionsActives()->current();
            if (
                $app_active
                && $app_active->fin === null
                && $app_active->debut < $m->get('inscription/fin')) {
                $cv = $individu->findAppartenances();
            }
        }

        /* Récupérer les infos d'un éventuel prédécesseur */
        if ($role = $m->get('inscription/role')) {
            list($role, $titre) = explode('__', $role);
            $r = $tr->findOne($role);
            $apps = $u->findFuturPredecesseurs($r, $titre);
            if ($apps->count()) {
                $app_predecesseur = $apps->current();
                $predecesseur = $app_predecesseur->findParentIndividus();
                $cv_predecesseur = $predecesseur->findAppartenances();
            }
        }

        return array(
            'page' => $page,
            'individu' => $individu,
            'cv' => $cv,
            'app_active' => $app_active,
            'predecesseur' => $predecesseur,
            'app_predecesseur' => $app_predecesseur,
            'cv_predecesseur' => $cv_predecesseur,
        );
    }

    function createModel($annee)
    {
        $u = $this->unite;
        $a = $annee;

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

        /* Proposer la succession pour les chefs d'unité et les titres */
        $g = $m->addGroup('succession');
        $g->addBool('succeder', "a passé le flambeau le", false);
        $g->addDate('date', "Succession", $debut);

        return $pm;
    }

    function aiguillage($pm, $data)
    {
        /* Détermine la page suivante. Ça serait sympa si
           Wtk_Pages_Model_Form structurait un peu ça. */
        extract($data);
        $m = $pm->data;
        $gotoEnd = false;

        if ($page == 'fiche' && $individu) {
            if ($m->sent_submission->id == 'continuer') {
                if (
                    $app_active
                    && $app_active->fin === null
                    && $app_active->debut < $m->get('inscription/fin')) {
                    /* Proposer de clore une inscription déjà active */
                    $pm->gotoPage('cloture');
                }
                else
                    /* Sauter l'étape fiche si l'individu est déjà en base et libre */
                    $gotoEnd = true;
            }
            else if ($m->sent_submission->id == 'precedent')
                $pm->gotoPage('inscription');
        }
        else if($page == 'cloture' && !$individu) {
            /* Ne pas présenter la page cloture pour un nouveau ! */
            $gotoEnd = true;
        }

        /* La fin du formulaire peut être d'enregistrer une date de
           passation, et de clore l'inscription du prédécesseur. */
        if ($gotoEnd == true) {
            if ($app_predecesseur) {
                $r = $app_predecesseur->findParentRoles();
                $tu = $r->findParentTypesUnite();
                /* On propose de succéder au chef d'unité, aux titres (aumônier,
                   trésorier, etc.) et aux enfants, car ces derniers sont
                   ordonnés dans leurs unités.

                   Cela évite de proposer la succession aux assistant de maîtrise,
                   aux routiers et au guides aînées. Mais on évite deux Hathi, deux
                   aumôniers, etc. sans forcer.
                */
                if ($r->acl_role == 'chef' || $app_predecesseur->titre || $tu->age_max <= 18) {
                    /* On présélectionne uniquement si on présente */
                    $m->getInstance('succession/succeder')->set(true);
                    $pm->gotoPage('succession');
                }
                else
                    /* Pas de prédécesseur, on termine directement. */
                    $pm->gotoEnd();
            }
            else {
                /* assistant, routier, guide-aînée, … on laisse s'accumuler
                   les inscrits au même rang */
                $pm->gotoEnd();
            }
        }
    }

    function fetch($annee = NULL)
    {
        $pm = $this->createModel($annee);
        $data = $this->completeData($pm);
        $this->aiguillage($pm, $data);

        if ($pm->validate())
            $this->validate($annee, $pm, $data);

        $parente = $this->unite->findParentUnites();
        return array_merge($data,
        array(
            'unite' => $this->unite,
            /* récursion=0 : uniquement la maîtrise */
            'apps' => $this->unite->findAppartenances($annee, 0),
            /* On affiche la maîtrise des CP, Sizaine, etc. */
            'parente' => $parente,
            'apps_parente' => $parente ? $parente->findAppartenances($annee, 0) : array(),
            'model' => $pm,
        ));
    }

    function validate($annee, $pm, $data)
    {
        extract($data);
        $m = $pm->data;
        $u = $this->unite;
        $a = $annee;

        $t = new Appartenances;
        $tr = new Roles;
        $ti = new Individus;
        $db = $t->getAdapter();
        $db->beginTransaction();

        $tu = $u->findParentTypesUnite();

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

            if ($m->get('succession/succeder')) {
                $role = $m->get('inscription/role');
                list($role, $titre) = explode('__', $role);
                $r = $tr->findOne($role);
                $apps = $u->findFuturPredecesseurs($r, $titre);
                $app_predecesseur = $apps->current();
                if ($apps->count()) {
                    $app_predecesseur = $apps->current();
                    $app_predecesseur->fin = $m->get('succession/date');
                    $app_predecesseur->save();
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
}
