<?php

abstract class Strass_Controller_Action extends Zend_Controller_Action implements Zend_Acl_Resource_Interface
{
  protected $_titreBranche = '';
  protected $_formats = array('html');
  protected $resourceid;
  public $_helper = null;
  public $_afficherMenuUniteRacine = false;

  public function getResourceId()
  {
    return $this->resourceid;
  }

  function initPage()
  {
    /* On préserve la page entre les appels à init, cela permet de
       préserver les liens, branches, etc. en cas d'erreur. */
    try {
      return Zend_Registry::get('page');
    }
    catch (Exception $e) {
      $config = Zend_Registry::get('config');

      $t = new Unites;
      $racine = $t->findRacines()->current();

      /* instanciation de la page courante */
      $metas = $config->metas;
      $site = Strass::getSiteTitle();

      $page = new Strass_Page(new Wtk_Metas(array('DC.Title'		=> $metas->title,
						  'DC.Title.alternative'=> $metas->title,
						  'DC.Subject'		=> $metas->subject,
						  'DC.Language'		=> $metas->language,
						  'DC.Creator'		=> $metas->author,
						  'DC.Date.created'	=> $metas->creation,
						  'DC.Date.available'	=> strftime('%Y-%m-%d'),
						  'organization'	=> $metas->organization,
						  'site' => $site)));
      Zend_Registry::set('page', $page);

      $this->branche = $page->addon(new Strass_Addon_Branche);

      $this->connexes = $page->addon(new Strass_Addon_Liens('connexes', 'Pages connexes'));
      $page->addon(new Strass_Addon_Formats);
      $lien = new Wtk_Link($this->_helper->Url('index', 'admin', null, null, true), 'Administrer');
      $this->actions = $page->addon(new Strass_Addon_Liens('admin', $lien));
      $page->addon(new Strass_Addon_Console($this->_helper->Auth));

      $page->addon(new Strass_Addon_Citation);
      $page->addon(new Strass_Addon_Menu);

      if ($config->system->short_title)
	$this->branche->append($config->system->short_title, array(), array(), true);

      if ($this->_afficherMenuUniteRacine && $racine)
	$this->_helper->Unite->liensConnexes($racine, $action='index', $controller='unites');

      if (!$this instanceof Strass_Controller_ErrorController && $this->_titreBranche)
	$this->branche->append($this->_titreBranche,
			       array('controller' => strtolower($this->_request->getControllerName())),
			       array(), true);
      return $page;
    }
  }

  public function init()
  {
    try {
      $this->logger = Zend_Registry::get('logger');
    }
    catch (Exception $e) {
      $this->logger = new Strass_ActionLogger($this);
      Zend_Registry::set('logger', $this->logger);
    }

    $this->initPage();
  }

  function redirectUrl($urlOptions = array(), $route = null, $reset = false)
  {
    $url = $this->_helper->Url->url($urlOptions, $route, $reset);
    $this->_redirect($url, array('prependBase' => false,
				 'exit' => true));
  }

  function redirectSimple($action, $controller = null, $module = null, array $params = null, $reset = false)
  {
    $url = $this->_helper->Url($action, $controller, $module, $params, $reset);
    $this->_redirect($url, array('prependBase' => false,
				 'exit' => true));
  }

  function isAllowed($role, $resource, $action) {
    $acl = Zend_Registry::get('acl');
    if (is_array($resource) || $resource instanceof Iterator) {
      $allowed = false;
      foreach($resource as $res)
	$allowed = $acl->isAllowed($role, $res, $action) || $allowed;
    }
    else
      $allowed = $acl->isAllowed($role, $resource, $action);

    return $allowed;
  }


  /* Si le message est défini, une page d'erreur est définie. Sinon
     retourne un booléen */
  function assert($role = null, $resource = null, $action = null, $message = null)
  {
    $role = $role ? $role : Zend_Registry::get('user');

    $action = $action ? $action : $this->_getParam('action');
    // Premier controle avant auth
    if ($this->isAllowed($role, $resource, $action)) {
      return true;
    }

    if (!$role->isMember() && $message) {
      /* Déclencher l'authentification HTTP Digest */
      $res = $this->_helper->Auth->http();
      /* si pas d'auth HTTP, en génère une page d'erreur, avec le code
	 401. Si le popup d'auth est annulé, c'est la page d'erreur
	 401 qui est affichée. */
      if ($res === false)
	throw new Strass_Controller_Action_Exception_Authentification($message);
      $role = Zend_Registry::get('user');
    }

    $allowed = $this->isAllowed($role, $resource, $action);

    if (!$allowed && $message)
      throw new Strass_Controller_Action_Exception_Forbidden($message);

    return $allowed;
  }

