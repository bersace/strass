<?php

$citations = $this->citations->fetchAll(null, 'date DESC', 100);

foreach ($citations as $citation) {
	$text = '> '.str_replace("\n", "\n> ", $citation->citation)."\n**".$citation->auteur."**";
	$this->feed['entries'][] = array('title'	=> $citation->auteur.' : '.wtk_first_words($citation->citation,
												   64, ' …'),
					 'link'		=> $this->url(array('controller'=> 'citations',
									    'action'	=> 'index',
									    'format'	=> 'xhtml'),
								      null,
								      true)."#".wtk_strtoid($citation->auteur.' '.$citation->date),
					 'description'	=> $this->tw->transform($text, 'Plain'),
					 'content'	=> $this->tw->transform($text),
					 'lastUpdate'	=> strtotime($citation->date));
  }

