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
  protected $_referenceMap = array('Etape' => array('columns'		=> 'etape',
						    'refTableClass'	=> 'Etapes',
						    'refColumns'	=> 'id',
						    'onUpdate'		=> self::CASCADE,
						    'onDelete'		=> self::SET_NULL),
				   );

  function selectAll()
  {
    return $this->select()
      ->setIntegrityCheck(false)
      ->from($this->_name)
      ->order('individu.nom')
      ->order('individu.prenom');
  }

  function findAdmins()
  {
    $s = $this->select()
      ->setIntegrityCheck(false)
      ->from($this->_name)
      ->join('user', 'user.individu = individu.id', array())
      ->where('user.admin > 0');
    return $this->fetchAll($s);
  }

  function findChefsRacines()
  {
    $s = $this->select()
      ->setIntegrityCheck(false)
      ->distinct()
      ->from($this->_name)
      ->join('appartenance', 'appartenance.individu = individu.id', array())
      ->join('unite', 'unite.id = appartenance.unite', array())
      ->join('unite_role', 'unite_role.id = appartenance.role', array())
      ->where("unite_role.acl_role = 'chef'")
      ->where('unite.parent IS NULL');
    return $this->fetchAll($s);
  }
}

class Individu extends Strass_Db_Table_Row_Abstract implements Zend_Acl_Resource_Interface, Zend_Acl_Role_Interface
{
  protected $_tableClass = 'Individus';
  protected $_privileges = array(array('chef',	NULL),
				 array('assistant', 'editer'),
				 array(NULL, 'fiche'));

  public function getResourceId()
  {
    return 'individu-'.$this->slug;
  }

  function initAclResource($acl)
  {
    $acl->add(new Zend_Acl_Resource($this->getResourceId()));

    $this->initPrivileges($acl, $this->findUnites());

    $acl->allow('membres', $this, 'fiche');
    $acl->deny(NULL, $this, 'voir-nom');
    $acl->allow('membres', $this, 'voir-nom');
    if ($acl->hasRole($this)) {
      $acl->allow($this, $this, array('editer', 'desinscrire'));
      $acl->deny($this, $this, 'desinscrire');
    }
  }

  public function getRoleId()
  {
    return 'individu-'.$this->slug;
  }

  function initAclRole($acl) {
    $parents = array();

    $apps = $this->findAppartenances();
    foreach($apps as $app) {
      // Choix conservateur : si on n'est plus chef, on ne l'est plus
      // et deviens un simple membre. Donc on perd les privilèges (qui
      // eux, sont indépendant du temps, le chef d'aujourd'hui peut
      // éditer tout, mais le chef d'hier ne peut plus rien).
      if ($app->fin)
	$parents[] = $app->findParentUnites()->getRoleId('membre');
      else
	$parents[] = $app->getRoleId();
    }

    if ($this->totem)
      $parents[] = 'sachem';

    $acl->addRole($this, $parents);
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
    try {
      return $this->getFullName();
    }
    catch (Exception $e) {
      error_log((string) $e);
      return "Individu #".$this->id;
    }
  }

  function capitalizedLastname($compact=false)
  {
    $noms = preg_split("`[ '-]`", $this->nom);
    $nom = array();
    foreach($noms as $n) {
      $n = mb_strtolower($n);
      switch($n) {
      case "d":
      case "l":
	$nom[] = $n."'";
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
	if ($compact)
	  $nom[] = mb_strtoupper($n{0}).'. ';
	else
	  $nom[] = mb_strtoupper($n).' ';
	break;
      }
    }
    return implode('', $nom);
  }

