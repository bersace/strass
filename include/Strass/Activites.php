<?php

require_once 'Strass/Unites.php';
require_once 'Strass/Photos.php';
require_once 'Strass/Documents.php';

class Activites extends Strass_Db_Table_Abstract
{
	protected $_name = 'activites';
	protected $_rowClass = 'Activite';
	protected $_dependentTables = array('Participations', 'Photos', 'DocsActivite',
					    'Commentaires');

	function findActivites($futures = TRUE) {
		// +2 => heures été/hivers. :/
		return $this->fetchAll("debut ".($futures ? '>' : '<').
				       " STRFTIME('%Y-%m-%d %H:%M', CURRENT_TIMESTAMP, '+2 HOURS')");
	}


	// Retourne les clefs primaires des unités qui ne sont pas
	// implicetement participante d'une activité via une unité parente.
	static function getUnitesParticipantesExplicites($participantes)
	{
		$explicites = array();
		$parentes = clone $participantes;
		foreach($participantes as $unite) {
			$implicite = false;
			foreach($parentes as $parente)
				$implicite = $implicite || $unite->parent == $parente->id;
			if (!$implicite)
				array_push($explicites, $unite->id);
		}
		return $explicites;
	}
}


class Activite extends Strass_Db_Table_Row_Abstract implements Zend_Acl_Resource_Interface
{
	static protected $us = array();
	protected $gi;
    
	static protected $types = array('diner' => 'Dîner',
					'sortie' => 'Sortie',
					'we' => 'Week-end',
					'camp' => 'Camp');

	static protected $type_accr = array('we' => 'WE');

	static $mois = array('January' => 'janvier',
			     'February' => 'février',
			     'March' => 'mars',
			     'April' => 'avril',
			     'May' => 'mai',
			     'June' => 'juin',
			     'July' => 'juillet',
			     'August' => 'août',
			     'September' => 'septembre',
			     'October' => 'octobre',
			     'November' => 'novembre',
			     'December' => 'décembre');


	protected $_privileges = array(array('chef', NULL),
				       array('assistant', array('modifier',
								'envoyer-photo',
								'dossier')),
				       array(NULL, 'consulter'));
	protected $type;
	protected $annee;

	public function __construct(array $config = array()) {
		parent::__construct($config);
		$this->initResourceAcl($this->getUnitesParticipantesExplicites());
		$this->type = self::findType($this->debut, $this->fin);
		$this->annee = intval(date('Y', strtotime($this->debut) - 243 * 24 * 60 * 60));
	}

	protected function _initResourceAcl(&$acl)
	{
		// permettre à tous de voir les détails des activités passées.
		if (!$this->isFuture())
			$acl->allow(null, $this, 'consulter');
		// permettre à tout le monde de voir les rapports de cette activité
		$acl->allow(null, $this, 'rapport');

		// permettre à toute les maîtrise d'envoyer des photos.
		$us = $this->getUnitesParticipantes();
		$chefs = array();
		foreach($us as $u) {
			if ($acl->hasRole($role = $u->getRoleRoleId('chef')))
				$chefs[] = $role;
			if ($acl->hasRole($role = $u->getRoleRoleId('assistant')))
				$chefs[] = $role;
		}
		if ($chefs)
			$acl->allow($chefs, $this, 'envoyer-photo');
	}

	function __toString()
	{
		return $this->getIntitule();
	}

	public function getResourceId()
	{
		return 'activite-'.$this->id;
	}

	public function getType()
	{
		return $this->type;
	}

	public function getAnnee()
	{
		return $this->annee;
	}

	public function getTypeName()
	{
		return self::$types[$this->type];
	}

	public static function findType($debut, $fin)
	{
		$dt = strtotime($debut);
		$ft = strtotime($fin);
		$jt = 60 * 60 * 20;     // secondes par "jour"
		if ($ft - $dt < $jt) {
			if (date('H', $dt) >= 16 && date('H', $ft) >= 20) {
				$type = 'diner';
			}
			else {
				$type = 'sortie';
			}
		}
		else if ($ft - $dt < 2 * $jt) {
			$type = 'we';
		}
		else {
			$type = 'camp';
		}
		return $type;
	}

