<?php

abstract class Strass_Format_Wtk extends Strass_Format
{
  protected	$_viewSuffix = 'wtk';
  protected	$_wtkRender;

  function _preRender($controller)
  {
    /* création du document, widget racine */
    $request = $controller->getRequest();
    $cn = strtolower($request->getControllerName());
    $an = strtolower($request->getActionName());
    $mn = strtolower($request->getModuleName());

    $view = $controller->view;
    $document = new Wtk_Document($view->page->metas);
    $document->addFlags($mn, $cn, $an);
    $config = Zend_Registry::get('config');
    $site = $config->site;
    $document->setStyle(new Wtk_Document_Style($site->style, 'data/styles/'));
    $document->addStyleComponents('layout', $cn, $an, $mn);

    $document->addFlags($site->association);
    $document->header->addFlags($site->association);
    $document->footer->addFlags($site->association);

    $link = new Wtk_Link('/', $site->metas->title);
    $document->header->setTitle($link);


    foreach($controller->view->page->formats as $format) {
      if ($format->suffix != $this->suffix) {
	$document->addAlternative($controller->view->url(array('format' => $format->suffix)),
				  $format->title, $format->mimeType);
      }
    }

    $view->document = $document;
  }

  protected function _render($view)
  {
    $render = Wtk_Render::factory($view->document, $this->_wtkRender);
    return $render->render();
  }
}