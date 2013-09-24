<?php

class Strass_View_Helper_ItemAppartient
{
  protected	$view;

  public function setView($view)
  {
    $this->view = $view;
  }

  public function itemAppartient($app)
  {
    return new Wtk_Container($this->view->lienIndividu($app->findParentIndividus()),
			     new Wtk_Inline(", ".
					    $app->findParentRoles().
					    " depuis le ".
					    strftime('%e/%m/%Y', strtotime($app->debut))));
  }
}
