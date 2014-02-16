<?php

require_once 'Strass/Citation.php';
require_once 'Strass/Livredor.php';

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

  function init()
  {
    parent::init();

    $this->assert(null, 'site', 'admin', "Espace réservé aux administrateurs.");
  }

  function indexAction()
  {
    $this->metas(array('DC.Title' => 'Administration'));

    $this->actions->append("Paramètres", array('action' => 'parametres'));

    $this->view->indicateurs = $m = new Wtk_Table_Model('label', 'url', 'compteur', 'level');
    $config = Zend_Registry::get('config');
    $m->append('Version', null, Strass_Version::PROJET, array('version-produit', 'notice'));
    $m->append('Version des données', null, Strass_Version::dataCurrent(),
	       array('version-data',
		     strass_admin_count_level(Strass_Version::DATA - Strass_Version::dataCurrent(),
					      1, 1)));
    $m->append('Mouvement', null, strtoupper($config->system->mouvement), 'notice');
    $t = new Inscriptions;
    $count = $t->countRows($t->select());
    $m->append("Inscriptions à valider",
	       $this->_helper->Url('inscriptions', 'membres'),
	       $count, strass_admin_count_level($count, 1, 5));

    $t = new Unites;
    $count = $t->countRows($t->select());
    $m->append("Unités",
	       $this->_helper->Url('unites'),
	       $count, strass_admin_count_level(0-$count, 0, 0));

    $t = new Individus;
    $count = $t->countRows($t->select());
    $m->append("Fiches d'individu",
	       $this->_helper->Url('individus'),
	       $count, 'notice');

    $t = new Users;
    $count = $t->countRows($t->select());
    $m->append("Membres",
	       $this->_helper->Url('index', 'membres'),
	       $count, 'notice');

    $t = new Livredor;
    $count = $t->countRows($t->select()->where('public = 0'));
    $action = $count ? 'moderer' : 'index';
    $m->append("Nouveaux messages du livre d'or",
	       $this->_helper->Url($action, 'livredor'),
	       $count,
	       strass_admin_count_level($count, 1, 10));

    $t = new Citation;
    $count = $t->countRows($t->select());
    $m->append("Citations",
	       $this->_helper->Url('index', 'citation'),
	       /* réellement, on s'en fout des citations, c'est
		  optionnel. Le but de ce compteur être d'être le
		  point l'entrée pour activer la fonctionnalité. Pour
		  l'activer, il faut rentrer une citation. On accède
		  ensuite aux autres citations depuis la barre
		  latérale. */
	       $count, 'notice');

    $this->view->log = $m = new Wtk_Table_Model('date', 'level', 'logger', 'label', 'url',
						'prenom-nom', 'fiche');
    $t = new Logs;
    $events = $t->fetchAll($t->select()->order('date DESC')->limit(20));
    foreach ($events as $e) {
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
      $m->append($e->date,
		 strtolower($e->level),
		 $e->logger,
		 wtk_first_words($e->message, 40),
		 $e->url,
		 $pn, $fiche);
    }

    $this->view->connexions = $m = new Wtk_Table_Model('date', 'prenom-nom', 'fiche');
    $t = new Users;
    $users = $t->fetchAll($t->select()->order('last_login DESC')->limit(8));
    foreach ($users as $u) {
      $i = $u->findParentIndividus();
      $m->append($u->last_login,
		 $i->getFullname(false, false),
		 $this->_helper->Url('fiche', 'individus', null, array('individu' => $i->slug)));
    }
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
    $g->addString('id', 'Identifiant du site', $config->system->id);
    $enum = array();
    foreach(Wtk_Document_Style::listAvailables() as $style) {
      $enum[$style->id] = $style->title;
    }
    $g->addEnum('style', 'Style', $config->system->style, $enum);
    $g->addString('admin', 'E-mail système', $config->system->admin);
    $g = $g->addGroup('mail');
    $i0 = $g->addBool('enable', 'Envoyer les mails', $config->system->mail->enable);
    $i1 = $g->addString('smtp', 'Serveur SMTP', $config->system->mail->smtp);
    $m->addConstraintDepends($i1, $i0);
    $m->addNewSubmission('enregistrer', 'Enregistrer');

    if ($m->validate()) {
      $new = new Strass_Config_Php('strass', $m->get());
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
    $this->view->unites = $m = new Wtk_Table_Model_Tree('nom', 'accueil', 'statut',
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

      $apps = $unite->getApps();
      $actifs = $apps->count();
      $path = $m->append($ppath,
			 wtk_ucfirst($unite->getFullname()),
			 $this->_helper->Url('index', 'unites', null, array('unite' => $unite->slug)),
			 $unite->isFermee() ? 'fermée' : 'ouverte',
			 'Inconnu', null,
			 "${actifs} inscrits",
			 $this->_helper->Url('supprimer', 'unites', null, array('unite' => $unite->slug)),
			 array($unite->isFermee() ? 'fermee' : 'ouverte',
			       $unite->findParentTypesUnite()->slug,
			       $actifs == 0 ? 'warn' : null,
			       )
			 );
      $pathes[$unite->slug] = $path;
    }
  }
}