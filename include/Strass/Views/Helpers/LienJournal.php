<?php

class Strass_View_Helper_LienJournal
{
  protected	$view;

  public function setView($view)
  {
    $this->view = $view;
  }

  public function lienJournal($journal,
			      $label = null,
			      $action = 'lire',
			      $controller = 'journaux',
			      $reset = true)
  {
    $label = $label ? $label : ucfirst($journal->nom);
    $acl = Zend_Registry::get('acl');
    return $this->view->lien(array('journal' => $journal->id,
				   'action' => $action,
				   'controller' => $controller),
			     $label,
			     $reset);
  }
}
