<?php

class Strass_Pages_Model_Prevoir extends Strass_Pages_Model_Historique
{
  function __construct($controller, $unite, $annee)
  {
    /* forcer la validité de l'année */
    parent::__construct($unite, $annee, true);
    $this->controller = $controller;
  }

  function fetch($annee = NULL)
  {
    $u = $this->unite;
    $a = $annee;

    $m = new Wtk_Form_Model('prevoir');

    $t = new Unites;
    $enum = array();
    foreach($t->fetchAll() as $unite)
      if ($this->controller->assert(null, $unite, 'prevoir'))
	$enum[$unite->id] = $unite->getFullname();

    if (!$enum)
      throw new Strass_Controller_Action_Exception_Notice("Vous ne pouvez pas enregistrer une activité");
    $i = $m->addEnum('unites', 'Unités participantes', key($enum), $enum, true);    // multiple
    $m->addConstraintRequired($i);

    $annee = $this->controller->_helper->Annee(false);
    $repere = $annee ? substr($u->findLastDate($annee), 0, 10) : strftime('%Y-%m-%d');
    $debut = strftime('%Y-%m-%d', strtotime($repere.' next saturday +4 weeks'));
    $fin = strftime('%Y-%m-%d', strtotime($repere.' next sunday +4 weeks'));

    $m->addDate('debut', 'Début', $debut.' 14:30', '%Y-%m-%d %H:%M');
    $m->addDate('fin', 'Fin', $fin.'17:00', '%Y-%m-%d %H:%M');
    $m->addString('intitule', 'Intitulé explicite', "");

    $m->addBool('prevoir', "J'ai d'autres activités à prévoir", true);
    $m->addNewSubmission('ajouter', 'Ajouter');
    $m->addConstraintRequired($m->getInstance('unites'));

    if ($m->validate()) {
      $t = new Activites;
      $tu = new Unites;
      $td = new Documents;

      $a = new Activite;
      $a->debut = $m->debut;
      $a->fin = $m->fin;

      $unites = call_user_func_array(array($tu, 'find'), (array) $m->unites);
      // génération de l'intitulé
      $type = $unites->current()->findParentTypesUnite();
      $a->intitule = $m->intitule;
      $intitule = $type->getIntituleCompletActivite($a);
      $a->slug = $slug = $t->createSlug($intitule);

      $db = $t->getAdapter();
      $db->beginTransaction();
      try {
	$a->save();
	$a->updateUnites($unites);

	$this->controller->_helper->Flash->info("Activité enregistrée");
	$this->controller->logger->info("Nouvelle activite",
					$this->controller->_helper->Url('consulter', null, null,
									array('activite' => $a->slug)));

	$db->commit();
      }
      catch(Exception $e) {
	$db->rollBack();
	throw $e;
      }

      if ($m->get('prevoir'))
	$this->controller->redirectSimple('prevoir');
      else
	$this->controller->redirectSimple('consulter', null, null, array('activite' => $slug));
    }

    return array('model' => $m,
		 'calendrier' => $u->findActivites($this->current),
		 );
  }
}
