<?php

require_once 'Strass/Journaux.php';
require_once 'Strass/Documents.php';

class Unites extends Strass_Db_Table_Abstract
{
	protected $_name = 'unite';
	protected $_rowClass = 'Unite';
	protected $_dependentTables = array('Unites',
					    'Appartenances',
					    'Participations',
					    'Journaux',
					    'DocsUnite');
	protected $_referenceMap = array('Parent' => array('columns' => 'parent',
							   'refTableClass' => 'Unites',
							   'refColumns' => 'id',
							   'onUpdate' => self::CASCADE,
							   'onDelete' => self::CASCADE),
					 'Type' => array('columns' => 'type',
							 'refTableClass' => 'TypesUnite',
							 'refColumns' => 'id'));

	function getFermees($where = null) {
		return $this->_getStatut(false, $where);
	}

	function getOuvertes($where = null) {
		return $this->_getStatut(true, $where);
	}

	function getIdSousUnites($ids_parent, $annee = NULL) {
		// mettre à jour les participations
		$rows = $this->find($ids_parent);

		// sélectionner *toutes* les sous-unités.
		$unites = $ids_parent;
		foreach($rows as $unite) {
			$sus = $unite->getSousUnites(true, $annee);
			foreach($sus as $su)
				$unites[] = $su->id;
		}
		$unites = array_unique($unites);
		return $unites;
	}

	/*
	 * Liste les unités dans l'ordre.
	 */
	function findMany($ids)
	{
		$select = $this->select()
			->from('unite')
		  ->where('unite.id IN ?', $ids);

		return $this->fetchAll($select);
	}

	function fetchAll($select)
	{
	  $args = func_get_args();
	  if ($args[0] instanceof Zend_Db_Table_Select)
	    $this->_ordonner($args[0]);
	  return call_user_func_array(array('parent', 'fetchAll'), $args);
	}

	protected function _ordonner($select)
	{
		$select->distinct()
			->join('unite_type', 'unite.type = unite_type.id', array())
			->order('unite_type.ordre');
	}

	protected function _getStatut($ouverte, $where = null) {
	  $select = $this->select()->distinct();

		if ($ouverte) {
			// appartenances à l'unité parente. C'est
			// incomplet car on pourrait avoir les
			// effectifs des patrouilles sans la maîtrise
			// (PL) et donc avoir une HP.
			$select->join('appartenance',
				      "appartenance.unite = unite.id".
				      " OR ".
				      ("((unite.type = 'hp' OR unite.type = 'aines')".
				       " AND ".
				       "appartenance.unite = unite.parent)"),
				      array())
				->where('fin IS NULL');
		}
		else {
			$select->joinLeft('appartenance',
					  'appartenance.unite = unite.id'.
					  ' AND '.
					  'appartenance.fin IS NULL', array());
			$select->where("appartenance.unite IS NULL");
			$select->where("unite.type <> 'hp' AND unite.type <> 'aines'");
		}

		if ($where)
			$select->where($where);

		return $this->fetchAll($select);
	}
}

class Unite extends Strass_Db_Table_Row_Abstract implements Zend_Acl_Resource_Interface, Zend_Acl_Role_Interface
{
	protected $terminale = null;
	protected $fermee = null;
	public	  $abstraite = false;
	protected static $tu = array();
	protected static $ssu = array();
	protected $_privileges = array(array('chef',		NULL),
				       array('assistant',	array('prevoir-activite',
								      'reporter')),
				       array(NULL,		array('consulter',
								      'calendrier',
								      'contacts',
								      'infos')));

	public function __construct(array $config = array()) {
		parent::__construct($config);
		$this->initRoleAcl();
		$this->initResourceAcl(array($this));
		Zend_Registry::set($this->id, $this);
		$this->abstraite = in_array($this->type, array('hp','aines'));
	}

