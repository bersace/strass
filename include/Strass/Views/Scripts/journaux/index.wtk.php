<?php

$s = $this->document->addSection('journaux', "Les journaux des unités");
$l = $s->addChild(new Wtk_List());
foreach($this->journaux as $journal) {
  $l->addItem(new Wtk_Paragraph($this->lienJournal($journal),
				new Wtk_RawText(" ( "),
				$this->lienUnite($journal->findParentUnites()),
				new Wtk_RawText(" ).")));
}
