<?php

if (count($this->page->formats) > 1) {
	$s = $this->addons->addSection('formats', 'Exporter');
	$l = $s->addList();
	foreach($this->page->formats as $format) {
		if ($format == $this->page->format)
			continue;

		$l->addItem($this->lien(array('format' => $format->suffix),
					$format->title))
			->addFlags($format->suffix);
	}
 }