  function getFullName($compute = true, $totem = true, $compact = false)
  {
    $acl = Zend_Registry::get('acl');

    // si je suis un sachem
    if ($this->totem && ($compute && $totem)) {
      // et que l'utilisateur est un sachem/admin
      if ($acl->isAllowed(null, $this, 'totem')) {
	// montrer mon totem
	return wtk_ucfirst($this->totem);
      }
    }
    if ($acl->isAllowed(null, $this, 'voir-nom'))
      return trim(wtk_ucfirst($this->prenom)." ".$this->capitalizedLastname($compact));
    else if ($compute && $app = $this->findAppartenances()->current()) {
      $role = $app->findParentRoles();
      $mineur = $role->findParentTypesUnite()->age_min < 17;
      if ($role->nom_jungle)
	/* Branche jaune, préférer Akéla, Guillemette pour les inconnus */
	return $app->getTitre();
      else
	/* Prénom et initiales des mineurs pour les visiteurs*/
	return trim(wtk_ucfirst($this->prenom)." ".$this->capitalizedLastname($compact || $mineur));
    }
    else
      /* dans le doute, on masque plutôt que de fuiter un prénom de cheftaine à un louveteau*/
      return 'Nom masqué';
  }

  function getName()
  {
    return $this->getFullName(true, true, true);
  }

  function getDateNaissance($format = "%e/%m/%Y")
  {
    return strftime($format, strtotime($this->naissance));
  }

  function getAge()
  {
    if ($this->naissance)
      return date("Y", time() - strtotime($this->naissance)) - date("Y", 0);
    else
      return null;
  }

  function getTelephone()
  {
    if ($this->portable)
      return $this->portable;
    else
      return $this->fixe;
  }

  function isAncien()
  {
    $t = new Appartenances();
    $s = $t->select()->where('fin IS NULL');
    return $this->findAppartenances($s)->count() == 0;
  }

  function estActifDans($unite)
  {
    $t = new Appartenances;
    $db = $t->getAdapter();
    $s = $t->select()
      ->setIntegrityCheck(false)
      ->from('appartenance')
      ->join('individu', $db->quoteInto('individu.id = ?', $this->id), array())
      ->join('unite', $db->quoteInto('unite.id = ?', $unite->id), array())
      ->where('appartenance.fin IS NULL');
    return (bool) $t->countRows($s);
  }

  function storeImage($path)
  {
    if ($fichier = $this->getImage())
      unlink($fichier);

    $fichier = $this->getImage(null, false);

    $dossier = dirname($fichier);
    if (!file_exists($dossier))
      mkdir($dossier, 0700, true);

    $config = Zend_Registry::get('config');

    $photo = new Imagick($path);
    $width = $photo->getImageWidth();
    $height = $photo->getImageHeight();

    $image = new Imagick;
    $image->newImage($width, $height, "white", 'jpeg');
    $image->setImageCompression(Imagick::COMPRESSION_JPEG);
    $image->setImageCompressionQuality($config->get('photo/qualite', 85));
    $image->compositeImage($photo, Imagick::COMPOSITE_OVER, 0, 0);

    $MAX = $config->get('photo/taille_vignette', 256);
    if (min($width, $height) > $MAX)
      $image->cropThumbnailImage($MAX, $MAX);

    $image->writeImage($fichier);

    unset($image);
  }

  function findAppartenances($s=null)
  {
    if (is_null($s)) {
      $t = new Appartenances;
      $s = $t->select()
	// Placer les inscriptions en cours en premier
	->order(new Zend_Db_Expr('(CASE WHEN appartenance.fin IS NULL THEN 0 ELSE 1 END)'))
	->order('appartenance.fin DESC');
    }
    return parent::findAppartenances($s);
  }

  function findEtapesCanditates()
  {
    $t = new Etapes;
    $s = $t->select()
      ->setIntegrityCheck(false)
      ->from('etape')
      ->where("etape.sexe IN ('m', ?)", $this->sexe);
    if ($age = $this->getAge())
      $s->where("? >= etape.age_min", $age);
    return $t->fetchAll($s);
  }

  function findInscriptionsActives()
  {
    $s = $this->getTable()->select()->where('fin IS NULL');
    return parent::findAppartenances($s);
  }

  function findUnitesCandidates()
  {
    $t = new Unites;
    $s = $t->select()
      ->setIntegrityCheck(false)
      ->distinct()
      ->from('unite')
      ->join('unite_type', "unite_type.id = unite.type\n", array())
      ->where("unite_type.sexe IN ('m', ?)\n", $this->sexe)
      ->where("NOT unite_type.virtuelle\n");

    if ($age = $this->getAge())
      $s->where("unite_type.age_min <= ?\n", $age);

    return $t->fetchAll($s);
  }

