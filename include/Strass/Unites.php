<?php

require_once 'Strass/Journaux.php';
require_once 'Strass/Documents.php';

class Unites extends Strass_Db_Table_Abstract
{
	protected $_name = 'unites';
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
	function find($ids)
	{
		$db = $this->getAdapter();
		$select = $db->select()
			->from('unites')
			->where('unites.id = "'.
				implode('" OR unites.id = "', (array) $ids).'"');

		return $this->fetchSelect($select);
	}

	function fetchSelect($select)
	{
		$this->_ordonner($select);
		return parent::fetchSelect($select);
	}

	protected function _ordonner($select)
	{
		$select->distinct()
			->join('types_unite', 'unites.type = types_unite.id', array())
			->order('types_unite.ordre');
	}

	protected function _getStatut($ouverte, $where = null) {
		$select = $this->_db->select()->distinct()
			->from('unites');

		if ($ouverte) {
			// appartenances à l'unité parente. C'est
			// incomplet car on pourrait avoir les
			// effectifs des patrouilles sans la mâitrise
			// (PL) et donc avoir une HP.
			$select->join('appartient',
				      "appartient.unite = unites.id".
				      " OR ".
				      ("((unites.type = 'hp' OR unites.type = 'aines')".
				       " AND ".
				       "appartient.unite = unites.parent)"),
				      array())
				->where('fin IS NULL');
		}
		else {
			$select->joinLeft('appartient',
					  'appartient.unite = unites.id'.
					  ' AND '.
					  'appartient.fin IS NULL', array());
			$select->where("appartient.unite IS NULL");
			$select->where("unites.type <> 'hp' AND unites.type <> 'aines'");
		}

		if ($where)
			$select->where($where);

		return $this->fetchSelect($select);
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
		$acl->allow(null, $this, array('index',
					       'trombi', 'sousunites',
					       'rapport', 'rapports'));

		switch($this->type) {
		case 'hp':
		case 'aines':
			// TODO : gérer ça proprement ?
			$acl->addRole(new Zend_Acl_Role($this->getRoleRoleId('chef')));
			$acl->addRole(new Zend_Acl_Role($this->getRoleRoleId('assistant')));
			break;
		default:
			// GESTION DES NOM DE JUNGLE
			if ($this->type == 'meute') {
				// par défaut, permettre de voir les nom
				$acl->allow(null, $this, 'voir-nom');
				$siz =
					$this->getTable()->fetchAll("type = 'sizloup' OR type = 'sizjeannette'");

				$acl->addRole($a = new Zend_Acl_Role($this->getRoleRoleId('assistant')));
				$t = new Roles();
				$rs = $t->fetchAll("type = 'meute' OR type = 'ronde'");
				foreach($rs as $role) {
					// ON AJOUTE CHAQUE NOM DE JUNGLE COMME ASSISTANT
					$r = new Zend_Acl_Role($this->getRoleRoleId($role->id));
					if (!$acl->hasRole($r))
						$acl->addRole($r, $a);

					// ON INTERDIT AUX LOUP DE VOIR LES NOM DE JUNGLE.
					$r = new Zend_Acl_Resource($this->getRoleRoleId($role->id));
					if (!$acl->has($r)) {
						$acl->add($r);
						// refuser aux sizaines de voir le nom des cheftaines
						foreach($siz as $s) {
							$acl->deny($s, $r, 'voir-nom');
							$acl->deny($s, $r, 'voir');
						}
					}
				}
			}

			// Donner aux assistante tout les pouvoirs sur
			// l'unité. Oui c'est du machisme, mais c'est
			// plus rare les cheftaines qui se soucient de
			// l'informatique …
			if ($this->findParentTypesUnite()->sexe == 'f') {
                                // CORRECTIF à l'arrache du 15 juin 2012 : il faut tester la création des
                                // rôle pour les unités féminines.
			        $r = new Zend_Acl_Role($this->getRoleRoleId('assistant'));
				if (!$acl->hasRole($r))
					$acl->addRole($r);
				$acl->allow($r, $this);
                        }


			// considérer les chef et assistants des
			// unités sœur comme chef et assistant cette
			// unité
			$tu = $this->getTable();
			$db = $tu->getAdapter();
			$select = $db->select()
				->from("unites")
				->where("unites.parent = ?", $this->parent)
				->where("unites.type = 'hp' OR unites.type = 'aines'");
			$soeur = $tu->fetchSelect($select)->current();
			$soeurroles = $soeur ? array($soeur, $soeur->getRoleRoleId('assistant')) : array();

			$roles = $this->findParentTypesUnite()->findRoles();
			// Pour chaque roles des unités sœur
			foreach($roles as $role) {
				$rid = $this->getRoleRoleId($role->id);
				if (!$acl->hasRole($rid)) {
					// ajouter le role des
					// maîtrise comme étant
					// assistant dans cette unité.
					// TODO: gérér globalement car
					// il y a ici une course, dans
					// le cas de page web sans
					// état, c'est peu gênant.
					$parent = in_array($role->id, array('chef', 'assistant')) ? $soeurroles : array();
					$acl->addRole(new Zend_Acl_Role($rid), $parent);
				}
			}
		}

		// permettre au chef d'unités racine de valider les inscriptions.
		if (!$this->parent) {
			if (!$acl->has('membres'))
				$acl->add(new Zend_Acl_Resource('membres'));

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
		return $this->id;
	}

	public function getRoleId()
	{
		return $this->id;
	}

	public function getRoleRoleId($role, $annee = null)
	{
		$r = $role;
		switch ($role) {
		case 'aumonier':
		case 'akela':
                case 'guillemette':
			$role = 'chef';
			break;
		case 'tresorier':
		case 'secretaire';
		case 'baloo':
		case 'bagheera':
		case 'raksha':
		case 'chil':
		case 'kaa':
		case 'hathi':
		case 'wontolla':
		case 'rama':
		case 'rikki':
		case 'chuchundra':
		case 'gris':
		case 'fauvette':
		case 'sahi':
			$role = 'assistant';
			break;
		}

		return ($role ? $role.'-' : '').$this->id.($annee ? '-'.$annee : '');
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

	function getImage($id = null, $test = true)
	{
		$id = $id ? $id : $this->id;
		$image = 'data/unites/'.$id.'.png';
		return !$test || is_readable($image) ? $image : null;
	}

	function getWiki($id = null, $test = true)
	{
		$id = $id ? $id : $this->id;
		$image = 'private/unites/'.$id.'.wiki';
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
			if ($st->id == 'hp')
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
			$select = $db->select()
				->from('unites')
				->where('unites.parent = ?', $this->id);

			if (!is_null($annee)) {
				$select->join('appartient',
					      'appartient.unite = unites.id'.
					      ' OR '.
					      ("((unites.type = 'hp' OR unites.type = 'aines')".
					       " AND ".
					       " appartient.unite = unites.parent)"),
					      array());
				if ($annee === false)
					$select->where('appartient.fin IS NULL');
				else if ($annee === true)
					$select->where('appartient.fin IS NOT NULL');
				else {
					$date = ($annee+1).'-06';
					$select->where("debut < ? AND (fin IS NULL OR fin >= ?)", $date);
				}
			}

			$su = $this->getTable()->fetchSelect($select);
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

		$select = $db->select()
			->distinct()
			->from('appartient')
			->join('roles',
			       'roles.type = appartient.type AND roles.id = appartient.role',
			       array());
		
		switch($this->type) {
		case 'hp':
			$where[]= 'appartient.role = "chef" OR appartient.role = "assistant"';
		case 'aines':
			$select->join('unites',
				      'appartient.unite = unites.id'.
				      ' AND '.
				      '('.($db->quoteInto('unites.parent = ?',
							  $this->parent).
					   ' OR '.
					   $db->quoteInto('unites.id = ?',
							  $this->parent)).
				      ')',
				      array());
			break;
		default:
			$select->where($db->quoteInto('unite = ?', $this->id));
			if ($recursive) {
				$in = $db->select()
					->from(array('filles' => 'unites'), 'id')
					->where("filles.parent = '".$this->id."'");
				$select->orWhere($db->quoteInto('unite IN (?)',
								new Zend_Db_Expr($in->__toString())));
			}
			break;
		}

		$select->join('individu',
			      "individu.slug = appartient.individu\n",
			      array())
			->order('roles.ordre')
			->order('naissance');
		$where = array_filter($where);
		foreach($where as $clause)
			if ($clause)
				$select->where($clause);

		$ta = new Appartenances();
		return $ta->fetchSelect($select);
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
		return $tp->fetchSelect($select);
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
		$select = $ti->getAdapter()->select()
			->distinct()
			->from('appartient',
			       array('poste' => 'role',
				     'debut' => "strftime('%Y', debut, '-8 months')",
				     'fin' => "strftime('%Y', fin, '-7 months')"))
			->join('individu',
			       'individu.slug = appartient.individu')
			->order('debut ASC');
        
		switch($this->type) {
		case 'hp':
		case 'aines':
			$select->join('unites',
				      'unites.id = appartient.unite',
				      array());
			$select->where("unites.id = ?", $this->parent);
			break;
		default:
			$select->where("appartient.unite = ?", $this->id);
			break;
		}

		$is = $ti->fetchSelect($select);
		$annees = array();
		$cette_annee = intval(strftime('%Y', time()-243*24*60*60));
		foreach($is as $individu) {
			 $fin = $individu->fin ? $individu->fin : $cette_annee;
			 $fin = ($fin == $cette_annee) ? $fin+1 : $fin;
			 for($annee = $individu->debut; $annee < $fin; $annee++) {
				 if (!array_key_exists($annee, $annees))
					 $annees[$annee] = null;

				if ($individu->poste == 'chef') {
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
	protected $_name = 'types_unite';
	protected $_rowClass = 'TypeUnite';
	protected $_dependentTables = array('Unites', 'TypesUnite', 'Roles');
	protected $_referenceMap = array('Parent' => array('columns' => 'parent',
							   'refTableClass' => 'TypesUnite',
							   'refColumns' => 'id'));

	function getTypesRacine()
	{
		$select = $this->getAdapter()->select()
			->where('parent IS NULL');
		return $this->fetchSelect($select);
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
		case 'clan':
		case 'feu':
		case 'meute':
		case 'ronde':
		case 'sizaine':
			return null;
		case 'troupe':
		case 'compagnie':
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
	protected $_name = 'roles';
	protected $_rowClass = 'Role';
	protected $_dependentTables = array('Appartenances', 'Privileges');
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

