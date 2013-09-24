<?php

$this->document->addStyleComponents('vignette', 'article');
$c = $this->content;

if ($this->participation->boulet) {
	$s = $c->addSection(null, null);
	$s->addFlags('boulet', 'article');
	$s->addText($this->participation->boulet);
 }

if ($this->photo) {
	$s = $c->addSection(null, null);
	$s->addFlags('vignette');
	$s->addChild($this->vignettePhoto($this->photo));
 }

if ($this->participation->rapport) {
	$s = $c->addSection(null, null);
	$s->addFlags('rapport', 'article');
	$s->addText($this->participation->rapport);
 }
 else if (!$this->participation->boulet) {
	 $c->addText("//Pas de rapport pour cette activité. //");
 }
