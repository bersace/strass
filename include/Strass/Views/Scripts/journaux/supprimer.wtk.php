<?php

$s = $this->content->addSection('supprimer',
				new Wtk_Container(new Wtk_Inline("Supprimer l'article "),
						  $this->lienArticle($this->article)));
$f = $s->addChild(new Wtk_Form($this->model));
$f->addCheck('confirmer');
$b = $f->addChild(new Wtk_Form_ButtonBox());
$b->addChild(new Wtk_Form_Submit($this->model->getSubmission('continuer')));
