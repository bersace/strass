<?php

abstract class Strass_Format_Wtk extends Strass_Format
{
  protected	$_viewSuffix = 'wtk';
  protected	$_wtkRender;

  function _preRender($controller)
  {
    $config = Zend_Registry::get('config');
    $page = Zend_Registry::get('page');

    /* création du document, widget racine */
    $request = $controller->getRequest();
    $cn = strtolower($request->getControllerName());
    $an = strtolower($request->getActionName());
    $mn = strtolower($request->getModuleName());

    $mouvement = $config->get('system/mouvement');

    $view = $controller->view;
    $document = new Wtk_Document($page->metas);
    $document->sitemap = '/sitemap';
    $document->addFlags($mn, $cn, $an);
    $document->setStyle(new Wtk_Document_Style($config->get('system/style', 'strass'), 'data/styles/'));
    $document->addStyleComponents('layout', 'common', $cn, $mn, $mouvement);

    if ($view->unite)
      $unite = $view->unite;
    else {
	try {
	  $t = new Unites;
	  $unite = $t->findRacine();
	}
	catch (Exception $e) {
	  $unite = null;
	}
      }

    if ($unite)
      $document->addFlags($unite->slug, $unite->findParentTypesUnite()->slug);
    $document->addFlags(Strass::onDevelopment() ? 'development' : 'production');

    $document->addFlags($mouvement);
    $document->header->addFlags($mouvement);
    $document->footer->addSection('wrapper');

    $link = new Wtk_Link('/', $page->metas->site);
    $document->header->setTitle($link);

    foreach($page->formats as $format) {
      if ($format->suffix != $this->suffix) {
	$document->addAlternative($controller->view->url(array('format' => $format->suffix)),
				  $format->title, $format->mimeType);
      }
    }

    $view->page = $page;
    $view->document = $document;
  }

  protected function _render($view)
  {
    $render = Wtk_Render::factory($view->document, $this->_wtkRender);
    return $render->render();
  }
}