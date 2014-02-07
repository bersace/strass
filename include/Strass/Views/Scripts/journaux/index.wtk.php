<?php

$s = $this->document;
$l = $s->addList();
foreach($this->journaux as $journal) {
  $l->addItem()->addParagraph($this->lienJournal($journal),
			      new Wtk_RawText(" ( "),
			      $this->lienUnite($journal->findParentUnites()),
			      new Wtk_RawText(" )."));
}
