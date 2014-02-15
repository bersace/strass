<?php

class Strass_View_Helper_Citation
{
  public $view;

  public function setView($view)
  {
    $this->view = $view;
  }

  public function citation($citation)
  {
    $s = new Wtk_Section;
    $s->addFlags('citation');
    $p = $s->addParagraph($citation->auteur)->addFlags('auteur');
    $p->tooltip = strftime('le %d-%m-%Y à %H:%M', strtotime($citation->date));
    $s->addParagraph("« ".$citation->texte." »")->addFlags('citation');
    return $s;
  }
}