	public function getIntitule($date = true, $lieu = true, $compact = false)
	{
		// Si l'intitulé est généré, alors le regénérer.
	  if ($this->intitule) {
	    $intitule = $this->intitule;
	  }
	  else {
	    $intitule = self::generateIntitule($this->_data,
					       $this->getUnitesParticipantes(),
					       false, $lieu, $compact);
	  }

	  if ($date) {
	    $dt = strtotime($this->debut);
	    $ft = strtotime($this->fin);
	    $gdate = self::generateDate($intitule, self::findType($this->debut, $this->fin),
					$dt,$ft);
	    // s'il est imposé, alors ajouter l'année (ou pas).
	    $intitule.= $gdate;
	  }

	  return $intitule;
	}
    
	protected function getGeneratedIntitule()
	{
		if (is_null($this->gi)) {
			$this->gi = self::generateIntitule($this->toArray(),
							   $this->getUnitesParticipantes());
		}
		return $this->gi;
	}


	public static function generateIntitule(array $data, $unites,
						$date = true, $location = true, $compact = false) {
		extract($data);
		$dt = strtotime($debut);
		$ft = strtotime($fin);

		$type = self::findType($debut, $fin);

		$tu = new Unites();
		$eids = Activites::getUnitesParticipantesExplicites($unites);
		$explicites = count($eids) > 1 ? $tu->findMany($eids) : Unite::getInstance($eids[0]);

		// on commence toujours par le type d'activité.
		// les camp des routier s'appellent des "routes" (uniquement FSE ?)
		$first = $explicites instanceof Countable ? $explicites->current() : $explicites;
		if ($type == 'camp' && ($first->type == 'clan' || $first->type == 'eqcclan')) {
			$i = 'Route';
		}
		else if ($compact) {
		  switch ($type) {
		  case 'we':
		    $i = 'WE';
		    break;
		  default:
		    $i = self::$types[$type];
		    break;
		  }
		}
		else
			$i = self::$types[$type];


		// type des unités participante
		if ($explicites instanceof Countable) {   // inter unités
			$types = array();
			foreach($explicites as $unite) {
				array_push($types, $unite->getTypeName());
			}
			$types = array_unique($types);
			if (count($types) == 1) {   // unités de même types
				$i.= " inter-".$types[0];
			}
		}
		else {                  // unité unique
			$unite = $explicites;
			if ($compact && $type == 'we') {
			  switch ($unite->type) {
			  case 'hp':
			    $i.= 'HP';
			    break;
			  case 'aines':
			    $i.= 'CA';
			    break;
			  case 'troupe':
			    $i.= "T ".$unite->nom;
			    break;
			  case 'groupe':
			    $i.= "G";
			    break;
			  case 'patrouille':
			    $i.= "P ".$unite->getName();
			    break;
			  case 'equipe':
			  case 'eqclan':
			    $i.= "E ".$unite->getFullname();
			    break;
			  default:
			    $i.= " de ".$unite->getTypeName();
			    break;
			  }
			  
			}
			else {
			  switch ($type) {
			  case 'camp':
			    if ($unite->type == 'hp') {
			      $i.= ' HP';
			    }
			    else {
			      // été/noël/hiver ?
			      switch(strftime('%m', $dt)) {
			      case 12:
			      case 1:
				$i.= ' de Noël';
				break;
			      case 2:
			      case 3:
				$i.= " d'hiver";
				break;
			      case 4:
			      case 5:
				$i.= " de Pâques";
				break;
			      case 7:
			      case 8:
				$i.= " d'été";
				break;
			      default:
				break;
			      }
			    }
			    break;
			  default:
			    switch ($unite->type) {
			    case 'hp':
			      $i.= ' HP';
			      break;
			    case 'patrouille':
			      $i.= " de ".$unite->getFullname();
			      break;
			    case 'equipe':
			    case 'eqclan':
			      $i.= " d'".$unite->getTypeName();
			      break;
			    default:
			      $i.= " de ".$unite->getTypeName();
			      break;
			    }
			  }
			}
		}

		if ($lieu && $location) {
		  if ($compact || $type == 'camp')
		    $i.= ' '.$lieu;
		  else
		    $i.= ' à '.$lieu;
		}

		if ($date)
		  $i.= self::generateDate($intitule, $type, $dt, $ft);

		return $i;
	}

	static function generateDate($intitule, $type, $dt, $ft)
	{
		$i = "";

		// gruik
		if (strpos($intitule, 'Rentr') === 0)
		  return strftime(' %Y', $ft);

		switch ($type) {
		case 'diner':
		case 'sortie':
			$i.= " du ".trim(strftime("%e/%m/%Y", $dt));
			break;
		case 'we':
			$i.= (" du ".
			      trim(strftime("%e", $dt)).' - '.trim(strftime("%e", $ft)).
			      ' '.self::$mois[strftime('%B', $dt)].' '.strftime('%Y', $dt));
			break;
		case 'camp':
		default:
			$i.= (" ".strftime("%Y", $dt));
			break;
		}

		return $i;
	}

