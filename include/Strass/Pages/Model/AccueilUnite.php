<?php

class Strass_Pages_Model_AccueilUnite extends Wtk_Pages_Model_Assoc
{
	protected $_unite;
	protected $_cette_annee;
	protected $_calendrier;

	function __construct(Unite $unite, $annee, $cette_annee, $calendrier)
	{
		$this->_unite		= $unite;
		$this->_cette_annee	= $cette_annee;
		$this->_calendrier	= $calendrier;
		parent::__construct($unite->getAnneesOuverte(), $annee);
	}

	function fetch($annee = null)
	{
		$u = $this->_unite;
		$w = $u->getWiki();
		return array('unite'	=> $u,
			     'apps' 	=> $u->getApps($annee),
			     'texte'	=> $w ? file_get_contents($w) : '',
			     );
	}

	protected function isActuelle($annee)
	{
		return $annee == $this->_cette_annee;
	}

	protected function fetchProchainesActivites($annee)
	{
		if (!$this->_calendrier or !$this->isActuelle($annee))
			return null;

		$ta = new Activites();
		$select = $ta->select()
			->distinct()
			->where('debut > CURRENT_TIMESTAMP')
			->join('participe',
			       'participe.activite = activites.id',
			       array())
			->where('participe.unite = ?', $this->_unite->id)
			->order('activites.debut ASC')
			->limit(5);
		
		return $ta->fetchSelect($select);
	}

	protected function fetchRapports($annee)
	{
		$u = $this->_unite;

		$tp = new Participations();
		$db = $tp->getAdapter();
		$select = $db->select()
			->distinct()
			->from('participe')
			->where('unite = ?', $u->id)
			->join('activites',
			       'participe.activite = activites.id',
			       array())
			->where('participe.unite = ?', $u->id)
			->where('activites.debut >= ?', $annee.'-09-01 00:00')
			->where('activites.fin <= ?', ($annee+1).'08-31 23:59')
			->where('fin < CURRENT_TIMESTAMP')
			->where("(boulet IS NOT NULL AND boulet <> '') OR (rapport IS NOT NULL AND rapport <> '')")
			->order('fin DESC')
			->limit(8);
			
		return $tp->fetchSelect($select);
	}

	protected function fetchPhotos($annee)
	{
		$u = $this->_unite;
		$actuelle = $this->isActuelle($annee);

		$tp = new Photos();
		$select = $tp->select()
			->distinct()
			->from('photos')
			->join('participe',
			       'participe.activite = photos.activite',
			       array())
			->join('activites',
			       'activites.id = photos.activite',
			       array())
			->where('participe.unite = ?', $u->id)
			->where('activites.debut >= ?', $annee.'-09-01 00:00')
			->where('activites.fin <= ?', ($annee+1).'08-31 23:59')
			// on affiche 4 photos aléatoire dans
			// l'historique et les 8 dernières photos de
			// l'année courante.
			->order($actuelle ? 'date DESC' : 'RANDOM()')
			->limit($actuelle ? 8 : 4);
		return $tp->fetchSelect($select);
	}

	function valid()
	{
		return in_array($this->pointer, $this->pages_id);
	}
}
