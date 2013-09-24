<?php
require_once 'Knema/Log.php';

class Knema_Controller_Action_Helper_Log extends Zend_Controller_Action_Helper_Abstract
{
	public function log($detail, $attrs = array(), $url = null, $titre = null)
	{
		$tl = new Logs;

		$user = Zend_Registry::get('user');


		// récupération de la page courante.
		if (!$url)
			$url = $this->getRequest()->REQUEST_URI;

		if (!$titre) {
			$page = Zend_Registry::get('page');
			$titre = $page->metas->get('DC.Title');
		}

		// insertion du tuple
		$data = array('username' => $user->username,
			      'url' => $url,
			      'titre' => wtk_ucfirst($titre),
			      'detail' => $detail,
			      );
		$k = $tl->insert($data);

		// insertion des attributs
		$tla = new LogAttrs;
		foreach($attrs as $detail => $row) {
			if (!$row instanceof Zend_Db_Table_Row_Abstract) {
				$data = array('log' => $k,
					      'detail' => $detail,
					      'descr' => (string) $row,
					      'clef' => var_export($row, true),
					      );
			}
			else {
				// récupération de la clef primaire du tuple.
				$pka = $row->getTable()->info(Zend_Db_Table_Abstract::PRIMARY);
				$pk = array();
				foreach($pka as $a)
					$pk[$a] = $row->$a;

				// insertion de l'attribut
				$data = array('log'	=> $k,
					      'detail'	=> is_int($detail) ? get_class($row) : $detail,
					      'descr'	=> (string) $row,
					      'classe'	=> get_class($row),
					      'clef'	=> var_export($pk, true),
					      );
			}
			$tla->insert($data);
		}
	}

	function direct($detail, $attrs = array(), $url = null, $titre = null)
	{
		return $this->log($detail, $attrs, $url, $titre);
	}
}
