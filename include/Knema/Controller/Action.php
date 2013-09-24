<?php

require_once 'Wtk.php';

abstract class Knema_Controller_Action extends Zend_Controller_Action
{
	protected $_titreBranche = '';
	protected $_availableFormats = array();
	protected $_formats = array('xhtml');
    
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

	public function init()
	{
		$config = Zend_Registry::get('site');

		// lister les formats disponibles
		$formats = $this->_helper->Config('knema/formats');
		foreach($formats as $format)
			if ($f = Knema_Format::factory($format))
				$this->_availableFormats[$f->suffix] = $f;

		if (!array_key_exists($this->_getParam('format'), $this->_availableFormats))
			throw new Knema_Controller_Action_Exception("Format inconnu");

		/* instanciation de la page courante */
		$page = Zend_Registry::get('page');
		$page->addon(new Knema_Addon_Menu);
		$this->branche = $page->addon(new Knema_Addon_Branche);
		$page->addon(new Knema_Addon_Navigateurs);
		$page->addon(new Knema_Addon_Formats);

		$this->branche->append($config->short_title, array(), array(), true);

		if (!$this instanceof Knema_Controller_ErrorController)
			$this->branche->append($this->_titreBranche,
					       array('controller' => strtolower($this->_request->getControllerName())),
					       array(),
					       true);
	}

	public function preDispatch ()
	{
		$this->view = $this->initView();
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
		//throw new Knema_Controller_Action_Exception("Ce document n'est pas disponible dans ce format.");

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

	/**
	 * Tests ACL and trigger a 403 error with @message on failure.
	 */
	protected function assert($role, $resource = null, $priv = null, $message = False)
	{
		$acl = Zend_Registry::get('acl');
		$priv = $priv ? $priv : $this->_getParam('action');
		if (is_array($resource) || $resource instanceof Iterator) {
			$allowed = false;
			foreach($resource as $res)
				$allowed = $acl->isAllowed($role, $res, $priv) || $allowed;
		}
		else
			$allowed = $acl->isAllowed($role, $resource, $priv);

		if (!$allowed && $message)
			throw new Knema_Controller_Action_Exception_Forbidden($message);

		return $allowed;
	}

	protected function metas(array $metas)
	{
		$metas = new Wtk_Metas($metas);
		$site = Zend_Registry::get('site');
		$page = Zend_Registry::get('page');
		/*
		 * Fusionner certains champs plutôt que les écraser.
		 */
		$append = $metas->has('title.alternative.append') ? $metas->get('title.alternative.append').' – ' : '';
		if ($metas->has('DC.Title.alternative')) {
			$metas->set('DC.Title.alternative',
				    $metas->get('DC.Title.alternative').' – '.
				    $append.$site->metas->title);
		}
		elseif ($metas->has('DC.Title')) {
			$metas->set('DC.Title.alternative',
				    $metas->get('DC.Title').' – '.
				    $append.$site->metas->title);
		}
		else {
			$metas->set('DC.Title.alternative', $append.$site->metas->title);
		}

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
