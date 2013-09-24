<?php

if (isset($this->title)) {
	$p = $_context->parent;
	$h = $p->openSection();
	// Rendu dans l'en-tête.
	$_context->pushParent($h);
	wtk_context($this->title, $_context);
	$this->title->output();
	$_context->popParent();
 }

wtk_children_context($this, $_context);
$this->outputChildren();

if (isset($this->title)) {
	$p->closeSection();
 }