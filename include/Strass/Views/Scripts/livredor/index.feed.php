<?php

$messages = $this->livredor->fetchAll('public IS NOT NULL', 'date DESC', 100);

foreach ($messages as $message) {
	$text = '> '.str_replace("\n", "\n> ", $message->message)."\n**".$message->auteur."**";
	$this->feed['entries'][] = array('title'	=> $message->auteur.' : '.wtk_first_words($message->message,
												   64, ' …'),
					 'link'		=> $this->url(array('controller'=> 'citations',
									    'action'	=> 'index',
									    'format'	=> 'xhtml'),
								      null,
								      true)."#".wtk_strtoid($message->auteur.' '.$message->date),
					 'description'	=> $this->tw->transform($text, 'Plain'),
					 'content'	=> $this->tw->transform($text),
					 'lastUpdate'	=> strtotime($message->date));
  }
