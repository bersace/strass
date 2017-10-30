<?php

if (count($this->page->formats) > 1 or $this->page->download) {
  $s = $this->addons->addSection('formats', 'Exporter');
  $l = $s->addList();

  if ($this->page->download)
      $l->addItem()
          ->addFlags('telechargement')
          ->addLink($this->page->download, 'Télécharger');

  if ($this->document->hasFlag('printable'))
    $l->addItem()->addFlags('print')
      ->addLink('javascript:window.print()', "Imprimer");

  foreach($this->page->formats as $format) {
    if ($format == $this->page->format)
      continue;

    $l->addItem($this->lien(array('format' => $format->suffix),
			    $format->title))
      ->addFlags($format->suffix);
  }
}
