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
    return new Wtk_Container("par ",
			     $this->view->lienIndividu($article->findAuteur()),
			     " le ".strftime('%d/%m/%Y',
					     strtotime($article->findParentCommentaires()->date)));
  }
}
