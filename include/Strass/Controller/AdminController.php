<?php

require_once 'Strass/Citation.php';

function strass_admin_count_level($count, $warn, $critical)
{
    if ($count >= $critical)
        return 'critical';
    if ($count >= $warn)
        return 'warn';
    return 'good';
}

class AdminController extends Strass_Controller_Action
{
    public $_titreBranche = 'Administration';
    public $_afficherMenuUniteRacine = true;

    function init()
    {
        parent::init();

        $this->assert(null, 'site', 'admin', "Espace réservé aux administrateurs");
    }

    function indexAction()
    {
        $this->metas(array('DC.Title' => 'Administration'));

        $this->actions->append("Paramètres du site", array('action' => 'parametres'));

        $this->view->indicateurs = $m = new Wtk_Table_Model('label', 'url', 'compteur', 'level');
        $config = Zend_Registry::get('config');
        $m->append('Version', null, Strass_Version::PROJET, array('version-produit', 'notice'));
        $m->append('Version des données', null, Strass_Version::dataCurrent(),
        array(
            'version-data',
            strass_admin_count_level(Strass_Version::DATA - Strass_Version::dataCurrent(),
            1, 1)));
        $m->append('Association', null, strtoupper($config->system->association), 'notice');
        $t = new Inscriptions;
        $count = $t->countRows();
        $m->append(
            "Inscriptions à valider", $this->_helper->Url('valider', 'membres'),
            $count, strass_admin_count_level($count, 1, 5));

        $t = new Unites;
        $count = $t->countRows();
        $m->append(
            "Unités", $this->_helper->Url('unites'),
            $count, strass_admin_count_level(0-$count, 0, 0));

        $t = new Individus;
        $count = $t->countRows();
        $m->append(
            "Fiches d'individu", $this->_helper->Url('index', 'individus'),
            $count, 'notice');

        $t = new Users;
        $count = $t->countRows();
        $m->append(
            "Membres", $this->_helper->Url('index', 'individus', null, array('filtre' => 'membres')),
            $count, 'notice');

        $t = new Citation;
        $count = $t->countRows();
        $m->append("Citations",
        $this->_helper->Url('index', 'citation'),
        /* réellement, on s'en fout des citations, c'est optionnel. Le but de
           ce compteur être d'être le point l'entrée pour activer la
           fonctionnalité. Pour l'activer, il faut rentrer une citation. On
           accède ensuite aux autres citations depuis la barre latérale. */
        $count, 'notice');

        $this->view->log = $m = new Wtk_Table_Model('date', 'level', 'logger', 'label', 'url',
        'prenom-nom', 'fiche', 'detail-url');
        $t = new Logs;
        $events = $t->fetchAll($t->select()->order('date DESC')->limit(20));
        foreach ($events as $e) {
            $url = $this->_helper->Url('event', null, null, array('id' => $e->id));
            $u = $e->findParentUsers();
            if ($u) {
                $i = $u->findParentIndividus();
                $pn = $i->getFullname();
                $fiche = $this->_helper->Url('fiche', 'individus', null, array('individu' => $i->slug));
            }
            else {
                $pn = 'Visiteur';
                $fiche = null;
            }
            $m->append(
                $e->date,
                strtolower($e->level),
                $e->logger,
                $e->message,
                $e->url,
                $pn, $fiche,
                $url);
        }

        $this->view->connexions = $m = new Wtk_Table_Model('date', 'prenom-nom', 'fiche');
        $t = new Users;
        $users = $t->fetchAll($t->select()->where('last_login')->order('last_login DESC')->limit(8));
        foreach ($users as $u) {
            $i = $u->findParentIndividus();
            $m->append($u->last_login,
            $i->getFullname(false, false),
            $this->_helper->Url('fiche', 'individus', null, array('individu' => $i->slug)));
        }
    }

