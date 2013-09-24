<?php

require_once 'Strass/Livredor.php';

class LivredorController extends Strass_Controller_Action
{
	protected	$_titreBranche = "Livre d'or";

	function indexAction()
	{
		$this->metas(array('DC.Title' => "Livre d'or",
				   'DC.Subject' => 'livre,or'));

		$this->view->current = $page = $this->_getParam('page');
		$this->view->livredor = $table = new Livredor;
		$this->view->messages = $table;
		$this->connexes->append('Poster un message',
					array('action' => 'poster'));
		if ($table->fetchAll('public IS NULL')->count()) {
			$this->actions->append('Valider des messages',
					       array('action' => 'moderer'),
					       array(null, $table));
		}

		$this->formats('rss', 'atom');
	}

	function posterAction()
	{
		$this->view->model = $m = new Wtk_Form_Model('poster');
		$this->metas(array('DC.Title' => "Écrire dans le livre d'or",
				   'DC.Subject' => 'livre,or'));

		$i = $m->addString('auteur', 'Nom ou pseudonyme');
		$m->addConstraintRequired($i);
		$m->addString('adelec', 'Adresse électronique');
		$i = $m->addString('message', 'Message');
		$m->addConstraintRequired($i);
		$m->addNewSubmission('poster', 'Poster');

		if ($m->validate()) {
			$ti = new Livredor();
			$db = $ti->getAdapter();
			$db->beginTransaction();
			try {
				$tuple = $m->get();
				$tuple['date'] = strftime('%Y-%m-%d %H:%m');
				$tuple['public'] = Zend_Registry::get('individu') ? '1' : null;
				$ti->insert($tuple);

				// signaler a l'admin qu'il faut
				// modérer un nouveau message sur le
				// livre d'or. On épargne la
				// modération du livre d'or à tout les
				// admins.
				if (!$tuple['public']) {
					$mail = new Strass_Mail("Nouveau message sur le livre d'or");
					$d = $mail->getDocument();
					$d->level+=2;
					$d->addParagraph("Cher administrateur,");
					$d->addParagraph($tuple['auteur']." a posté un message sur le livre d'or, ".
							 "vous êtes invité à le ",
							 new Wtk_Link($this->_helper->Url->full('moderer'), "modérer"),
							 ".");
					$s = $d->addSection(null, 'Message de '.$tuple['auteur']);
					$s->addText($tuple['message']);
					$s = $d->addSection(null, 'Validation');
					$l = $s->addList();
					$l->addItem()->addLink($this->_helper->Url->full('valider', null, null,
											 array('auteur' => $tuple['auteur'],
											       'date' => $tuple['date'],
											       'verdict' => 'accepter')),
							       "Accepter");
					$l->addItem()->addLink($this->_helper->Url->full('valider', null, null,
											 array('auteur' => $tuple['auteur'],
											       'date' => $tuple['date'],
											       'verdict' => 'refuser')),
							       "Refuser");
					$mail->send();
				}

				$db->commit();
				$this->redirectSimple('index');
			}
			catch (Exception $e) {
				$db->rollBack();
				throw $e;
			}
		}
	}

	function modererAction()
	{
		$ti = new Livredor();
		$this->assert(null, $ti, 'moderer',
			      "Vous n'avez pas le droit de modérer les messages du livre d'or.");

		$this->metas(array('DC.Title' => "Modérer le livre d'or",
				   'DC.Subject' => 'livre,or'));

		$page = $this->_getParam('page');
		$this->view->messages = new Wtk_Pages_Model_Table(new Livredor(),
								  'public IS NULL',
								  'date DESC',
								  15, $page);
	}

	function validerAction()
	{
		$verdict = $this->_getParam('verdict');
		$tm = new Livredor;
		$this->assert(null, $tm, 'moderer',
			      "Vous n'avez pas le droit de modérer les messages du livre d'or.");
		$this->metas(array('DC.Title' => "Valider un message du livre d'or",
				   'DC.Subject' => 'livre,or'));

		$message = $tm->find($this->_getParam('auteur'), $this->_getParam('date'))->current();

		if (!$message)
			throw new Knema_Controller_Action_Exception("Message introuvable");

		$db = $tm->getAdapter();
		$db->beginTransaction();

		try {
			if ($verdict == 'accepter') {
				$message->public = 1;
				$message->save();
				$db->commit();
			}
			else
				$this->redirectSimple('supprimer');

		}
		catch(Exception $e) {
			$db->rollBack();
			throw $e;
		}

		$this->redirectSimple('index', 'livredor', null, null, true);
	}

	function supprimerAction()
	{
		$verdict = $this->_getParam('verdict');
		$tm = new Livredor;
		$this->assert(null, $tm, 'moderer',
			      "Vous n'avez pas le droit de modérer les messages du livre d'or.");

		$message = $tm->find($this->_getParam('auteur'), $this->_getParam('date'))->current();

		if (!$message)
			throw new Knema_Controller_Action_Exception("Message introuvable");

		$db = $tm->getAdapter();
		$db->beginTransaction();

		try {
			$message->delete();
			$db->commit();

		}
		catch(Exception $e) {
			$db->rollBack();
			throw $e;
		}

		$this->redirectSimple('index', 'livredor', null, null, true);
	}
}