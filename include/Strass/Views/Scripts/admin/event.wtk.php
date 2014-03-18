<?php

$m = new Wtk_Table_Model('champ', 'valeur', 'url', 'flags');
$m->append('Message', $this->event->message, null, 'message');
$m->append('Niveau', $this->event->level, null, array('level', strtolower($this->event->level)));
$m->append('Émetteur', $this->event->logger, null, 'logger');
$m->append('URL', $this->event->url, $this->event->url, 'url');
$m->append('Date', $this->event->date, null, 'date');

$user = $this->event->findParentUsers();
if (!$user)
  $user = new Nobody;

$individu = $user->findParentIndividus();
if ($user->isMember())
  $url = $this->url(array('controller' => 'individus',
			  'action' => 'fiche',
			  'individu' => $individu->slug));
else
  $url = null;

$m->append('Utilisateur', $individu->getFullname(), $url, 'user');

$s = $this->document->addSection('event');
$t = $s->addTable($m, false, 'flags');
$t->addNewColumn(null, new Wtk_Table_CellRenderer_Text('text', 'champ'));
$t->addNewColumn(null, new Wtk_Table_CellRenderer_Link('href', 'url', 'label', 'valeur'));

$detail = @unserialize($this->event->detail);
if ($detail) {
  $s->addSection('detail', "Détails")
    ->addText($detail, true);
}
