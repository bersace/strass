<?php

class Strass_View_Helper_ItemArticle
{
	protected	$view;

	public function setView($view)
	{
		$this->view = $view;
	}

	public function itemArticle($article,
				    $label = null,
				    $action = 'consulter',
				    $controller = 'journaux',
				    $reset = true)
	{
		$c = new Wtk_Container();
		$c->addParagraph($this->view->lienArticle($article));

		$t = $c->addText($article->boulet ? $article->boulet : $article->article);
		$tw = $t->getTextWiki();
		$tw->setRenderConf('Xhtml', 'image', 'base', $article->getDossier());

		$c->addParagraph(new Wtk_Inline("//dans// "),
				 $this->view->lienRubrique($article->findParentRubriques()),
				 ", ",
				 $this->view->signature($article), ".");
		return $c;
	}
}
