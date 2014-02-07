<?php

class Strass_View_Helper_LienArticle
{
  protected	$view;

  public function setView($view)
  {
    $this->view = $view;
  }

  public function lienArticle($article,
			      $label = null,
			      $action = 'consulter',
			      $controller = 'journaux',
			      $reset = true)
  {
    $label = $label ? $label : wtk_ucfirst($article->titre);
    $acl = Zend_Registry::get('acl');
    $lien = $this->view->lien(array('controller' => $controller,
				    'action'	=> $action,
				    'article'	=> $article->slug),
			      $label,
			      $reset);
    if (!$article->public)
      $lien->addEmphasis(' (brouillon)');
    return $lien;
  }
}
