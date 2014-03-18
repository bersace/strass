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
    $label = $label ? $label : $article->titre;
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
