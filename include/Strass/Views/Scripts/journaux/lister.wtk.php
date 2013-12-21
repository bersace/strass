<?php

$s = $this->document->addSection('rubrique');
$s->addFlags($this->journal->id, $this->rubrique->id);
$s->addChild(new Wtk_Pages(null,
			   new Wtk_Pages_Model_Iterator($this->articles, 15,
							$this->current),
			   new Strass_Page_RendererArticle($this, $s)));
