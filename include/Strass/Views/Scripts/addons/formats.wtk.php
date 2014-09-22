<?php

if (count($this->page->formats) > 1) {
  $s = $this->addons->addSection('formats', 'Exporter');
  $l = $s->addList();

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
