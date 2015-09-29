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

    $style = $config->get('system/style', 'joubert');
    try {
        $document->setStyle(Wtk_Document_Style::factory($style));
    }
    catch (Wtk_Document_Style_NotFound $e) {
        error_log("Style " . $style . " inconnu.");
    }

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