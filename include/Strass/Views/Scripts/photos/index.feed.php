<?php

foreach ($this->photos as $photo) {
	$description = "[[image http://".$_SERVER['HTTP_HOST']."/".$photo->getCheminVignette()."]]\n\n";
	$content = "[[image http://".$_SERVER['HTTP_HOST']."/".$photo->getCheminImage()."]]\n\n".$photo->desc."\n";
	$activite = $photo->findParentActivites();
	$this->feed['entries'][] = array('title'	=> wtk_ucfirst($activite->getIntitule()).' : '.$photo->titre,
					 'link'		=> $this->url(array('controller'=> 'photos',
									    'action'	=> 'voir',
									    'activite'	=> $photo->activite,
									    'photo'	=> $photo->id,
									    'format'	=> 'xhtml'),
								      null,
								      true),
					 'description'	=> $this->tw->transform($description, 'Plain'),
					 'content'	=> $this->tw->transform($content),
					 'lastUpdate'	=> strtotime($photo->date));
  }

