<?php

require_once 'Strass/Liens.php';

class LiensController extends Strass_Controller_Action
{
  protected $_titreBranche = 'Liens';
  public $_afficherMenuUniteRacine = true;

  function indexAction()
  {
    $liens = new Liens();
    $this->metas(array('DC.Title' => 'Liens',
		       'DC.Subject' => 'liens,externes'));

    $this->actions->append("Éditer les liens",
			   array('action'	=> 'editer'),
			   array(null, $liens, 'editer'));
    $this->view->liens = $liens->fetchAll();
  }

  function editerAction()
  {
    $t = new Liens();
    $this->assert(null, $t, 'editer',
		  "Vous n'avez pas le droit d'éditer de liens");

    $this->metas(array('DC.Title' => 'Éditer les liens'));

    $this->view->model = $m = new Wtk_Form_Model('liens');
    $i = $m->addTable('liens', "Liens", array('url'		=> array('String', "URL"),
					      'nom'		=> array('String', 'Nom'),
					      'description'	=> array('String', 'Description')));
    $lns = $t->fetchAll();
    foreach($lns as $lien) {
      $i->addRow($lien->toArray());
    }
    $i->addRow();
    $m->addNewSubmission('enregistrer', "Enregistrer");

    if ($m->validate()) {
      $db = $t->getAdapter();
      $db->beginTransaction();
      try {
	$listes = $m->get('liens');
	$db->query('DELETE FROM `lien`;');

	foreach($listes as $data)
	  if ($data['url'])
	    $t->insert($data);

	$this->logger->info("Liens édités");
	$db->commit();
	$this->redirectSimple('index', 'liens');
      }
      catch (Exception $e) {
	$db->rollBack();
	throw $e;
      }
    }
  }
}
