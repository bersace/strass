<?php

abstract class Strass_Format_Wtk extends Strass_Format
{
  protected	$_viewSuffix = 'wtk';
  protected	$_wtkRender;

    static function createDocument($metas, $unite=null)
    {
        $config = Zend_Registry::get('config');

        $document = new Wtk_Document($metas);
        $document->header->setTitle(new Wtk_Link('/', $metas->site));
        $document->sitemap = '/sitemap';
        $document->addStyleComponents('layout', 'common', 'web');

        if ($unite)
            $document->addFlags($unite->slug, $unite->findParentTypesUnite()->slug);

        $association = $config->get('system/association');
        $document->addFlags($association);
        $document->header->addFlags($association);

        $style = $config->get('system/style', 'joubert');
        try {
            $document->setStyle(Wtk_Document_Style::factory($style));
        }
        catch (Wtk_Document_Style_NotFound $e) {
            error_log("Style " . $style . " inconnu.");
        }

        if (Strass::onDevelopment()) {
            $document->addFlags('development');
            $document->header
                ->addSection('development')
                ->addFlags('ruban')
                ->addParagraph('Test');
        }
        else {
            $document->addFlags('production');
        }

        return $document;
    }

  function _preRender($controller)
  {
    $page = Zend_Registry::get('page');

    /* création du document, widget racine */
    $request = $controller->getRequest();
    $cn = strtolower($request->getControllerName());
    $an = strtolower($request->getActionName());
    $mn = strtolower($request->getModuleName());

    $view = $controller->view;

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

    $document = self::createDocument($page->metas, $unite);
    $document->addFlags($mn, $cn, $an);
    $document->footer->addSection('wrapper');

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
