<?php

$this->document->setTitle(new Wtk_Container(new Wtk_Inline("Supprimer l'article "),
						 $this->lienArticle($this->article)));
$f = $this->document->addForm($this->model);
$f->addCheck('confirmer');
$f->addForm_ButtonBox()->addForm_Submit($this->model->getSubmission('continuer'));
