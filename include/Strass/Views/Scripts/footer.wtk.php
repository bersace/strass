<?php

require_once 'Strass/Statique.php';

$s = $this->document->getFooter()->current()->addSection('about', "À propos");

$created = $this->document->metas->get('DC.Date.created');
$l = $s->addList();
$l->addItem(
    '© '.$created.($created == date('Y') ? '' : '-'.date('Y'))." ".
    $this->document->metas->get('DC.Creator'))->addFlags('copyright');

$page = new Statique('legal');
if ($page->readable() || $this->assert(null, $page, 'editer'))
    $l->addItem()->addFlags('legal')
      ->addLink(
          $this->url(array('controller' => 'statiques', 'action' => 'index', 'page' => 'legal'), true),
          'Mentions légales');

$l->addItem($this->page->metas->get('DC.Title'))->addFlags('title');
$l->addItem($this->page->metas->get('DC.Creator'))->addFlags('author');
$l->addItem(strftime('%x', strtotime($this->page->metas->get('DC.Date.available'))))->addFlags('date');
$l->addItem()->addFlags('strass')
  ->addLink('https://github.com/bersace/strass', 'Propulsé par Strass');
