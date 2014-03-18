<?php

abstract class Strass_Format_Feed extends Strass_Format
{
	protected	$_viewSuffix = 'feed';
	protected	$_renderFooter = false;
	protected	$_feedClass;

	function _preRender($controller)
	{
		$view = $controller->view;
		$p = Zend_Registry::get('page');
		$m = $p->metas;
		$view->feed = array('title'	=> $m->get('DC.Title'),
				    'link'	=> $view->url(array('format' => 'xhtml'), false, true),
				    'charset'	=> 'UTF-8',
				    'language'	=> 'fr',
				    'entries'	=> array());

		$tw = new Text_Wiki();
		// Désactivation de certaine fonctionnalité peu sécurisé ou utiles dans
		// le cadre d'un wiki uniquement.
		$disable = array('phplookup',	'interwiki',	'wikilink',	'freelink',	'bold',
				 'italic',	'embed',	'include',	'toc');
		foreach($disable as $rule) {
			$tw->disableRule($rule);
		}

		$enable = array('html', 'code', 'translatehtml');
		foreach($enable as $rule) {
			$tw->enableRule($rule);
		}

		// Ajouter la gestion des url relative.
		$options = array('http://',
				 'https://',
				 'ftp://',
				 'gopher://',
				 'news://',
				 'irc://',
				 'file://',
				 'mailto:',
				 'xmpp:',
				 './',
				 '../',
				 );
		$tw->setParseConf('Url', 'schemes', $options);
		$tw->setFormatConf('Xhtml', 'translate', HTML_SPECIALCHARS);
		$tw->setRenderConf('Xhtml', 'image', 'base', './');
		$view->tw = $tw;
	}

	protected function _render($view)
	{
		$class = 'Zend_Feed_'.$this->_feedClass;
		Zend_Loader::loadClass($class);
		$feed = new $class(null, null, new Zend_Feed_Builder($view->feed));
		return $feed->saveXML();
	}
}