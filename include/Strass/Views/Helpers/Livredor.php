<?php

class Strass_View_Helper_Livredor
{
  protected	$view;

  public function setView($view)
  {
    $this->view = $view;
  }

  public function livredor($message)
  {
    $s = new Wtk_Section;
    $s->addFlags('message');
    $p = $s->addParagraph($message->auteur)->addFlags('auteur');
    $p->tooltip = strftime('le %d-%m-%Y à %H:%M', strtotime($message->date));
    $s->addText($message->contenu);
    return $s;
  }
}
