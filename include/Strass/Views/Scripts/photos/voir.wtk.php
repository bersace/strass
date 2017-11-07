<?php

class Strass_Pages_Renderer_Photo extends Wtk_Pages_Renderer
{
  function render($id, $photo, $c)
  {
    $c->addParagraph()
      ->addFlags('photo')
      ->addImage(
          $photo->getURLImage(),
		 $photo->titre,
		 $photo->titre);

    return $c;
  }
}

$renderer = new Strass_Pages_Renderer_Photo($this->url(array('photo' => '%i')).'#document', false,
					    array('previous'	=> "Précédente",
						  'next'		=> "Suivante"));
$s = $this->document->addSection('visionneuse');
$s->addPages(null, $this->model, $renderer);
;
if ($description = $this->photo->getDescription()) {
    $s = $this->document->addSection('description');
    $s->addText($description);

    $c = $this->photo->findParentCommentaires();
    if ($this->assert(null, $c, 'editer')) {
      $l = $s->addList()->addFlags('adminlinks');
      $l->addItem()->addChild($this->lien(array('controller' => 'commentaires',
						'action' => 'editer',
						'message' => $c->id),
					  "Éditer"));
    }
}

if ($this->commentaires->count() || $this->com_model) {
  $s = $this->document->addSection('commentaires', "Commentaires");
  if ($m = $this->com_model) {
    $f = $s->addForm($this->com_model);
    $f->addEntry('message', 38, 4)->useLabel(false);
    $f->addForm_ButtonBox()->addForm_Submit($m->getSubmission('commenter'));
  }

  $acl = Zend_Registry::get('acl');
  foreach($this->commentaires as $i => $c) {
    $com = $s->addSection(null)->addFlags('commentaire', $i%2 ? 'even' : 'odd');
    $p = $com->addParagraph($this->lienIndividu($c->findParentIndividus()))->addFlags('auteur');
    $p->tooltip = strftime('le %d-%m-%Y à %H:%M', strtotime($c->date));
    $com->addText($c->message);

    if ($this->assert(null, $c, 'editer')) {
      $l = $com->addList()->addFlags('adminlinks');
      $l->addItem()->addChild($this->lien(array('controller' => 'commentaires',
						'action' => 'editer',
						'message' => $c->id),
					  "Éditer"));
      $l->addItem()->addChild($this->lien(array('controller' => 'commentaires',
						'action' => 'supprimer',
						'message' => $c->id),
					  "Supprimer"))
	->addFlags('critical');

    }
  }
}
