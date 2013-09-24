<?php

abstract class Knema_Format
{
	// filename suffix
	protected	$_suffix;
	protected	$_mimeType;
	// format name
	protected	$_title;
	// Zend_View script suffix
	protected	$_viewSuffix;
	// whether to render addons
	protected	$_renderAddons = false;
	// whether to render footer.
	protected	$_renderFooter = true;
	protected	$_output;
	// whether to force download
	protected	$_download = false;

	static function factory($format)
	{
		$class = 'Knema_Format_'.$format;
		Zend_Loader::loadClass($class);
		return new $class;
	}

	function render($controller)
	{
		$this->_preRender($controller);
		$controller->view->format = $this->_suffix;
		$this->_output = $controller->render();
		$this->_postRender($controller);
		$this->renderAddons($controller);

		if ($this->_renderFooter)
			$controller->render('footer', true);

		return $this->_render($controller->view);
	}

	protected function _preRender($controller)
	{
	}

	protected function _postRender($controller)
	{
	}

	protected function _render($view)
	{
	}

	function getFilename($view)
	{
		return wtk_strtoid($view->page->metas->get('DC.Title')).'.'.$this->_suffix;
	}

	function renderAddons($controller)
	{
		if (!$this->_renderAddons)
			return;

		$view = $controller->view;
		$view->addons = $view->document->addSection('webaddons');
		$view->addons->level = 1;
		foreach($view->page as $addon) {
			$addon->initView($view);
			$script = $controller->getViewScript($addon->viewScript(), 'addons');
			$view->render($script);
		}
	}

	function __get($field)
	{
		switch($field) {
		case 'suffix':
		case 'mimeType':
		case 'title':
		case 'viewSuffix':
		case 'download':
			$var = '_'.$field;
		        $val = $this->$var;
			break;
	        }
	        return $val;
        }
}