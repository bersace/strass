<?php

class Strass_View_Helper_Commentaire
{
  protected	$view;

  public function setView($view)
  {
    $this->view = $view;
  }

  public function commentaire($commentaire)
  {
    $s = new Wtk_Section;
    $s->addFlags('commentaire');
    $p = $s->addParagraph($this->view->lienIndividu($commentaire->findParentIndividus()))
      ->addFlags('auteur');
    $p->tooltip = strftime('le %d-%m-%Y à %H:%M', strtotime($commentaire->date));
    $s->addText($commentaire->message);

    return $s;
  }
}
