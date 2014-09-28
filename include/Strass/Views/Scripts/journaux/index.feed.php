<?php

$t = new Articles;
$s = $this->model->select;
$s->limit(30);
$articles = $t->fetchAll($s);
foreach ($articles as $article) {
  $link = $this->url(array('controller'=> 'journaux',
			   'action'	=> 'consulter',
			   'article'	=> $article->slug,
			   'format'	=> 'html'),
		     null, true);
  $this->feed['entries'][] = array('title'	=> $article->titre ? $article->titre : $article->slug,
				   'link'	=> $link,
				   'description'=> $this->tw->transform($article->getBoulet(), 'Plain'),
				   'content'	=> $this->tw->transform($article->article),
				   'lastUpdate'	=> strtotime($article->getDate()));
}
