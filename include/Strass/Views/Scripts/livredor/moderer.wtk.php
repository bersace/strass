<?php

class Scout_Pages_Renderer_Livredor extends Wtk_Pages_Renderer
{
	protected $view;

	function __construct($view, $href)
	{
		parent::__construct($href);
		$this->view = $view;
	}

	function renderContainer($root = null)
	{
		return new Wtk_List();
	}

	function render($id, $message, $list)
	{
		$urlOptions = array('action' => 'valider',
				    'auteur' => $message->auteur,
				    'date' => $message->date);
							 
		$i = $list->addItem();
		$i->addText($message->message);
		$i->addParagraph(new Wtk_Inline('posté par **'.$message->auteur.'** '.
						'le '.strftime('%d-%m-%Y', strtotime($message->date)).'.'))
			->addFlags('signature');
		$i->addParagraph($this->view->lien($urlOptions + array('verdict' => 'accepter'),
					     'Accepter'),
				 ' ou ',
				 $this->view->lien($urlOptions + array('verdict' => 'refuser'),
						   'refuser'),
				 ' ce message.')->addFlags('validation');
	}
}

$this->document->addStyleComponents('signature');
$s = $this->content->addSection('livredor', "Livre d'or");
if ($this->messages->count()) {
	$p = $s->addPages(null,
			  $this->messages,
			  new Scout_Pages_Renderer_Livredor($this, $this->url(array('page' => '%i'))));
 }
 else {
	 $s->addParagraph("Aucun message à valider.");
 }