    function logAction()
    {
        $t = new Logs;

        $this->metas(array('DC.Title' => 'Journal système'));
        $this->branche->append();

        $s = $t->select()->from('log')->order('date DESC');
        $this->view->events = new Strass_Pages_Model_Rowset($s, 30, $this->_getParam('page'));
    }

    function eventAction()
    {
        $t = new Logs;
        $this->view->event = $event = $t->findOne($this->_getParam('id'));

        $this->metas(array('DC.Title' => 'Évènement'));
        $this->branche->append("Journal système", array('action' => 'log', 'id' => null));
        $this->branche->append();
    }

    function parametresAction()
    {
        $this->metas(array('DC.Title' => 'Paramètres'));
        $this->branche->append();

        $config = Zend_Registry::get('config');
        $this->view->model = $m = new Wtk_Form_Model('parametres');
        $g = $m->addGroup('metas', "Informations");
        $g->addString('title', 'Titre', $config->metas->title);
        $g->addString('short_title', 'Titre court', $config->system->short_title);
        $g->addString('subject', 'Mots clefs', $config->metas->subject);
        $g->addString('author', 'Créateur du site', $config->metas->author);
        $g->addInteger('creation', 'Date de création du site', $config->metas->creation);

        $g = $m->addGroup('system', 'Système');
        $enum = array();
        foreach(Wtk_Document_Style::listAvailables() as $style) {
            $enum[$style->id] = $style->title;
        }
        $g->addEnum('style', 'Style', $config->system->style, $enum);
        $g->addString('admin', 'E-mail système', $config->system->admin);
        $g = $g->addGroup('mail');
        $g->addBool('enable', 'Envoyer les mails', $config->system->mail->enable);
        $m->addNewSubmission('enregistrer', 'Enregistrer');

        if ($m->validate()) {
            $new = new Strass_Config_Php('strass', $m->get());
            /* Migration en douceur de mouvement vers association. */
            if ($config->system->mouvement) {
                $new->system->association = $config->system->mouvement;
                unset($new->system->mouvement);
            }
            $new->system->short_title = $new->metas->short_title;
            unset($new->metas->short_title);
            $config->merge($new);
            $config->write();
            $this->logger->warn("Configuration mise-à-jour");
            $this->redirectSimple('index');
        }
    }

    function unitesAction()
    {
        $this->metas(array('DC.Title' => 'Les unités'));
        $this->branche->append();
        $this->actions->append("Fonder",
        array('action' => 'fonder',
        'controller' => 'unites'));

        $t = new Unites;
        $this->view->unites = $m = new Wtk_Table_Model_Tree(
            'nom', 'accueil', 'statut',
            'chef', 'fiche-chef', 'inscrits',
            'url-supprimer',
            'flags');

        $unites = $t->fetchAll();
        $pathes = array();
        foreach ($unites as $unite) {
            if ($unite->parent) {
                $parent = $unite->findParentUnites();
                $ppath = $pathes[$parent->slug];
            }
            else {
                $ppath = array();
            }

            $apps = $unite->findAppartenances(null);
            $inscrits = $apps->count();
            $chef = $unite->findChef(null);
            $level = ($inscrits == 0 || !$chef) ? 'warn' : null;
            $path = $m->append(
                $ppath,
                $unite->getFullname(),
                $this->_helper->Url('index', 'unites', null, array('unite' => $unite->slug)),
                $unite->isFermee() ? 'fermée' : 'ouverte',
                $chef ? $chef->getFullname() : 'Inconnu',
                $chef ? $this->_helper->Url('fiche', 'individus', null,
                array('individu' => $chef->slug)) : null,
                "${inscrits} inscrits",
                $this->_helper->Url('supprimer', 'unites', null, array('unite' => $unite->slug)),
                array($unite->isFermee() ? 'fermee' : 'ouverte',
                $unite->findParentTypesUnite()->slug,
                $level,
                )
            );
            $pathes[$unite->slug] = $path;
        }
    }
}
