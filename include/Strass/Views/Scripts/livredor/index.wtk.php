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
		$i = $list->addItem();
		$i->addText($message->message);
		$auteur = Zend_Registry::get('individu') && $message->adelec ?
			"[mailto:".$message->adelec." ".$message->auteur."]" :
			$message->auteur;
		$i->addParagraph(new Wtk_Inline('posté par **'.$auteur.'** '.
						'le '.strftime('%d-%m-%Y', strtotime($message->date)).'.'))
			->addFlags('signature');
	}
}

$this->document->addStyleComponents('signature');
$s = $this->document;
$p = $s->addPages(null,
		  new Wtk_Pages_Model_Table($this->livredor,
					    'public IS NOT NULL',
					    'date DESC',
					    15, $this->current),
		  new Scout_Pages_Renderer_Livredor($this, $this->url(array('page' => '%i'))));
 