<?php

class Scout_Pages_Renderer_Photo extends Wtk_Pages_Renderer
{
  function render($id, $photo, $c)
  {
    $a = $photo->findParentActivites();
    $p = new Wtk_Image($photo->getCheminImage(),
		       $photo->titre,
		       $photo->titre);
    $p->useViewHelper();

    // mieux un div ou un p ?
    $c->addParagraph($p)
      ->addFlags('photo');

    return $c;
  }
}

$this->document->addStyleComponents('signature');
$s = $this->content->addSection('photo', ucfirst($this->photo->titre));
$s->addPages(null, $this->model,
	     new Scout_Pages_Renderer_Photo($this->url(array('photo' => '%i')).'#photo',
							  false,
							  array('previous'	=> "Précédente",
								'next'		=> "Suivante")));
$s->addText("Prise le ".strftime('%d/%m/%Y à %Hh%M', strtotime($this->photo->date)).
	    " lors de ".$this->activite->intitule.".\n\n".
	    $this->photo->desc);

if ($this->commentaires->count()) {
	$ss = $s->addSection('commentaires', "Commentaires");
	foreach($this->commentaires as $i => $c) {
		$sss = $s->addSection(null)->addFlags('commentaire', $i%2 ? 'even' : 'odd');
		$sss->addText($c->commentaire);
		$sss->addParagraph($this->signature($c))->addFlags('signature');
	}
 }