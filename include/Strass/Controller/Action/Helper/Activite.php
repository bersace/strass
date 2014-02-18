<?php

require_once 'Strass/Activites.php';

class Strass_Controller_Action_Helper_Activite extends Zend_Controller_Action_Helper_Abstract
{
  function pourIndividu($individu, $debut = null, $fin = null)
  {
    $ta = new Activites;
    $db = $ta->getAdapter();
    $s = $ta->select()
      ->setIntegrityCheck(false)
      ->distinct()
      ->from('activites')
      ->join('participe',
	     'participe.activite = activites.id',
	     array())
      ->join('unite',
	     'unite.slug = participe.unite',
	     array())
      ->join('appartenance',
	     $db->quoteInto("appartenance.individu = ?", $individu->id).
	     " AND ".
	     "appartenance.unite = unite.id",
	     array())
      ->where("activites.debut < STRFTIME('%Y-%m-%d %H:%M', CURRENT_TIMESTAMP)")
      // on trie de manière à avoir les activités
      // les plus récentes en haut de liste.
      ->order('activites.debut DESC');

    if ($debut && $fin) {
      $s->where("activites.debut > '".$debut."'");
      $s->where("activites.fin < '".$fin."'");
    }

    return $ta->fetchAll($s);
  }

  function direct($slug = null, $throw = true, $reset = true,
		  $urlOptions = array())
  {
    $slug = $slug ? $slug : $this->getRequest()->getParam('activite');
    $activites = new Activites;
    $activite = $activites->findBySlug($slug);

    if (!$activite)
      if ($throw)
	throw new Strass_Controller_Action_Exception_Notice("Activité ".$slug." inexistante.");
      else
	return null;

    $this->setBranche($activite);

    return $activite;
  }

  function setBranche($activite)
  {
    $unites = $activite->findUnitesParticipantesExplicites();
    if ($unites->count() == 1) {
      $unite = $unites->current();
      $this->_actionController->_helper->Unite->setBranche($unite, 'calendrier');

      $this->_actionController->branche->append($activite->getAnnee(),
						array('controller'=> 'activites',
						      'action'	=> 'calendrier',
						      'unite'	=> $unite->slug,
						      'annee' => $activite->getAnnee()),
						array(), true);
    }

    $this->_actionController->branche->append(wtk_ucfirst($activite->getIntitule(false)),
					      array('controller'=> 'activites',
						    'action'	=> 'consulter',
						    'activite'	=> $activite->slug),
					      array(), true);
  }
}