	function _initResourceAcl(&$acl)
	{
		$acl->allow(null, $this, array('index'));

		/* En dur, car systématique et pour en base pour les unités virtuelles et les sizaines */
		$acl->addRole(new Zend_Acl_Role($this->getRoleRoleId('chef')));
		$acl->addRole(new Zend_Acl_Role($this->getRoleRoleId('assistant')));

		// Donner aux assistante tout les pouvoirs sur
		// l'unité. Oui c'est du machisme, mais c'est plus
		// rare les cheftaines qui se soucient de
		// l'informatique …
		if ($this->findParentTypesUnite()->sexe == 'f') {
		  // CORRECTIF à l'arrache du 15 juin 2012 : il faut tester la création des
		  // rôle pour les unités féminines.
		  $r = new Zend_Acl_Role($this->getRoleRoleId('assistant'));
		  if (!$acl->hasRole($r))
		    $acl->addRole($r);
		  $acl->allow($r, $this);
		}

		// Considérer les chef et assistants des unités sœur
		// comme chef et assistant de cette unité. Ex: les CP
		// et SP sont chefs et assistants de la HP.
		$tu = $this->getTable();
		$select = $tu->select()
		  ->setIntegrityCheck(false)
		  ->from('unite')
		  ->where("unite.parent = ?", $this->parent)
		  ->join('unite_type', 'unite_type.id = unite.type')
		  ->where("unite_type.virtuelle");
		$soeur = $tu->fetchAll($select)->current();
		$soeurroles = $soeur ? array($soeur, $soeur->getRoleRoleId('assistant')) : array();

		$roles = $this->findParentTypesUnite()->findRoles();
		foreach($roles as $role) {
		  $rid = $this->getRoleRoleId($role->acl_role);
		  if (!$acl->hasRole($rid)) {
		    // ajouter le role des maîtrise comme étant
		    // assistant dans cette unité.  TODO: gérér
		    // globalement car il y a ici une course, dans le
		    // cas de page web sans état, c'est peu gênant.
		    $parent = in_array($role->acl_role, array('chef', 'assistant')) ? $soeurroles : array();
		    $acl->addRole(new Zend_Acl_Role($rid), $parent);
		  }
		}

		// permettre au chef d'unités racine de valider les inscriptions.
		if (!$this->parent) {
			if (!$acl->has('inscriptions'))
				$acl->add(new Zend_Acl_Resource('inscriptions'));
			$acl->allow($this->getRoleRoleId('chef'), array('membres', 'inscriptions'));
		}


	}

	static function getInstance($id)
	{
		try {
			return Zend_Registry::get($id);
		}
		catch (Exception $e) {
			$t = new Unites();
			return $t->find($id)->current();
		}
	}

	public function getResourceId()
	{
		return 'unite-'.$this->slug;
	}

	public function getRoleId()
	{
		return 'unite-'.$this->slug;
	}

	public function getRoleRoleId($role, $annee = null)
	{
	  return $role.'-'.$this->getRoleId();
	}

	public function getTypeName()
	{
		return $this->findParentTypesUnite()->nom;
	}

	function getName()
	{
		// pat, sizaine, etc. utiliser le totem de pat
		if ($this->findParentTypesUnite()->age_max < 18)
			return $this->nom;
		else
			return $this->getFullName();
	}

	public function getFullName()
	{
		return trim($this->getTypeName()." ".$this->nom);
	}

	function getImage($slug = null, $test = true)
	{
		$slug = $slug ? $slug : $this->slug;
		$image = 'data/unites/'.$slug.'.png';
		return !$test || is_readable($image) ? $image : null;
	}

	function getWiki($slug = null, $test = true)
	{
		$slug = $slug ? $slug : $this->slug;
		$image = 'private/unites/'.$slug.'.wiki';
		return !$test || is_readable($image) ? $image : null;
	}

	public function __toString()
	{
		return $this->getFullName();
	}

	public function getSousTypes()
	{
		return $this->findParentTypesUnite()->findTypesUnite();
	}

	public function getSousTypeName($pluriel = false)
	{
		if ($this->isTerminale())
			return '';

		$sts = $this->getSousTypes();
		$soustype = '';
		foreach($sts as $st) {
			if ($st->slug == 'hp')
				continue;

			if (!$soustype)
				$soustype = $st->nom;
			else if ($st->nom != $soustype)
				$soustype = 'unité';
		}
		return $soustype.($pluriel ? 's' : '');

	}

	// retourne les sous-unités, récursivement ou non
	//
	// annee = null       ->toutes
	//       = false      ->actives
	//       = true       ->fermées
	//       = <annee>    ->active en <annee>
	public function getSousUnites($recursif = true, $annee = null) {
		$ia = intval($annee);
		$ir = intval($recursif);
		if (!isset(self::$ssu[$this->id]))
			self::$ssu[$this->id] = array();

		if (!isset(self::$ssu[$this->id][$ia]))
			self::$ssu[$this->id][$ia] = array();

		if (!isset(self::$ssu[$this->id][$ia][$ir])) {
			$unites = array();
			$db = $this->getTable()->getAdapter();
			$select = $this->getTable()->select()
			  ->setIntegrityCheck(false)
				->from('unite')
				->where('unite.parent = ?', $this->id);

			if (!is_null($annee)) {
				$select
				  ->join('unite_type', 'unite_type.id = unite.type', array())
				  ->join('appartenance',
					 'appartenance.unite = unite.id'.
					 ' OR '.
					 ("(unite_type.virtuelle".
					  " AND ".
					  " appartenance.unite = unite.parent)"),
					 array());
				if ($annee === false)
					$select->where('appartenance.fin IS NULL');
				else if ($annee === true)
					$select->where('appartenance.fin IS NOT NULL');
				else {
					$date = ($annee+1).'-06';
					$select->where("debut < ? AND (fin IS NULL OR fin >= ?)", $date);
				}
			}

			$su = $this->getTable()->fetchAll($select);
			foreach($su as $u) {
				$unites[] = $u;
				if ($recursif) {
					$sousunites = $u->getSousUnites($recursif, $annee);
					if ($sousunites) {
						$unites = array_merge($unites, $sousunites);
					}
				}
			}
			self::$ssu[$this->id][$ia][$ir] = $unites;
		}
		return self::$ssu[$this->id][$ia][$ir];
	}

