<?php

class Scout_Pages_RendererCitation extends Wtk_Pages_Renderer
{
	function renderContainer($root = null)
	{
		return new Wtk_List;
	}

	function render($id, $citation, $liste)
	{
		$i = $liste->addItem()->addFlags('citation');
		$i->addParagraph("« ".$citation->citation." »")->addFlags('citation');
		$i->addParagraph($citation->auteur)->addFlags('signature');
	}
}

$this->document->addStyleComponents('signature');
$s = $this->document;
$s->addPages(null,
	     new Wtk_Pages_Model_Table($this->citations, null, 'date DESC', 10,
				       $this->current),
	     new Scout_Pages_RendererCitation($this->url(array('page' => '%i')),
					      true,
					      array('previous' => 'Précédentes',
						    'next' => 'Suivantes')));