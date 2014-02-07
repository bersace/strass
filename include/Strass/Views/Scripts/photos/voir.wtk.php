<?php

class Scout_Pages_Renderer_Photo extends Wtk_Pages_Renderer
{
  function render($id, $photo, $c)
  {
    $c->addParagraph()
      ->addFlags('photo')
      ->addImage($photo->getCheminImage(),
		 $photo->titre,
		 $photo->titre)
      ->addFlags('photo');

    return $c;
  }
}

$this->document->addStyleComponents('signature');
$s = $this->document;
$s->addPages(null, $this->model,
	     new Scout_Pages_Renderer_Photo($this->url(array('photo' => '%i')).'#document',
					    false,
					    array('previous'	=> "Précédente",
						  'next'		=> "Suivante")));
$description = $this->photo->findParentCommentaires()->message;
if ($description) {
  $s->addText($description);
}

if ($this->commentaires->count()) {
	$ss = $s->addSection('commentaires', "Commentaires");
	foreach($this->commentaires as $i => $c) {
		$sss = $s->addSection(null)->addFlags('commentaire', $i%2 ? 'even' : 'odd');
		$sss->addText($c->message);
		$sss->addParagraph($this->signature($c))->addFlags('signature');
	}
 }
