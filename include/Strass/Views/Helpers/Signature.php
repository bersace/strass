<?php

class Strass_View_Helper_Signature
{
  protected	$view;

  public function setView($view)
  {
    $this->view = $view;
  }

  public function signature($article)
  {
    return new Wtk_Container(new Wtk_Inline("par "),
			     $this->view->lienIndividu($article->findParentIndividus()),
			     new Wtk_Inline(" le ".strftime('%d/%m/%Y', strtotime($article->date))));
  }
}
