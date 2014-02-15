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

    $m->append('Version', null, Strass_Version::PROJET, array('version-produit', 'good'));
    $m->append('Version des données', null, Strass_Version::dataCurrent(),
	       array('version-data',
		     strass_admin_count_level(Strass_Version::DATA - Strass_Version::dataCurrent(),
					      1, 1)));

    $t = new Inscriptions;
    $count = $t->countRows($t->select());
    $m->append("Inscriptions à valider",
	       $this->_helper->Url('inscriptions', 'membres'),
	       $count, strass_admin_count_level($count, 1, 5));

    $t = new Livredor;
    $count = $t->countRows($t->select()->where('public = 0'));
    $action = $count ? 'moderer' : 'index';
    $m->append("Nouveaux messages du livre d'or",
	       $this->_helper->Url($action, 'livredor'),
	       $count, strass_admin_count_level($count, 1, 10));

    $t = new Unites;
    $count = $t->countRows($t->select());
    $m->append("Unités",
	       $this->_helper->Url('unites'),
	       $count, strass_admin_count_level(0-$count, 0, 0));

    $t = new Individus;
    $count = $t->countRows($t->select());
    $m->append("Fiches d'individu",
	       $this->_helper->Url('individus'),
	       $count, 'good');

    $t = new Users;
    $count = $t->countRows($t->select());
    $m->append("Membres",
	       $this->_helper->Url('index', 'membres'),
	       $count, 'good');

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
	       $count, 'good');

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

  function unitesAction()
  {
    $this->metas(array('DC.Title' => 'Les unités'));

    $this->actions->append(array('label' => "Fonder"),
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