	/**
	 * Si $unique, alors la date est complète et préfixé d'un article : les 4/5
	 * juin 2009.
	 */
	function getDate($unique = true) {
		$dt = strtotime($this->debut);
		$ft = strtotime($this->fin);

		switch ($this->getType()) {
		case 'diner':
		case 'sortie':
			return
				(!$unique ? "" : "le ").
				strftime('%e', $dt).' '.
				self::$mois[strftime('%B', $dt)].' '.
				(!$unique ? '' :
				 strftime('%Y', $dt).
				 ' de '.strftime('%H:%M', $dt).' à '.strftime('%H:%M', $ft));
			break;
		case 'we':
			return
				(!$unique ? "" : "les ").
				trim(strftime('%e', $dt)).' - '.
				trim(strftime('%e', $ft)).' '.
				self::$mois[strftime('%B', $dt)].
				(!$unique ? '' : strftime(' %Y', $dt));
			break;
		case 'camp':
			return
				strftime((!$unique ? '%e - ' : 'du %e au '), $dt).
				strftime('%e ', $ft).
				self::$mois[strftime('%B', $dt)].' '.
				(!$unique ? '' : strftime(' %Y', $dt));
			break;
		}
	}

	function isFuture()
	{
		return strtotime($this->debut) > time();
	}

	function getDossierPhoto($id = NULL) {
		return 'data/photos/'.($id ? $id : $this->id);
	}

	function getPhotoAleatoire()
	{
		$select = $this->getTable()->select()
			->order('RANDOM()')
			->limit(1);
		return $this->findPhotos($select)->current();
	}

	function countPhotos()
	{
		$db = $this->getTable()->getAdapter();
		$select = $db->select()
			->from('photos', 'COUNT(*)')
			->where($db->quoteInto('photos.activite = ?', $this->id));
		$stmt = $db->query($select);
		return count($stmt->fetchAll());
	}

	protected function _postUpdate()
	{
		rename($this->getDossierPhoto($this->_cleanData['id']),
		       $this->getDossierPhoto());
	}

	protected function _postDelete()
	{
		$d = $this->getDossierPhoto();
		if (file_exists($d)) {
			$fs = scandir($d);
			foreach($fs as $f) {
				if ($f != '.' && $f != '..') {
					if (!unlink($d.'/'.$f)) {
						throw new
							Exception("Impossible de supprimer le fichier ".
								  $f." du dossier ".$d);
					}
				}
			}
			if (!rmdir($d)) {
				throw new Exception("Impossible de supprimer le dossier ".$d);
			}
		}
	}


	function getUnitesParticipantes()
	{
		// mettre à jour les participations
		$rows = $this->findUnitesViaParticipations();

		// sélectionner *toutes* les sous-unités.
		$participantes = array();
		foreach($rows as $unite) {
			$participantes[] = $unite->id;
			$sus = $unite->getSousUnites(true, $this->getAnnee());
			foreach($sus as $su) {
				$participantes[] = $su->id;
			}
		}
		$participantes = array_unique($participantes);

		$tu = new Unites();
		return $tu->findMany($participantes);
	}

	function getUnitesParticipantesExplicites()
	{
		$tu = new Unites();
		$ids = Activites::getUnitesParticipantesExplicites($this->getUnitesParticipantes());
		return $tu->findMany($ids);
	}
}

class Participations extends Strass_Db_Table_Abstract
{
	protected $_name = 'participe';
	protected $_rowClass = 'Participe';
	protected $_referenceMap = array('Unite' => array('columns' => 'unite',
							  'refTableClass' => 'Unites',
							  'refColumns' => 'id', 'onUpdate' => self::CASCADE,
							  'onDelete' => self::CASCADE),
					 'Activite' => array('columns' => 'activite',
							     'refTableClass' => 'Activites',
							     'refColumns' => 'id', 'onUpdate' => self::CASCADE,
							     'onDelete' => self::CASCADE));

}

class Participe extends Strass_Db_Table_Row_Abstract implements Zend_Acl_Resource_Interface
{
	protected $_privileges = array(array('chef', NULL),
				       array(NULL, 'reporter'));


	function __construct($config)
	{
		parent::__construct($config);
		$this->initResourceAcl();
	}
	function getResourceId()
	{
		return 'participation-'.$this->activite.'-'.$this->unite;
	}
}