  function findRolesCandidats($unite, $filter_current=true)
  {
    $t = new Roles;
    $db = $t->getAdapter();
    $s = $t->select()
      ->setIntegrityCheck(false)
      ->from('unite_role')
      ->join('unite_type', 'unite_type.id = unite_role.type', array())
      ->join('unite', 'unite.type = unite_type.id', array())
      ->where('unite.id = ?', $unite->id);
    if ($filter_current)
      $s->joinLeft('appartenance',
		   'appartenance.role = unite_role.id AND '.
		   'appartenance.unite = unite.id AND '.
		   $db->quoteInto('appartenance.individu', $this->id),
		   array())
	->where('appartenance.id IS NULL');
    return $t->fetchAll($s);
  }

  function findInscriptionSuivante($annee)
  {
    $t = new Appartenances;
    $db = $t->getAdapter();
    $s = $t->select()
      ->setIntegrityCheck(false)
      ->from('appartenance')
      ->where('appartenance.individu = ?', $this->id)
      ->where('appartenance.debut >= ?', $annee.'-09-01')
      ->order('appartenance.debut')
      ->limit(1);
    return $t->fetchAll($s)->current();
  }

  function findActivites($annee=null)
  {
    $t = new Activites;
    $db = $t->getAdapter();
    $s = $t->select()
      ->setIntegrityCheck(false)
      ->distinct()
      ->from('activite')
      ->join('participation', 'participation.activite = activite.id', array())
      ->join('unite', 'unite.id = participation.unite', array())
      ->join('appartenance',
	     $db->quoteInto("appartenance.individu = ?", $this->id)." AND ".
	     "appartenance.unite = unite.id", array())
      // on trie de manière à avoir les activités
      // les plus récentes en haut de liste.
      ->order('activite.debut DESC');

    if ($annee) {
      $s->where("activite.debut >= ?", $annee.'-08-31');
      $s->where("activite.fin <= ?", ($annee+1).'-08-31');
    }

    return $t->fetchAll($s);
  }

  function getImage($slug = null, $test = true)
  {
    $ind = Zend_Registry::get('user');
    if (!$ind)
      return null;
    $slug = $slug ? $slug : $this->slug;
    $image = 'data/avatars/'.$slug.'.png';
    return !$test || is_readable($image) ? $image : null;
  }

  /*
   * Retourne la liste de toutes les unités où l'individu a un rôle,
   * récursivement.
   */
  function findUnites($actif = TRUE)
  {
    $t = new Unites;
    $s = $t->select()
      ->setIntegrityCheck(false)
      ->distinct()
      ->from('unite')
      ->joinLeft(array('parent' => 'unite'), 'parent.id = unite.parent', array())
      ->joinLeft(array('grandparent' => 'unite'), 'grandparent.id = parent.parent', array())
      ->join('appartenance', 'appartenance.unite IN (unite.id, parent.id, grandparent.id)', array())
      ->where('appartenance.individu = ?', $this->id);

    if ($actif === true)
      $s->where('appartenance.fin IS NULL');
    else if ($actif === false)
      $s->where('appartenance.fin IS NOT NULL');

    return $t->fetchAll($s);
  }

  function findCommentaires($select)
  {
    $select->where('commentaire.parent IS NOT NULL');
    return parent::findCommentaires($select);
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
								 'onUpdate'		=> self::CASCADE,
								 'onDelete' => self::CASCADE),
					'Role'		=> array('columns'		=> 'role',
								 'refTableClass'	=> 'Roles',
								 'refColumns'		=> 'id'));
}

class Appartient extends Strass_Db_Table_Row_Abstract implements Zend_Acl_Role_Interface, Zend_Acl_Resource_Interface
{
  protected $_tableClass = 'Appartenances';

  public function getRoleId()
  {
    return $this->findParentUnites()->getRoleId($this->findParentRoles()->acl_role);
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
    if ($this->fin)
      return strftime('%Y', strtotime($this->fin) - 10 * 31 * 24 * 60 * 60);
    else
      return null;
  }

  function getAccronyme()
  {
    if ($this->titre)
      return $this->titre;
    else
      return $this->findParentRoles()->getAccronyme();
  }

