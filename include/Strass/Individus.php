<?php

require_once 'Unites.php';
require_once 'Photos.php';

class Individus extends Strass_Db_Table_Abstract
{
  protected $_name = 'individu';
  protected $_rowClass = 'Individu';
  protected $_dependentTables = array('Users',
				      'Appartenances',
				      'Commentaires');
  protected $_referenceMap = array('Etape'	=> array('columns'		=> 'etape',
							 'refTableClass'	=> 'Etapes',
							 'refColumns'		=> 'id',
							 'onUpdate'		=> self::CASCADE,
							 'onDelete'		=> self::CASCADE),
				   );
}

class Individu extends Strass_Db_Table_Row_Abstract implements Zend_Acl_Role_Interface, Zend_Acl_Resource_Interface
{
  protected $_privileges = array(array('chef',	NULL),
				 array('assistant', 'editer'),
				 array(NULL,	'fiche'));

  protected $apps = array();

  public function __construct(array $config = array())
  {
    parent::__construct($config);

    $this->initResourceAcl($this->getUnites(NULL));

    $t = new Appartenances();
  }

  function _initResourcesAcl($acl)
  {
    $acl->allow('individus', $this, 'fiche');
  }

  function _initRoleAcl($acl)
  {
    $acl->allow($this, $this, array('editer', 'desinscrire'));
    $acl->deny($this, $this, 'sudo');
  }

  protected function _parentRoles()
  {
    $acl = Zend_Registry::get('acl');

    // Choix conservateur : si on n'est plus chef, on ne l'est
    // plus. Donc on perd les privilèges (qui eux, sont
    // indépendant du temps, le chef d'aujourd'hui peut éditer
    // tout, mais le chef d'hier ne peut plus rien).
    $apps = $this->findAppartenances('fin IS NULL');
    $roles = array('individus');

    foreach($apps as $app) {
      $u = $app->findParentUnites();
      // retourne siz pour les sizaine, chef sinon
      $roles[] = $app->getRoleId();
      $roles[] = $u->getRoleId();
      $parente = $u->findParentUnites();
      // récursif ?
      if ($parente) {
	$roles[] = $parente;
	$grandmere = $parente->findParentUnites();
	if ($grandmere)
	  $roles[] = $grandmere;
      }
      // Un membre d'une unité est l'équivalent d'un chef d'une
      // sous-unité. Dans les faits, les membres d'une unités
      // sont se résument à l'ensemble de la maîtrise de la dite
      // unité exceptées les unités finales
      $roles = array_merge($roles, $this->getSousRoles($acl, $u));
    }

    if ($this->totem)
      $roles[] = 'sachem';

    return $roles;
  }

  protected function getSousRoles($acl, $unite)
  {
    $su = $unite->findUnites();
    $roles = array();
    foreach($su as $u) {
      $role = $u->type == 'sizloup' || $u->type == 'sizjeannette' ? 'siz' : 'chef';
      $roles[] = $u->getRoleId();
      $roles[] = $u->getRoleRoleId($role);
      $roles = array_merge($roles,
			   $this->getSousRoles($acl, $u));
    }
    return $roles;
  }

  public function getRoleId()
  {
    return 'individu-'.$this->slug;
  }

  public function getResourceId()
  {
    return 'individu-'.$this->slug;
  }

  function findUser() {
    $user = $this->findUsers()->current();
    return $user ? $user : new Nobody;
  }

  function findArticles() {
    $t = new Articles;
    $s = $t->select()
      ->setIntegrityCheck(false)
      ->distinct()
      ->from('article')
      ->join('commentaire', 'commentaire.id = article.commentaires', array())
      ->where('commentaire.auteur = ?', intval($this->id));
    return $t->fetchAll($s);
  }

  function isMember() {
    return $this->findUser()->username != 'nobody';
  }

  function __toString()
  {
    return $this->getFullName();
  }

  /*
   * retourne si $i a le droit de voir le nom de $this
   */
  public function voirNom($i = null)
  {
    $ind = Zend_Registry::get('individu');
    return $ind && $ind->getAge() > 11;
  }

  function getFullName($compute = true, $totem = true)
  {
    $ind = Zend_Registry::get('individu');
    if ($compute && !$ind)
      // aux inconnus, on n'affiche que les initiales.
      return $this->getName();

    // si je suis un sachem
    if (($compute && $totem) && $this->totem) {
      // et que l'utilisateur est un sachem/admin
      $acl = Zend_Registry::get('acl');
      if ($acl->isAllowed($ind, $this, 'totem')) {
	// montrer mon totem
	return wtk_ucfirst($this->totem);
      }
    }

    // si l'utilisateur n'a pas le droit de voir le nom, retourner le
    // nom de jungle (ou équivalent).
    if ($compute && $ind && !$this->voirNom()) {
      $app = $this->findAppartenances()->current();
      return $app->findParentRoles()->titre;
    }
    // retourner effectivement le nom.
    else {
      $noms = preg_split("`[ '-]`", $this->nom);
      $nom = array();
      foreach($noms as $n) {
	switch($n) {
	case "d":
	case "l":
	  $nom[]=$n."'";
	break;
	case 'de':
	case 'la':
	case 'du':
	case 'des':
	case 'van':
	case 'von':
	  $nom[] = $n.' ';
	  break;
	default:
	  $nom[] = mb_strtoupper($n).' ';
	  break;
	}
      }
      return trim(wtk_ucfirst($this->prenom)." ".implode('',$nom));
    }
  }

