<?php
require_once 'ICS.php';

class Strass_Format_ICS extends Strass_Format
{
  protected	$_suffix	= 'ics';
  protected	$_mimeType	= 'text/calendar';
  protected	$_title		= 'iCalendar';
  protected	$_viewSuffix	= 'ics';
  protected	$_renderFooter	= false;

  protected function _preRender($controller)
  {
    $page = Zend_Registry::get('page');
      $controller->view->ics = new ICS($page->metas->get('DC.Title.alternative'));
    $tw = new Text_Wiki;
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
    $controller->view->tw = $tw;
  }

  protected function _render($view)
  {
    return $view->ics->render();
  }

  function getFilename($view)
  {
    return $view->ics->getFilename();
  }
}