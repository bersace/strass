<?php

require_once 'Strass/Activites.php';

class Strass_Controller_Action_Helper_Activite extends Zend_Controller_Action_Helper_Abstract
{
	function pourIndividu($individu, $debut = null, $fin = null)
	{
		$ta = new Activites;
		$s = $ta->getAdapter()->select()
			->distinct()
			->from('activites')
			->join('participe',
			       'participe.activite = activites.id',
			       array())
			->join('unites',
			       'unites.id = appartient.unite'.
			       ' OR '.
			       'unites.parent = appartient.unite',
			       array())
			->join('appartient',
			       "appartient.individu = '".$individu->id."'".
			       " AND ".
			       "appartient.unite = unites.id",
			       array())
			->where("activites.debut < STRFTIME('%Y-%m-%d %H:%M', CURRENT_TIMESTAMP)")
			// on trie de manière a avoir les
			// activités les plus récente en haut
			// de liste.
			->order('activites.debut DESC');
		if ($debut && $fin) {
			$s->where("activites.debut > '".$debut."'");
			$s->where("activites.fin < '".$fin."'");
		}

		return $ta->fetchSelect($s);
	}

	function direct($id = null, $throw = true, $reset = true,
			$urlOptions = array())
	{
		$id = $id ? $id : $this->getRequest()->getParam('activite');
		$activites = new Activites();
		$activite = $activites->find($id)->current();
		if (!$activite && $throw)
			throw new Knema_Controller_Action_Exception_Notice("Activité ".$id." inexistante.");

		if ($activite) {
			if ($reset) {
				$urlOptions+= array('controller'=> 'activites',
						    'action'	=> 'consulter',
						    'activite'	=> $id);
				$this->_actionController->branche->append(wtk_ucfirst($activite->getIntitule()),
									  $urlOptions,
									  array(),
									  true);
			}
			else 
				$this->_actionController->branche->append(wtk_ucfirst($activite->getIntitule()));
		}

		return $activite;
	}
}
