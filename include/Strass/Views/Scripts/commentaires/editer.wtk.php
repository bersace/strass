<?php

$f = $this->document->addForm($this->model);
$f->addParagraph("Auteur : ", $this->lienIndividu($this->commentaire->findParentIndividus()));
$f->addEntry('message', 38, 8);
$f->addForm_ButtonBox()->addForm_Submit($this->model->getSubmission('enregistrer'));
