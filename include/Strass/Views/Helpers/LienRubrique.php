<?php

class Strass_View_Helper_LienRubrique
{
  protected	$view;

  public function setView($view)
  {
    $this->view = $view;
  }

  public function lienRubrique($rubrique,
			       $label = null,
			       $action = 'lister',
			       $controller = 'journaux',
			       $reset = true)
  {
    if (!$rubrique) {
      return null;
    }

    $label = $label ? $label : ucfirst($rubrique->nom);
    return $this->view->lien(array('journal'	=> $rubrique->journal,
				   'rubrique'	=> $rubrique->id,
				   'action'	=> $action,
				   'controller'	=> $controller),
			     $label,
			     $reset);
  }
}
