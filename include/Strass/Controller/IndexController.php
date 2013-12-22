<?php

class IndexController extends Strass_Controller_Action
{
  public function indexAction()
  {
	  $this->redirectSimple('index', 'unites');
  }
}
