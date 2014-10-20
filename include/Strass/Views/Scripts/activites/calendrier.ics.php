<?php

$this->ics->_title = $this->unite->getFullname();
extract($this->model->fetch($this->model->current));
foreach($activites as $activite)
  $this->activiteEvent($this->ics, $activite);