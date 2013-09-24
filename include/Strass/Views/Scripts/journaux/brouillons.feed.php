<?php

$count = 0;
foreach ($this->brouillons as $article) {
	// limiter à 100 articles dans le flux RSS.
	if (++$count > 100)
		break;

	$this->feed['entries'][] = array('title'	=> $article->titre,
					 'link'		=> $this->url(array('controller'=> 'journaux',
									    'action'	=> 'consulter',
									    'journal'	=> $article->journal,
									    'date'	=> $article->date,
									    'article'	=> $article->id,
									    'format'	=> 'xhtml'),
								      null,
								      true),
					 'description'	=> $this->tw->transform($article->boulet, 'Plain'),
					 'content'	=> $this->tw->transform($article->article),
					 'lastUpdate'	=> strtotime($article->date.' '.$article->heure),
					 'category'	=> array(array('term' => $article->rubrique)));
  }