  function getName()
  {
    if ($this->voirNom()) {
      $noms = preg_split("`[ '-]`", $this->nom);
      $nom = array();
      foreach($noms as $n) {
	switch($n) {
	case "d":
	case "l":
	  $nom[]=$n."'";
	break;
	case 'de':
	case 'la':
	case 'du':
	case 'des':
	case 'van':
	case 'von':
	  $nom[] = $n.' ';
	  break;
	default:
	  $nom[] = $n{0}.'. ';
	  break;
	}
      }
      return $this->prenom.' '.implode('', $nom);
    }
    else if ($app = $this->findAppartenances()->current()) {
      return $app->findParentRoles()->titre;
    }
    else {
      return 'Nom masqué';
    }
  }

  function getDateNaissance($format = "%e/%m/%Y")
  {
    return strftime($format, strtotime($this->naissance));
  }

  function getAge()
  {
    return date("Y", time() - strtotime($this->naissance)) - date("Y", 0);
  }

  function isAncien()
  {
    $t = new Appartenances();
    $s = $t->select()->where('fin IS NULL');
    return $this->findAppartenances($s)->count() == 0;
  }

  function getImage($id = null, $test = true)
  {
    $ind = Zend_Registry::get('user');
    if (!$ind)
      return null;
    $id = $id ? $id : $this->id;
    $image = 'data/avatars/'.$id.'.png';
    return !$test || is_readable($image) ? $image : null;
  }

  /*
   * Retourne la liste de toutes les unités où l'individu a un rôle,
   * récursivement.
   */
  function getUnites($actif = TRUE, $recursif = FALSE)
  {
    $unites = array();
    $where = is_null($actif) ? NULL : "fin IS ".($actif ? "": " NOT ")." NULL";
    if ($where) {
      $t = new Unites;
      $s = $t->select()->where($where);
    }
    else {
      $s = NULL;
    }
    $us = $this->findUnitesViaAppartenances($s);
    foreach($us as $u) {
      $unites[$u->id] = $u;
      $unites = array_merge($unites,
			    ($recursif ? $u->getSousUnites() : array()));
    }
    return $unites;
  }

  /*
   * Sélectionne les activités concernées par l'individus;
   */
  function getActivites($futures = TRUE, $count = null)
  {
    $db = $this->getTable()->getAdapter();
    // récupérer toutes les unités concernées.

    // récupérer les activités des ces unités.
    $select = $db->select()
      ->from('activites')
      ->distinct()
      ->join('participe',
	     $db->quoteInto('participe.activite = activites.id AND participe.unite IN (?)',
			    new Zend_Db_Expr($db->select()
					     ->from('unites', 'id')
					     ->join('appartient',
						    $db->quoteInto('appartient.unite = unites.id'.
								   ' AND '.
								   'appartient.individu = ?',
								   $this->id),
						    array())
					     ->__toString())),
	     array())
      ->order('debut');

    if (!is_null($futures)) {
      $select->where('debut '.($futures ? '>' : '<').' STRFTIME("%Y-%m-%d %H:%M", "NOW")');
    }

    if (!is_null($count)) {
      $select->limit($count);
    }

    $activites = new Activites();
    return $activites->fetchAll($select);
  }

  function _postDelete()
  {
    if ($i = $this->getImage())
      unlink($i);
  }

  function _postUpdate()
  {
    if ($i = $this->getImage($this->_cleanData['id']))
      rename($i, $this->getImage());
  }
}

class Etapes extends Strass_Db_Table_Abstract
{
  protected $_name =  'etape';
  protected $_dependentTables = 'Individus';
}


class Appartenances extends Strass_Db_Table_Abstract
{
  protected	$_name		= 'appartenance';
  protected	$_rowClass	= 'Appartient';
  protected	$_referenceMap	= array('Individu'	=> array('columns'		=> 'individu',
								 'refTableClass'	=> 'Individus',
								 'refColumns'		=> 'id',
								 'onUpdate'		=> self::CASCADE,
								 'onDelete'		=> self::CASCADE),
					'Unite'		=> array('columns'		=> 'unite',
								 'refTableClass'	=> 'Unites',
								 'refColumns'		=> 'id',
								 'onUpdate'		=> self::CASCADE),
					'Role'		=> array('columns'		=> 'role',
								 'refTableClass'	=> 'Roles',
								 'refColumns'		=> 'id'));
}

