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
      $sus = $unite->findSousUnites(true, $annee);
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

  function fetchAll($where = NULL, $order = NULL, $count = NULL, $offset = NULL)
  {
    $args = func_get_args();

    if (!$args)
      $args[0] = $this->select()->from($this->_name);

    if ($args[0] instanceof Zend_Db_Table_Select)
      $this->_ordonner($args[0]);

    return call_user_func_array(array('parent', 'fetchAll'), $args);
  }

  protected function _ordonner($select)
  {
    $select->distinct()
      ->join(array('strass_unite_ordre' => 'unite_type'),
	     'strass_unite_ordre.id = unite.type'."\n", array())
      ->order('strass_unite_ordre.ordre')
      ->order('strass_unite_ordre.id');
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
  protected $fermee = null;
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
      return $t->findBySlug($id);
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
    $tu = $this->findParentTypesUnite();
    if ($tu->age_max && $tu->age_max < 18 && $this->nom)
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

  function storeImage($path)
  {
    if ($fichier = $this->getImage())
      unlink($fichier);

    $fichier = $this->getImage(null, false);
    $dossier = dirname($fichier);
    if (!file_exists($dossier))
      mkdir($dossier, 0700, true);

    $config = Zend_Registry::get('config');

    $image = new Imagick;
    $image->setBackgroundColor(new ImagickPixel('transparent'));
    $image->readImage($path);
    $width = $image->getImageWidth();
    $height = $image->getImageHeight();

    $MAX = $config->get('photo/taille_vignette', 256);
    if (min($width, $height) > $MAX)
      $image->scaleImage($MAX, $MAX, true);

    $image->setImageFormat('png');
    $image->writeImage($fichier);
  }

  function getWiki($slug = null, $test = true)
  {
    $slug = $slug ? $slug : $this->slug;
    $image = 'private/unites/'.$slug.'.wiki';
    return !$test || is_readable($image) ? $image : null;
  }

  function storePresentation($wiki)
  {
    $path = $this->getWiki(null, false);
    if (!file_exists($d = dirname($path)))
      mkdir($d, 0700, true);

    file_put_contents($path, trim($wiki));
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
  public function findSousUnites($recursif = true, $annee = null) {
    $unites = array();
    $db = $this->getTable()->getAdapter();
    $select = $this->getTable()->select()
      ->setIntegrityCheck(false)
      ->from('unite')
      ->where('unite.parent = ?'."\n", $this->id);

    if (!$annee) {
      $select
	->join('unite_type', 'unite_type.id = unite.type', array())
	->joinLeft(array('actif' => 'appartenance'),
		   'actif.unite = unite.id'."\n".
		   ' OR '.
		   ("(unite_type.virtuelle".
		    " AND ".
		    " actif.unite = unite.parent)"),
		   array())
	->joinLeft(array('inactif' => 'appartenance'),
		   'inactif.unite = unite.id'."\n".
		   ' OR '.
		   ("(unite_type.virtuelle".
		    " AND ".
		    " inactif.unite = unite.parent)").
		   ' AND inactif.fin IS NOT NULL',
		   array());
      $date = ($annee+1).'-06-01';
      $select->where("(actif.debut < ? AND (actif.fin IS NULL OR ?<= actif.fin))".
		     " OR inactif.ID IS NULL", $date);
    }

    $su = $this->getTable()->fetchAll($select);
    foreach($su as $u) {
      $unites[] = $u;
      if ($recursif) {
	$sousunites = $u->findSousUnites($recursif, $annee);
	if ($sousunites) {
	  $unites = array_merge($unites, $sousunites);
	}
      }
    }

    return $unites;
  }

  /**
   * Retrouve les appartenances à l'unité en fonction de l'année en
   * tenant compte du type (ex: HP).
   */
  public function findAppartenances($annee = null, $recursive = false)
  {
    $db = $this->getTable()->getAdapter();

    $where = array();

    if ($annee === false)
      $where[]= 'fin IS NULL';
    elseif ($annee) {
      // Est considéré comme inscrit pour une année donnée un personne inscrite
      // avant le 24 août de l'année suivante …
      $where[]= $db->quoteInto('STRFTIME("%Y-%m-%d", debut) <= ?', ($annee+1).'-08-24');
      // … toujours en exercice ou en exercice au moins jusqu'au 1er janvier de l'année suivante.
      $where[]= $db->quoteInto('fin IS NULL OR STRFTIME("%Y-%m-%d", fin) >= ?', ($annee+1).'-01-01');
    }

    $t = new Appartenances;
    $select = $t->select()
      ->setIntegrityCheck(false)
      ->distinct()
      ->from('appartenance')
      ->join('unite_type', $db->quoteInto('unite_type.id = ?', $this->type), array())
      ->joinLeft(array('soeur' => 'unite'),
		 'unite_type.virtuelle AND '.
		 $db->quoteInto('soeur.parent = ?', $this->parent)."\n",
		 array());

    if ($recursive) {
      $in = $db->select()
	->from(array('filles' => 'unite'), 'id')
	->where("filles.parent = ?", $this->id)
	->orWhere("filles.id = ?", $this->id);
      $select->where('appartenance.unite IN (?)',
		     new Zend_Db_Expr($in->__toString()));
    }
    else {
      $select->where('(unite_type.virtuelle AND '.
		     ('(appartenance.unite = soeur.id OR '.
		      $db->quoteInto('appartenance.unite = ?', $this->parent).')').') OR '.
		     'appartenance.unite = ?', $this->id);
    }

    $select
      ->order('appartenance.unite')
      ->join('individu',
		  "individu.id = appartenance.individu\n",
		  array())
      ->order('naissance')
      ->join('unite_role',
	     'unite_role.id = appartenance.role',
	     array())
      ->order('unite_role.ordre');

    $where = array_filter($where);
    foreach($where as $clause)
      if ($clause)
	$select->where($clause);

    return $t->fetchAll($select);
  }

  function findChef()
  {
    $db = $this->getTable()->getAdapter();
    $t = new Individus;
    $select = $t->select()
      ->setIntegrityCheck(false)
      ->distinct()
      ->from('individu')
      ->join('appartenance', 'appartenance.individu = individu.id', array())
      ->join('unite', 'unite.id = appartenance.unite', array())
      ->join('unite_role', 'unite_role.id = appartenance.role', array())
      ->join('unite_type', 'unite_type.id = unite.type', array())
      ->where('appartenance.fin IS NULL')
      ->where("unite_role.acl_role = 'chef'")
      ->order('individu.naissance');

    if ($this->findParentTypesUnite()->virtuelle)
      $select->where('unite.id = ?', $this->parent);
    else
      $select->where('unite.id = ?', $this->id);

    return $t->fetchAll($select)->current();
  }

  function isFermee()
  {
    if (is_null($this->fermee)) {
      $t = new Appartenances;
      $db = $t->getAdapter();
      $s = $t->select()
	->distinct()
	->from('appartenance')
	->where('fin IS NULL')
	->where('unite = ?', $this->id);
      $actives = $t->countRows($s);
      $s = $t->select()
	->distinct()
	->from('appartenance')
	->where('fin IS NOT NULL')
	->where('unite = ?', $this->id);
      $inactives = $t->countRows($s);
      $this->fermee = $actives == 0 && $inactives > 0;
      if ($this->fermee && !$this->isTerminale())
	$this->fermee = count($this->findSousUnites(null, false)) == 0;
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
      $us = $this->findSousUnites(false, false);
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
    return $this->findParentTypesUnite()->isTerminale();
  }


  // retourne les années où l'unité fut ouverte.
  function getAnneesOuverte()
  {
    // sélectionner les années où l'unité à eut au moins un membre
    $db = $this->getTable()->getAdapter();
    $ti = new Individus;
    // DISTINCT ON dans SQLite est fait avec MIN() hors group by.
    $select = $ti->select()
      ->setIntegrityCheck(false)
      ->distinct()
      ->from('appartenance',
	     array('debut' => "strftime('%Y', debut)",
		   'fin' => "strftime('%Y', fin)",
		   'unite' => 'appartenance.unite'))
      ->join('unite_role', 'unite_role.id = appartenance.role',
	     array('role' => 'unite_role.acl_role',
		   'ordre' => 'MIN(unite_role.ordre)'))
      ->join('individu',
	     'individu.id = appartenance.individu')
      ->join('unite',
	     'unite.id = appartenance.unite',
	     array())
      ->group('debut')
      ->order('debut ASC');

    $virtuelle = $this->findParentTypesUnite()->virtuelle;
    if ($virtuelle) {
      $select->where("unite.id = ?", $this->parent);
    }
    else {
      $select->where('unite.id = ? OR unite.parent = ?', intval($this->id));
    }

    $is = $ti->fetchAll($select);
    $annees = array();
    $cette_annee = intval(strftime('%Y', time()-243*24*60*60));
    foreach($is as $individu) {
      /* pour le dernier chef en cours, inclure l'année courante *incluse* */
      $fin = $individu->fin ? $individu->fin : $cette_annee + 1;
      for($annee = $individu->debut; $annee < $fin; $annee++) {
	if (!array_key_exists($annee, $annees))
	  $annees[$annee] = null;

	if (is_object($annees[$annee])) {
	  continue;
	}

	if ($individu->unite == $this->id || ($virtuelle && $individu->unite == $this->parent)) {
	  if ($individu->role == 'chef')
	    $annees[$annee] = $individu;
	  else // on a des assistant, mais pas de chef
	    $annees[$annee] = '##INCONNU##';
	}
      }
    }
    ksort($annees);
    return $annees;
  }

  function findDocuments()
  {
    $uids = array($this->id, $this->parent);
    $gp = $this->findParentUnites();
    if ($gp)
      array_push($uids, $gp->parent);
    $uids = array_filter($uids);
    $uids = array_map('intval', $uids);

    $t = new Documents;
    $s = $t->select()
      ->setIntegrityCheck(false)
      ->distinct()
      ->from('document')
      ->join('unite_document', 'unite_document.document = document.id', array())
      ->where('unite_document.unite IN ?', $uids);

    return $t->fetchAll($s);
  }

  /* Liste les candidats à l'inscription dans l'unité pour une année données */
  function findCandidats($annee)
  {
    $t = new Individus;
    $db = $t->getAdapter();
    $s = $t->select()
      ->setIntegrityCheck(false)
      ->distinct()
      ->from('individu')
      ->join('unite_type', $db->quoteInto("unite_type.id = ?\n", intval($this->type)), array())
      ->joinLeft('appartenance',
		 $db->quoteInto('appartenance.individu = individu.id AND appartenance.unite = ?',
				$this->id).
		 ' AND '.
		 /* Inscription en cours, débutée au plus tard cette année */
		 '('.('appartenance.fin IS NULL AND '.
		      $db->quoteInto("appartenance.debut < ?", ($annee+1).'-08-01')
		      ).')', array())
      /* n'appartient pas déjà à l'unité */
      ->where('appartenance.id IS NULL')
      /* filtre sur le sexe */
      ->where("unite_type.sexe IN ('m', individu.sexe)\n")
      /* connaître l'âge est nécessaire ? ou ne pas contraindre sur l'âge ? */
      ->where("individu.naissance\n")
      /* filtre sur l'âge */
      ->where("unite_type.age_min <= ? - individu.naissance - 1\n", $annee.'-8-01')
      ->where("? - individu.naissance - 1 <= unite_type.age_max\n", $annee.'-8-01')
      ->order('individu.nom', 'individu.prenom');
    return $t->fetchAll($s);
  }

  function findRolesCandidats($unite, $annee)
  {
    $t = new Roles;
    $db = $t->getAdapter();
    $s = $t->select()
      ->setIntegrityCheck(false)
      ->from('unite_role')
      ->join('unite_type', 'unite_type.id = unite_role.type', array())
      ->join('unite', 'unite.type = unite_type.id', array())
      ->joinLeft('appartenance',
		 'appartenance.role = unite_role.id AND '.
		 'appartenance.unite = unite.id AND '.
		 'appartenance.fin IS NULL AND '.
		 $db->quoteInto('appartenance.debut < ?', ($annee+1).'-08-01'),
		 array())
      ->where('unite.id = ?', $unite->id)
      ->where('appartenance.id IS NULL');
    return $t->fetchAll($s);
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

class Roles extends Strass_Db_Table_Abstract
{
  protected $_name = 'unite_role';
  protected $_rowClass = 'Role';
  protected $_dependentTables = array('Appartenances');
  protected $_referenceMap = array('Type' => array('columns' => 'type',
						   'refTableClass' => 'TypesUnite',
						   'refColumns' => 'id',
						   'onUpdate' => self::CASCADE,
						   'onDelete' => self::CASCADE));
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

class Titres extends Strass_Db_Table_Abstract
{
  protected $_name = 'unite_titre';
  protected $_dependentTables = array();
  protected $_referenceMap = array('Role' => array('columns' => 'role',
						   'refTableClass' => 'Roles',
						   'refColumns' => 'id',
						   'onUpdate' => self::CASCADE,
						   'onDelete' => self::CASCADE));
}

class DocsUnite extends Strass_Db_Table_Abstract
{
  protected $_name = 'unite_document';
  protected $_referenceMap = array('Document' => array('columns' => 'document',
						       'refTableClass' => 'Documents',
						       'refColumns' => 'id',
						       'onUpdate' => self::CASCADE,
						       'onDelete' => self::CASCADE),
				   'Unite' => array('columns' => 'unite',
						    'refTableClass' => 'Unites',
						    'refColumns' => 'id',
						    'onUpdate' => self::CASCADE,
						    'onDelete' => self::CASCADE));
}
