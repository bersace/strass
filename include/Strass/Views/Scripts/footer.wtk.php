<?php

$s = $this->document->getFooter()->current()->addSection('about', "À propos");

$created = $this->document->metas->get('DC.Date.created');
$l = $s->addList();
$l->addItem('© '.$created.($created == date('Y') ? '' : '-'.date('Y'))." ".
	    $this->document->metas->get('DC.Creator'))->addFlags('copyright');


$l->addItem()->addFlags('legal')
->addLink($this->url(array('controller' => 'statiques', 'action' => 'index', 'page' => 'legal'), true),
          'Mentions légales');

$l->addItem($this->page->metas->get('DC.Creator'))->addFlags('author');
$l->addItem(strftime('%x', strtotime($this->page->metas->get('DC.Date.available'))))->addFlags('date');
$l->addItem()->addFlags('strass')
->addLink('http://gitorious.org/strass', 'Propulsé par Strass');
