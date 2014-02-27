<?php

$t = new Articles;
$s = $this->model->select;
$s->limit(100);
$articles = $t->fetchAll($s);
foreach ($articles as $article) {

  $this->feed['entries'][] = array('title'	=> $article->titre,
				   'link'	=> $this->url(array('controller'=> 'journaux',
								    'action'	=> 'consulter',
								    'article'	=> $article->slug,
								    'format'	=> 'xhtml'),
							      null,
							      true),
				   'description'	=> $this->tw->transform($article->getBoulet(), 'Plain'),
				   'content'	=> $this->tw->transform($article->article),
				   'lastUpdate'	=> strtotime($article->getDate()));
}