class Appartient extends Strass_Db_Table_Row_Abstract implements Zend_Acl_Role_Interface, Zend_Acl_Resource_Interface
{
  function __construct($config)
  {
    parent::__construct($config);
    $this->initRoleAcl();
  }

  public function getRoleId()
  {
    return $this->findParentUnites()->getRoleRoleId($this->findParentRoles()->acl_role);
  }

  public function getResourceId()
  {
    return $this->getRoleId();
  }

  public function getDebut($format = '%e-%m-%Y')
  {
    return strftime($format, strtotime($this->debut));
  }

  public function getFin($format = '%e-%m-%Y')
  {
    return strftime($format, strtotime($this->fin));
  }

  function getAnnee()
  {
    return strftime('%Y', strtotime($this->debut) - 243 * 24 * 60 * 60);
  }
}


class Inscriptions extends Strass_Db_Table_Abstract implements Zend_Acl_Resource_Interface
{
  protected	$_name		= 'inscriptions';

  function __construct()
  {
    parent::__construct();
    $acl = Zend_Registry::get('acl');
    if (!$acl->has($this)) {
      $acl->add($this);
    }
  }


  function getResourceId()
  {
    return 'inscriptions';
  }
}

class Users extends Strass_Db_Table_Abstract
{
  protected $_name = 'user';
  protected $_rowClass = 'User';
  protected $_referenceMap = array('Individu' => array('columns'       => 'individu',
						       'refTableClass' => 'Individus',
						       'refColumns'    => 'id',
						       'onUpdate'      => self::CASCADE,
						       'onDelete'      => self::CASCADE),
				   );

  static function hashPassword($username, $password) {
    /* Free suffixe le realm par l'UID. On doit donc générer le
       hash avec le suffixe pour que ça corresponde. */
    $config = new Strass_Config_Php('strass');
    return hash('md5', $username.':'.$config->site->realm.$config->site->realm_suffix.':'.$password);
  }

  function findByUsername($username) {
    $s = $this->select()->where('username = ?', $username);
    return $this->fetchOne($s);
  }

  function findByRecoverToken($token) {
    $s = $this->select()
      ->where('recover_token = ?', $token)
      ->where("recover_deadline > strftime('%s', 'now')");
    return $this->fetchOne($s);
  }

  function findByEMail($email)
  {
    $s = $this->select()
      ->from('user')
      ->join('individu', 'individu.id = user.individu', array())
      ->where('individu.adelec = ?', $email);
    return $this->fetchOne($s);
  }
}

class User extends Strass_Db_Table_Row_Abstract implements Zend_Acl_Role_Interface, Zend_Acl_Resource_Interface
{
  public function __construct(array $config = array())
  {
    parent::__construct($config);

    $this->initResourceAcl();
  }

  function _initRoleAcl($acl)
  {
    $acl->allow($this, $this, 'parametres');
  }

  protected function _parentRoles()
  {
    $acl = Zend_Registry::get('acl');

    $roles = array('membres');
    // hériter des privilèges de l'utilisateur
    if ($this->admin)
      $roles[] = 'admins';
    $roles[] = $this->findParentIndividus();

    return $roles;
  }

  function isMember()
  {
    return true;
  }

  public function getRoleId()
  {
    return 'user-'.$this->username;
  }

  public function getResourceId()
  {
    return 'user-'.$this->username;
  }

  function testPassword($password) {
    $digest = Users::hashPassword($this->username, $password);
    return $digest == $this->password;
  }

  function setPassword($password) {
    $digest = Users::hashPassword($this->username, $password);
    $this->password = $digest;
    return $this;
  }

  function getIdentity() {
    $config = new Strass_Config_Php('strass');
    return array('username' => $this->username, 'realm' => $config->realm);
  }
}

class Nobody implements Zend_Acl_Resource_Interface, Zend_Acl_Role_Interface {
  function __construct() {
    $this->id = null;
    $this->username = 'nobody';
    $this->admin = false;
    $this->last_login = null;

    $acl = Zend_Registry::get('acl');
    if (!$acl->has($this)) {
      $acl->add($this);
    }
    if (!$acl->hasRole($this)) {
      $acl->addRole($this);
    }
  }

  function initResourceAcl() {
  }

  function initRoleAcl() {
  }

  public function getRoleId()
  {
    return $this->username;
  }

  public function getResourceId()
  {
    return $this->username;
  }

  function isMember()
  {
    return false;
  }

  function getUnites() {
    return array();
  }

  function findParentIndividus() {
    return null;
  }
}
