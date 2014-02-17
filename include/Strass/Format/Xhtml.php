<?php

class Strass_Format_Xhtml extends Strass_Format_Wtk
{
  protected	$_suffix = 'xhtml';
  protected	$_mimeType = 'application/xhtml+xml';
  protected	$_title = 'XHTML';
  protected	$_wtkRender = 'Xhtml';
  protected	$_renderAddons = true;

  function __construct()
  {
    $this->_mimeType = 'text/html';
  }

  function _preRender($controller)
  {
    parent::_preRender($controller);
    try {
      $flash = Strass_Flash::current();
      $d = $controller->view->document->addDialog()
	->addFlags($flash->level)
	->setId('flash');
      $d->addParagraph($flash->message)->addFlags('message');
      $d->addParagraph($flash->detail)->addFlags('detail');
      $flash->clear();
    }
    catch (Strass_Flash_Empty $e) {}
  }
}
