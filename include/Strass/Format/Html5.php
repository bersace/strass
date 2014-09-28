<?php

class Strass_Format_Html5 extends Strass_Format_Wtk
{
  protected	$_suffix = 'html';
  protected	$_mimeType = 'text/html';
  protected	$_title = 'HTML';
  protected	$_wtkRender = 'Html5';
  protected    $_renderAddons = true;


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
