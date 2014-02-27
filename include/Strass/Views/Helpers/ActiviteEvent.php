<?php

class Strass_View_Helper_ActiviteEvent
{
  protected	$view;

  public function setView($view)
  {
    $this->view = $view;
  }

  function activiteEvent($calendrier, $activite)
  {
    $tda = $activite->findPiecesJointes();
    $attachments = array();
    foreach($tda as $da) {
      $doc = $da->findParentDocuments();
      array_push($attachments, 'http://'.$_SERVER['HTTP_HOST'].$doc->getUri());
    }

    $calendrier->addEvent(strtotime($activite->debut),
			  strtotime($activite->fin),
			  $activite->lieu,
			  wtk_ucfirst($activite->getIntitule()),
			  $this->view->tw->transform($activite->description, 'Plain'),
			  $attachments);
  }
}