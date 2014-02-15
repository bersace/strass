<?php

abstract class Strass_Controller_Action extends Zend_Controller_Action implements Zend_Acl_Resource_Interface
{
  protected $_titreBranche = '';
  protected $_availableFormats = array();
  protected $_formats = array('xhtml');
  protected $resourceid;
  public $_helper = null;

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
      /* instanciation de la page courante */
      $metas = $config->site->metas;
      $this->page = $page = new Strass_Page(new Wtk_Metas(array('DC.Title'		=> $metas->title,
								'DC.Title.alternative'	=> $metas->title,
								'DC.Subject'		=> $metas->subject,
								'DC.Language'		=> $metas->language,
								'DC.Creator'		=> $metas->author,
								'DC.Date.created'	=> $metas->creation,
								'DC.Date.available'	=> strftime('%Y-%m-%d'),
								'organization'	=> $metas->organization,)));
      $page->addon(new Strass_Addon_Menu);
      $this->branche = $page->addon(new Strass_Addon_Branche);
      $page->addon(new Strass_Addon_Navigateurs);
      $this->connexes = $page->addon(new Strass_Addon_Liens('connexes', 'Pages connexes'));
      $page->addon(new Strass_Addon_Formats);
      $this->actions = $page->addon(new Strass_Addon_Liens('admin', 'Administrer'));
      $page->addon(new Strass_Addon_Console($this->_helper->Auth));
      $page->addon(new Strass_Addon_Citation);

      if ($config->short_title)
	$this->branche->append($label, array(), array(), true);

      if (!$this instanceof Strass_Controller_ErrorController)
	$this->branche->append($this->_titreBranche,
			       array('controller' => strtolower($this->_request->getControllerName())),
			       array(), true);

      Zend_Registry::set('page', $page);
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

    // lister les formats disponibles
    $formats = require('include/Strass/formats.php');
    foreach($formats as $format)
      if ($f = Strass_Format::factory($format))
	$this->_availableFormats[$f->suffix] = $f;

    if (!array_key_exists($this->_getParam('format'), $this->_availableFormats))
      throw new Strass_Controller_Action_Exception("Format inconnu");

    $this->initPage();
  }

  protected function redirectUrl($urlOptions = array(), $route = null, $reset = false)
  {
    $url = $this->_helper->Url->url($urlOptions, $route, $reset);
    $this->_redirect($url, array('prependBase' => false,
				 'exit' => true));
  }

  protected function redirectSimple($action, $controller = null, $module = null, array $params = null, $reset = false)
  {
    $val = array('action', 'controller', 'module');
    foreach($val as $v)
      if ($$v !== null)
	$params[$v] = $$v;

    $url = $this->_helper->Url->url($params, null, $reset);
    $this->_redirect($url, array('prependBase' => false,
				 'exit' => true));
  }

  function assert($role = null, $resource = null, $action = null, $message = null)
  {
    $role = $role ? $role : Zend_Registry::get('user');
    if ($role->username == 'nobody' && $message) {
      $this->_helper->Auth->http();
      $role = Zend_Registry::get('user');
    }
    $acl = Zend_Registry::get('acl');
    $action = $action ? $action : $this->_getParam('action');
    if (is_array($resource) || $resource instanceof Iterator) {
      $allowed = false;
      foreach($resource as $res)
	$allowed = $acl->isAllowed($role, $res, $action) || $allowed;
    }
    else
      $allowed = $acl->isAllowed($role, $resource, $action);

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

    $view->page = Zend_Registry::get('page');
    try {
      $view->user = Zend_Registry::get('user');
    }
    catch(Exception $e) {}

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
    $page = $this->view->page;
    if (!in_array($format, $this->_formats))
      $format = 'xhtml';
    //throw new Strass_Controller_Action_Exception("Ce document n'est pas disponible dans ce format.");

    foreach($this->_formats as $f)
      $page->addFormat($this->_availableFormats[$f]);

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

  protected function metas(array $metas)
  {
    $metas = new Wtk_Metas($metas);
    $config = Zend_Registry::get('config');
    $page = $this->view->page;

    /*
     * Concaténer certains champs plutôt que les écraser.
     */
    if ($config->site->metas->title) {
      $site = $config->site->metas->title;
    }
    else {
      try {
	$racine = $this->_helper->Unite->racine();
	$site = $racine->getName();
      }
      catch (Strass_Db_Table_NotFound $e) {
	$site = null;
      }
    }

    $parts = array($site,
		   $metas->get('title.alternative.append'),
		   );

    if ($metas->has('DC.Title.alternative')) {
      $parts[] = $metas->get('(DC.Title.alternative');
    }
    elseif ($metas->has('DC.Title')) {
      $parts[] = $metas->get('DC.Title');
    }
    $parts = array_filter($parts);
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
      if (!array_key_exists($format, $this->_availableFormats))
	continue;
      if (!in_array($format, $this->_formats))
	$this->_formats[] = $format;
    }
  }
}