	/**
	 * Retrouve les appartenances à l'unité en fonction de l'année en
	 * tenant compte du type (ex: HP).
	 */
	public function getApps($annee = null, $recursive = false, $where = '') {
		$db = $this->getTable()->getAdapter();

		$where = (array) $where;

		if ($annee === false)
			$where[]= 'fin IS NULL';
		elseif ($annee) {
			// Est considéré comme inscrit pour une année donnée un personne inscrite
			// avant le 24 août de l'année suivante …
			$where[]= $db->quoteInto('STRFTIME("%Y-%m-%d", debut) <= ?', ($annee+1).'-08-24');
			// … toujours en exercice ou en exercice au moins jusqu'au 1er janvier de l'année suivante.
			$where[]= $db->quoteInto('fin IS NULL OR STRFTIME("%Y-%m-%d", fin) >= ?', ($annee+1).'-01-01');
		}

		$select = $this->getTable()->select()
		  ->setIntegrityCheck(false)
		  ->distinct()
		  ->from('appartenance')
		  ->join('unite', 'unite.id = appartenance.unite')
		  ->join('unite_role',
			 'unite_role.type = unite.type AND unite_role.id = appartenance.role',
			 array());

		switch($this->type) {
		case 'hp':
			$where[]= 'appartenance.role = "chef" OR appartenance.role = "assistant"';
		case 'aines':
			$select->join('unite',
				      'appartenance.unite = unite.id'.
				      ' AND '.
				      '('.($db->quoteInto('unite.parent = ?',
							  $this->parent).
					   ' OR '.
					   $db->quoteInto('unite.id = ?',
							  $this->parent)).
				      ')',
				      array());
			break;
		default:
			$select->where($db->quoteInto('unite = ?', $this->id));
			if ($recursive) {
				$in = $db->select()
					->from(array('filles' => 'unite'), 'id')
					->where("filles.parent = '".$this->id."'");
				$select->orWhere($db->quoteInto('unite IN (?)',
								new Zend_Db_Expr($in->__toString())));
			}
			break;
		}

		$select->join('individu',
			      "individu.id = appartenance.individu\n",
			      array())
			->order('unite_role.ordre')
			->order('naissance');
		$where = array_filter($where);
		foreach($where as $clause)
			if ($clause)
				$select->where($clause);

		$ta = new Appartenances();
		return $ta->fetchAll($select);
	}

	function isFermee()
	{
		if (is_null($this->fermee)) {
			$this->fermee = $this->getApps()->count() == 0;
			if ($this->fermee && !$this->isTerminale())
				$this->fermee = count($this->getSousUnites(null, false)) == 0;
		}
		return $this->fermee;
	}

	function fermer($fin, $recursif = true) {

		$ta = new Appartenances;
		$s = $ta->select()->where('fin IS NULL');
		$apps = $this->findAppartenances($s);
		foreach($apps as $app) {
			$app->fin = $fin;
			$app->save();
		}

		if ($recursif) {
			$us = $this->getSousUnites(false, false);
			foreach($us as $u) {
				$u->fermer($fin, $recursif);
			}
		}
	}

	function getDerniereAnnee()
	{
		$u = $this->type == 'hp' ? $this->findParentUnites() : $this;
		$ta = new Appartenances;
		$s = $ta->select()->order('fin IS NULL')->limit(1);
		$app = $u->findAppartenances($s)->current();
		return $app ? intval(strftime('%Y', strtotime($app->fin)) - 1) : null;
	}

