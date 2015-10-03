<?php

require_once 'Strass/Unites.php';

class SitemapController extends Zend_Controller_Action
{
  public function initView()
  {
    if (null === $this->view) {
      if (Zend_Registry::isRegistered('view')) {
	$this->view = Zend_Registry::get('view');
      } else {
	$this->view = new Zend_View;
	$viewdir = dirname(__FILE__).'/../Views/';
	$this->view->setBasePath($viewdir);
	$this->view->addScriptPath($viewdir.'Scripts');
      }
    }

    return $this->view;
  }

  function indexAction()
  {
    $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
    $viewRenderer->setView($this->initView());

    $pages = array(
		   array('uri' => '/'),
		   array('controller' => 'liens'),
		   array('controller' => 'citation'),
		   );

    /* Les unités */
    $t = new Unites;
    foreach($t->findRacines() as $u) {
      $spages = array();
      foreach($u->findSousUnites(true, true) as $su) {
	array_push($spages, array('controller' => 'unites',
				  'action' => 'index',
				  'params' => array('unite' => $su->slug)));
	array_push($spages, array('controller' => 'documents',
				  'action' => 'index',
				  'params' => array('unite' => $su->slug)));
	array_push($spages, array('controller' => 'unites',
				 'action' => 'archives',
				 'params' => array('unite' => $su->slug)));
      }
      array_push($pages, array('controller' => 'unites',
			       'action' => 'index',
			       'params' => array('unite' => $u->slug),
			       'pages' => $spages));
      array_push($pages, array('controller' => 'unites',
			       'action' => 'archives',
			       'params' => array('unite' => $u->slug)));
      array_push($pages, array('controller' => 'documents',
			       'action' => 'index',
			       'params' => array('unite' => $u->slug)));
    }

    /* Journaux */
    $t = new Journaux;
    foreach ($t->fetchAll() as $j) {
      $articles = array();
      foreach ($j->findArticles('article.public IS NOT NULL OR article.public != 0') as $a) {
	array_push($articles, array('controller' => 'journaux',
				    'action' => 'consulter',
				    'params' => array('article' => $a->slug)));
      }
      array_push($pages, array('controller' => 'journaux',
			       'action' => 'lire',
			       'params' => array('journal' => $j->slug),
			       'pages' => $articles));
    }

    /* Photos promues */
    $t = new Photos;
    $s = $t->select()->where('promotion > 0');
    foreach ($t->fetchAll($s) as $p) {
      array_push($pages, array('controller' => 'photos',
			       'action' => 'voir',
			       'params' => array('photo' => $p->slug)));
    }

    $this->view->nav = new Zend_Navigation($pages);

    $this->getResponse()->setheader('Content-Type', 'text/xml');
    $this->render();
  }
}