  /*
   * Créer un configure une vue en fournissant deux variables par
   * défaut : document (widget racine) et page (modèle de la page
   * courante).
   */
  function initView ()
  {
    $view = new Zend_View();
    $view->setEncoding('UTF-8');
    $fc = Zend_Controller_Front::getInstance();
    $cs = $fc->getControllerDirectory();
    $prefixes = array_keys($cs);
    foreach ($prefixes as $prefix) {
      $viewdir = dirname(dirname(dirname(__FILE__))).'/'.$prefix.'/Views/';
      $view->addScriptPath($viewdir.'Scripts');
      $view->addFilterPath($viewdir.'Filters', $prefix.'_View_Filter_');
      $view->addHelperPath($viewdir.'Helpers', $prefix.'_View_Helper_');
    }

    return $view;
  }

  /*
   * Travestissement de Zend_Controller_Action::getViewScript
   * pour utilise $controller comme controlleur si défini.
   */
  function getViewScript($action, $controller = false)
  {
    $script = parent::getViewScript($action, is_string($controller) ? true : $controller);

    if (is_string($controller))
      $script = wtk_strtoid($controller).DIRECTORY_SEPARATOR.$script;

    return $script;
  }

  public function preDispatch ()
  {
    $this->view = $this->initView();
  }

  /*
   * Version allégée de render qui n'ajoute rien à la requête (cf
   * postDispatch()).
   */
  public function render($action = null, $controller = false)
  {
    $script = $this->getViewScript($action, $controller);
    return $this->view->render($script);
  }

  /*
   * Génère effectivement le rendu.
   */
  function postDispatch ()
  {
    $format = $this->_getParam('format');
    $page = Zend_Registry::get('page');

    if (!in_array($format, $this->_formats))
      $format = 'html';
    //throw new Strass_Controller_Action_Exception("Ce document n'est pas disponible dans ce format.");

    $available_formats = require(Strass::getPrefix().'include/Strass/formats.php');
    foreach($available_formats as $name) {
      if (!$f = Strass_Format::factory($name))
	continue;

      if (in_array($f->suffix, $this->_formats))
	$page->addFormat($f);
    }

    // choper le format actuel
    $page->selectFormat($format);
    $this->viewSuffix = $page->format->viewSuffix.'.php';

    $response = $this->getResponse();
    // rendu effectif du document.
    $output = $page->format->render($this);
    $response->setHeader('Content-Type', $page->format->mimeType.'; charset=utf-8');
    if ($page->format->download) {
      $filename = $page->format->getFilename($this->view);
      $response->setHeader('Content-Disposition', 'attachment; filename='.urlencode($filename));
    }

    $response->appendBody($output);
  }

  function metas(array $metas)
  {
    $metas = new Wtk_Metas($metas);
    $config = Zend_Registry::get('config');
    $page = Zend_Registry::get('page');

    /*
     * Concaténer certains champs plutôt que les écraser.
     */
    $parts = array($metas->site,
		   $metas->get('title.alternative.append'),
		   );

    if ($metas->has('DC.Title.alternative')) {
      $parts[] = $metas->get('DC.Title.alternative');
    }
    elseif ($metas->has('DC.Title')) {
      $parts[] = $metas->get('DC.Title');
    }
    $parts = array_reverse(array_filter($parts));
    $metas->set('DC.Title.alternative', join(' − ', $parts));

    if ($metas->has('DC.Subject'))
      $metas->set('DC.Subject',
		  $metas->get('DC.Subject').','.$page->metas->get('DC.Subject'));

    $page->metas->merge($metas);
  }

  protected function formats($format0)
  {
    $formats = func_get_args();
    foreach($formats as $format) {
      if (!in_array($format, $this->_formats))
	$this->_formats[] = $format;
    }
  }
}
