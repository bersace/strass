<?php

$s = $this->content->addSection('brouillons',
				new Wtk_Container($this->lienJournal($this->journal)));
$s->addFlags($this->journal->id);
$s->addChild(new Wtk_Pages(null,
			   new Wtk_Pages_Model_Iterator($this->brouillons, 15, $this->current),
			   new Strass_Page_RendererArticle($this, $s)));