  function getTitre()
  {
    if ($this->titre)
      return $this->titre;
    else
      return $this->findParentRoles()->titre;
  }

  function getShortDescription()
  {
    return $this->getAccronyme().' '.$this->findParentUnites()->getName();
  }

  function _postInsert()
  {
    $this->findParentUnites()->clearCacheSousUnites();
  }

  function _postDelete()
  {
    $this->findParentUnites()->clearCacheSousUnites();
  }
}


class Inscriptions extends Strass_Db_Table_Abstract implements Zend_Acl_Resource_Interface
{
  protected $_name = 'inscription';
  protected $_rowClass = 'Inscription';

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

  function findByEMail($email)
  {
    $s = $this->select()
      ->from($this->_name)
      ->where('adelec = ?', $email);
    return $this->fetchOne($s);
  }
}

class Inscription extends Strass_Db_Table_Row_Abstract
{
  function getFullname()
  {
    return $this->prenom.' '.$this->nom;
  }

  function findIndividus()
  {
    $t = new Individus;
    $s = $t->select()
      ->where('slug LIKE ?', wtk_strtoid($this->getFullname()).'%');
    return $t->fetchAll($s)->current();
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
    $config = Zend_Registry::get('config');
    return hash('md5', $username.':'.$config->system->realm.$config->system->realm_suffix.':'.$password);
  }

  function selectAll()
  {
    return $this->select()
      ->setIntegrityCheck(false)
      ->from($this->_name)
      ->join('individu', 'individu.id = user.id', array())
      ->order('individu.nom')
      ->order('individu.prenom');
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

  public function getResourceId()
  {
    return 'user-'.$this->username;
  }

  function initAclResource($acl)
  {
    $acl->add(new Zend_Acl_Resource($this->getResourceId()));
    if ($acl->hasRole($this)) {
      $acl->allow($this, $this, 'parametres');
      $acl->deny($this, $this, 'sudo');
    }
  }

  public function getRoleId()
  {
    return 'user-'.$this->username;
  }

  function initAclRole($acl)
  {
    $parents = array('membres');
    // hériter des privilèges de l'utilisateur
    if ($this->admin)
      $parents[] = 'admins';

    $individu = $this->findParentIndividus();
    $individu->initAclRole($acl);
    $parents[] = $individu->getRoleId();

    $acl->addRole(new Zend_Acl_Role($this->getRoleId()), $parents);
  }

  protected $_individu;

  function findParentIndividus()
  {
    /* cache pour économiser pas tant la requête individu que les ACL
       qui vont avec */
    if (!$this->_individu)
      $this->_individu = parent::findParentIndividus();

    return $this->_individu;
  }

  function isMember()
  {
    return true;
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
    $config = Zend_Registry::get('config');
    return array('username' => $this->username, 'realm' => $config->system->realm);
  }
}

class FakeIndividu implements Zend_Acl_Resource_Interface, Zend_Acl_Role_Interface {
  function __construct($user) {
    $this->user = $user;
    $this->slug = $user->username;
  }

  function initResourceAcl() {
    $acl = Zend_Registry::get('acl');
    if (!$acl->has($this)) {
      $acl->add($this);
    }
  }

  function initAclRole($acl) {
    if (!$acl->hasRole($this)) {
      $acl->addRole($this);
    }
  }

  function findUnites() {
    return array();
  }

  function getFullName() {
    return null;
  }

  public function getRoleId()
  {
    return 'individu-'.$this->slug;
  }

  public function getResourceId()
  {
    return 'individu-'.$this->slug;
  }

  function findActivites()
  {
    return array();
  }
}

class Nobody implements Zend_Acl_Resource_Interface, Zend_Acl_Role_Interface {
  function __construct() {
    $this->id = null;
    $this->username = 'nobody';
    $this->admin = false;
    $this->last_login = null;
    $this->individu = new FakeIndividu($this);

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

  function initAclRole($acl) {
    $this->individu->initAclRole($acl);
  }

  function getIdentity()
  {
    return array('username' => 'nobody', 'realm' => null);
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

  function findUnites() {
    return array();
  }

  function findParentIndividus() {
    return $this->individu;
  }
}
