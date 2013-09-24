<?php

$f = $this->document->getFooter();

$created = $this->document->metas->get('DC.Date.created');
$f->addParagraph('Copyright © '.$created.($created == date('Y') ? '' : ' - '.date('Y')).' '.$this->document->metas->get('DC.Creator').' - ')->addFlags('copyright')
->addLink($this->url(array('controller' => 'statiques',
                           'action' => 'index',
                           'page' => 'legal')),
          'Mentions légales');
                            
$p = $f->addParagraph()->addFlags('metas');
$p->addSpan($this->page->metas->get('DC.Creator'))->addFlags('author');
$p->addSpan($this->page->metas->organization)->addFlags('organization');
$p->addSpan(strftime('%d-%m-%Y', strtotime($this->page->metas->get('DC.Date.available'))))->addFlags('date');

if (is_readable($logo = "resources/styles/".$this->document->default_style->id."/logo.png"))
	$f->addImage($logo, "logo", "Logo")->addFlags('logo');
