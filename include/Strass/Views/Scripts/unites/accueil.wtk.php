<?php
$this->document->addStyleComponents('accueil', 'effectifs');
$s = $this->content->addSection("accueil", wtk_ucfirst($this->unite->getName())." ".$this->unite->extra);
$s->addPages(null, $this->model,
	     new Strass_Views_PagesRenderer_Unites_Accueil($this,
							   $this->unite->getAnneesOuverte(), $this->annee));