	function getProchainesParticipations($count = 1, $explicites = false)
	{
		$tp = new Participations();
		$db = $tp->getAdapter();
		$select = $db->select()
			->distinct()
			->from('participe')
			->join('activites',
			       'activites.id = participe.activite'.
			       ' AND '.
			       'activites.debut > CURRENT_TIMESTAMP',
			       array())
			->where('participe.unite = "'.$this->id.'"')
			->order('debut DESC');

		if ($explicites) {
			$notexists = $db->select()
				->from(array('autre' => 'participe'))
				->where('autre.activite = participe.activite'.
					' AND '.
					'autre.unite == "'.$this->parent.'"');
			$select->where('NOT EXISTS (?)',
				       new Zend_Db_Expr($notexists->__toString()));
		}
		return $tp->fetchAll($select);
	}

	function isTerminale()
	{
		if (is_null($this->terminale)) {
			$this->terminale = $this->findParentTypesUnite()->isTerminale();
		}
		return $this->terminale;
	}


	// retourne les années où l'unité fut ouverte.
	function getAnneesOuverte()
	{
		// sélectionner les années où l'unité à eut au moins un membre
		$db = $this->getTable()->getAdapter();
		$ti = new Individus();
		$select = $ti->select()
		  ->setIntegrityCheck(false)
			->distinct()
		  ->from('individu')
		  ->join('appartenance',
			 'individu.id = appartenance.individu',
			 array('debut' => "strftime('%Y', debut, '-8 months')",
			       'fin' => "strftime('%Y', fin, '-7 months')"))
		  ->join('unite_role', 'unite_role.id = appartenance.role',
			 array('role' => 'unite_role.acl_role'))
		  ->order('debut ASC');

		if ($this->findParentTypesUnite()->virtuelle) {
			$select->join('unite',
				      'unite.id = appartenance.unite',
				      array());
			$select->where("unite.id = ?", $this->parent);
		}
		else {
		  $select->where("appartenance.unite = ?", $this->id);
		}

		$is = $ti->fetchAll($select);
		$annees = array();
		$cette_annee = intval(strftime('%Y', time()-243*24*60*60));
		foreach($is as $individu) {
			 $fin = $individu->fin ? $individu->fin : $cette_annee;
			 $fin = ($fin == $cette_annee) ? $fin+1 : $fin;
			 for($annee = $individu->debut; $annee < $fin; $annee++) {
				 if (!array_key_exists($annee, $annees))
					 $annees[$annee] = null;

				if ($individu->role == 'chef') {
					$annees[$annee] = $individu;
				}
			}
		}
		ksort($annees);
		return $annees;
	}

	function _postDelete()
	{
		if ($i = $this->getImage())
			unlink($i);

		if ($w = $this->getWiki())
			unlink($w);
	}

	function _postUpdate()
	{
		if ($i = $this->getImage($this->_cleanData['id']))
			rename($i, $this->getImage(null, false));

		if ($w = $this->getWiki($this->_cleanData['id']))
			rename($w, $this->getWiki());
	}
}

class TypesUnite extends Strass_Db_Table_Abstract
{
	protected $_name = 'unite_type';
	protected $_rowClass = 'TypeUnite';
	protected $_dependentTables = array('Unites', 'TypesUnite', 'Roles');
	protected $_referenceMap = array('Parent' => array('columns' => 'parent',
							   'refTableClass' => 'TypesUnite',
							   'refColumns' => 'id'));

	function getTypesRacine()
	{
		$select = $this->getAdapter()->select()
			->where('parent IS NULL');
		return $this->fetchAll($select);
	}
}

class TypeUnite extends Zend_Db_Table_Row_Abstract
{
	protected static $roles = array();
	protected $terminale = null;

	function __toString()
	{
		return $this->nom;
	}

	function getExtraName()
	{
		switch ($this->id) {
		case 'groupe':
		case 'sizaine':
			return null;
		case 'clan':
		case 'eqclan':
		case 'feu':
		case 'eqfeu':
		case 'troupe':
		case 'compagnie':
		case 'meute':
		case 'ronde':
			return 'Patronage';
		case 'patrouille':
		case 'equipage':
		case 'equipe':
		case 'hp':
			return 'Cri de pat\'';
		}
	}

	function isTerminale()
	{
		if (is_null($this->terminale)) {
			$this->terminale = $this->findTypesUnite()->count() == 0;
		}
		return $this->terminale;
	}
}

class Roles extends Zend_Db_Table_Abstract
{
	protected $_name = 'unite_role';
	protected $_rowClass = 'Role';
	protected $_dependentTables = array('Appartenances');
	protected $_referenceMap = array('TypeUnite' => array('columns' => 'type',
							      'refTableClass' => 'TypesUnite',
							      'refColumns' => 'id'));
}

class Role extends Zend_Db_Table_Row_Abstract
{
	function getAccronyme()
	{
		return $this->accr ? $this->accr : $this->titre;
	}

	function __toString()
	{
		return $this->titre;
	}
}
