<?php

class Strass_View_Helper_LienDocument {
	protected $view;

	public function setView($view)
	{
		$this->view = $view;
	}

	public function lienDocument($document) {
		return new Wtk_Link($document->getUri(),
				    $document->titre);

	}
  }

