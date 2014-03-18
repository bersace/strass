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
  function findRacines()
  {
    $s = $this->select()->where('unite.parent IS NULL');
    return $this->fetchAll($s);
  }

  function findRacine()
  {
    $s = $this->select()
      ->where('unite.parent IS NULL')
      ->order('unite.id')
      ->limit(1);
    $racines = $this->fetchAll($s);
    if (!$racines->count()) {
      throw new Strass_Db_Table_NotFound("Pas d'unité racine");
    }
    return $racines->current();
  }

  /* List les unités qui ne peuvent avoir de parent. */
  function findSuperUnites()
  {
    $s = $this->select()
      ->setIntegrityCheck(false)
      ->distinct()
      ->join('unite_type', 'unite_type.id = unite.id', array())
      ->joinLeft(array('soustype' => 'unite_type'), 'soustype.parent = unite_type.id', array())
      ->where('soustype.id IS NOT NULL');

    return $this->fetchAll($s);
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
}

class Unite extends Strass_Db_Table_Row_Abstract implements Zend_Acl_Resource_Interface
{
  protected $fermee = null;
  protected $_tableClass = 'Unites';
  protected $_privileges = array(array('chef',		NULL),
				 array('assistant',	array('prevoir-activite')),
				 array('membre',	array('consulter',
							      'calendrier',
							      'infos')));

  public function getResourceId()
  {
    return 'unite-'.$this->slug;
  }

  function initAclResource($acl)
  {
    $acl->add(new Zend_Acl_Resource($this->getResourceId()));
    $this->initPrivileges($acl, array($this));
    $acl->allow(null, $this, array('index', 'effectifs'));
  }

  public function getRoleId($role)
  {
    return $role.'-unite-'.$this->slug;
  }

  function initAclRoles($acl, $parent = null)
  {
    /*
      Les ACL sont un point crucial de Strass. Les rôles sont relatifs
      aux unités. On distingue trois classes de rôles : chef,
      assistant et membre.

      - Le chef et l'assistant d'une unité sont chef des sous-unités.

      - Les chefs, assistants et membre d'une unités sont membres de
      l'unité parente.

    */

    /* membres */
    $parents = array();
    if ($parent)
      array_push($parents, $parent->getRoleId('membre'));
    $acl->addRole(new Zend_Acl_Role($this->getRoleId('membre')), $parents);

    /* récursion des sous unités, qui initialisent leurs membres en
       héritant des membres de l'unité courante, et ses chefs dont
       les chefs de l'unité courant vont hériter */
    $sus = $this->findSousUnites(false, false);
    foreach ($sus as $su) {
      $su->initAclRoles($acl, $this);
    }

    /* assistant */
    $parents = array($this->getRoleId('membre'));
    foreach($sus as $u)
      array_push($parents, $u->getRoleId('chef'));
    // Considérer les assistants de cette unitée comme assistants des
    // unités virtuelles auxquels ils appartiennent. Ex: les CP et SP
    // sont assistant de la HP.
    $soeurs = $this->findSoeursVirtuelles();
    foreach ($soeurs as $u)
      array_push($parents, $u->getRoleId('assistant'));
    $acl->addRole(new Zend_Acl_Role($this->getRoleId('assistant')), $parents);

    /* chef */
    $parents = array($this->getRoleId('assistant'));
    $acl->addRole(new Zend_Acl_Role($this->getRoleId('chef')), $parents);
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
    else if (!$this->nom)
      return $this->getFullName();
    else
      return $tu->nom . ' ' . $this->nom;
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
  public function findSousUnites($annee = null, $recursif = true)
  {
    $c = Zend_Registry::get('cache');
    $id = str_replace('-', '_', trim('sous-unites-'.$this->slug.'-'.$annee.'-'.$recursif, '-'));
    if (($su = $c->load($id)) === false) {
      $t = $this->getTable();
      $db = $t->getAdapter();
      $select = $this->getTable()->select()
	->setIntegrityCheck(false)
	->from('unite')
	->join('unite_type', 'unite_type.id = unite.type', array())
	->order('unite_type.virtuelle DESC');

      if ($recursif) {
	$select
	  ->joinLeft(array('fille' => 'unite'), $db->quoteInto('fille.parent = ?', $this->id), array())
	  ->where('unite.parent IN (?, fille.id)'."\n", $this->id);
      }
      else {
	$select
	  ->where('unite.parent = ?'."\n", $this->id);
      }

      if ($annee) {
	/* unités ouvertes */
	$select
	  ->joinLeft(array('petitefille' => 'unite'), 'petitefille.parent = unite.id', array())
	  ->joinLeft(array('actif' => 'appartenance'),
		     'actif.unite IN (unite.id, petitefille.id)'."\n".
		     ' OR '.
		     ("(unite_type.virtuelle".
		      " AND ".
		    " actif.unite = unite.parent)"),
		     array())
	  ->joinLeft(array('inactif' => 'appartenance'),
		     'inactif.unite IN (unite.id, petitefille.id)'."\n".
		     ' OR '.
		     ("(unite_type.virtuelle".
		      " AND ".
		    " inactif.unite = unite.parent)").
		     ' AND inactif.fin IS NOT NULL',
		     array());
	if ($annee === true)
	  $select->where('actif.fin IS NULL OR inactif.id IS NULL');
	else {
	  $date = ($annee+1).'-06-01';
	  $select->where("(actif.debut < ? AND (actif.fin IS NULL OR ?<= actif.fin))".
			 " OR inactif.id IS NULL", $date);
	}
      }
      $su = $t->fetchAll($select);
      $tags = array('sous_unites');
      $c->save($su, $id, $tags);
    }

    return $su;
  }

  function findSoeursVirtuelles()
  {
    $t = $this->getTable();
    $select = $t->select()
      ->setIntegrityCheck(false)
      ->from('unite')
      ->where("unite.parent = ?", $this->parent)
      ->join('unite_type', 'unite_type.id = unite.type', array())
      ->where("unite_type.virtuelle")
      ->where('unite.id != ?', $this->id);
    return $t->fetchAll($select);
  }

  function findFermees()
  {
    $t = $this->getTable();
    $db = $t->getAdapter();
    $s = $t->select()
      ->setIntegrityCheck(false)
      ->distinct()
      ->from('unite')
      ->joinLeft(array('actif' => 'appartenance'),
		 'actif.unite = unite.id AND actif.fin IS NULL', array())
      ->where('actif.id IS NULL')
      ->joinLeft(array('inactif' => 'appartenance'),
		 'inactif.unite = unite.id AND inactif.fin IS NOT NULL', array())
      ->where('inactif.id')
      ->where('unite.parent = ?', $this->id);

    return $t->fetchAll($s);
  }

  /**
   * Retrouve les appartenances à l'unité en fonction de l'année en
   * tenant compte du type (ex: HP).
   *
   * $annee = null: actifs et anciens
   * $annee = <nombre> : actifs durant l'année <nombre>
   * $annee = false : uniquement les actifs.
   */
  public function findAppartenances($annee = null, $recursion = 0)
  {
    $db = $this->getTable()->getAdapter();

    $t = new Appartenances;
    $select = $t->select()
      ->setIntegrityCheck(false)
      ->distinct()
      ->from('appartenance')
      ->join('unite_type', $db->quoteInto('unite_type.id = ?', $this->type), array());

    $virtuelle = $this->findParentTypesUnite()->virtuelle;
    if ($virtuelle) {
      $select
	->joinLeft(array('soeur' => 'unite'),
		   $db->quoteInto('soeur.parent = ?', $this->parent ? $this->parent : null),
		   array())
	->where('(unite_type.virtuelle AND '."\n".
		('(appartenance.unite = soeur.id OR '.
		 $db->quoteInto('appartenance.unite = ?', $this->parent ? $this->parent : null).')').
		"AND unite_role.acl_role IN ('chef', 'assistant')) OR ".
		'appartenance.unite = ?', $this->id);
    }
    else if ($recursion) {
      $select->joinLeft(array('fille' => 'unite'), $db->quoteInto('fille.parent = ?', $this->id), array());
      if ($recursion == 1)
	$select->where('appartenance.unite IN (?, fille.id)'."\n", $this->id);
      else if ($recursion >= 2)
	$select
	  ->joinLeft(array('petitefille' => 'unite'), 'petitefille.parent = fille.id', array())
	  ->where('appartenance.unite IN (?, fille.id, petitefille.id)'."\n", $this->id);
    }
    else {
      $select->where('appartenance.unite = ?'."\n", $this->id);
    }

    $select
      ->join('individu', "individu.id = appartenance.individu", array())
      ->join('unite_role', 'unite_role.id = appartenance.role', array())
      ->join(array('app_unite' => 'unite'), 'app_unite.id = appartenance.unite', array())
      ->join(array('app_type' => 'unite_type'), 'app_type.id = app_unite.type'."\n", array())
      ->order('app_type.ordre')
      ->order('appartenance.unite')
      ->order('unite_role.ordre')
      ->order('appartenance.titre')
      ->order('naissance');

    if ($annee === false)
      $select->where('fin IS NULL');
    elseif ($annee) {
      // Est considéré comme inscrit pour une année donnée un personne inscrite
      // avant le 24 août de l'année suivante …
      $select->where('STRFTIME("%Y-%m-%d", debut) <= ?'."\n", ($annee+1).'-08-24');
      // … toujours en exercice ou en exercice au moins jusqu'au 1er janvier de l'année suivante.
      $select->where('fin IS NULL OR STRFTIME("%Y-%m-%d", fin) >= ?'."\n", ($annee+1).'-01-01');
    }

    return $t->fetchAll($select);
  }

  function findChef($annee = false)
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
      ->where("unite_role.acl_role = 'chef'")
      ->order('appartenance.debut DESC');

    if ($this->findParentTypesUnite()->virtuelle)
      $select->where('unite.id = ?', $this->parent);
    else
      $select->where('unite.id = ?', $this->id);

    if ($annee === false)
      $select->where('appartenance.fin IS NULL');
    else if ($annee)
      $select->where("STRFTIME('%Y', appartenance.fin, '-6 months'),  >= ?", $annee);

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
	->joinLeft(array('fille' => 'unite'), $db->quoteInto('fille.parent = ?', $this->id), array())
	->joinLeft(array('petitefille' => 'unite'), 'petitefille.parent = fille.id', array())
	->where('fin IS NULL')
	->where('appartenance.unite IN (?, fille.id, petitefille.id)', $this->id);
      $actives = $t->countRows($s);
      $s = $t->select()
	->distinct()
	->from('appartenance')
	->joinLeft(array('fille' => 'unite'), $db->quoteInto('fille.parent = ?', $this->id), array())
	->joinLeft(array('petitefille' => 'unite'), 'petitefille.parent = fille.id', array())
	->where('fin IS NOT NULL')
	->where('appartenance.unite IN (?, fille.id, petitefille.id)', $this->id);
      $inactives = $t->countRows($s);
      $this->fermee = $actives == 0 && $inactives > 0;
    }
    return $this->fermee;
  }

  function fermer($fin, $recursif = true) {

    $t = new Appartenances;
    $s = $t->select()
      ->from('appartenance')
      ->where('fin IS NULL')
      ->where('appartenance.unite = ?', $this->id);
    $apps = $t->fetchAll($s);
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

  function findAlbums($annee)
  {
    $t = new Activites;
    $db = $t->getAdapter();
    $s = $this->select()
      ->setIntegrityCheck(false)
      ->distinct()
      ->from('activite')
      ->join('participation', 'participation.activite = activite.id', array())
      ->joinLeft(array('fille' => 'unite'), $db->quoteInto('fille.parent = ?', $this->id), array())
      ->joinLeft(array('petitefille' => 'unite'), 'petitefille.parent = fille.id', array())
      ->join('photo', 'photo.activite = activite.id', array())
      ->where('participation.unite IN (?, fille.id, petitefille.id)', $this->id)
      ->where("? < activite.debut", $annee.'-08-31')
      ->where("activite.debut < ?", ($annee+1).'-08-31')
      ->where("activite.debut < STRFTIME('%Y-%m-%d %H:%M', CURRENT_TIMESTAMP)")
      ->order('fin');

    return $t->fetchAll($s);
  }

  function findPhotoAleatoire($annee = NULL)
  {
    // Une photos aléatoire d'une activité où l'unité à participé et
    // où les autres unités sont des sous-unités. Ex: une photo d'un
    // WET et pas de la rentrée de groupe.
    $t = new Photos;
    $db = $t->getAdapter();
    $s = $t->select()
      ->setIntegrityCheck(false)
      ->from('photo', array('photo.*', 'year' => "STRFTIME('%Y', photo.date)"))
      ->join('activite',
	     'activite.id = photo.activite', array())
      ->join('participation',
	     'participation.activite = activite.id'.
	     ' AND '.
	     $db->quoteInto('participation.unite = ?', intval($this->id)),
	     array())
      ->join('unite',
	     'unite.id = participation.unite',
	     array())
      ->joinLeft(array('parent_participation' => 'participation'),
		 "parent_participation.activite = activite.id\n".
		 ' AND '.
		 "parent_participation.unite = unite.parent\n",
		 array())
      ->where('parent_participation.unite IS NULL')
      ->order('year DESC')
      ->order('RANDOM()')
      ->limit(1);

    if ($annee)
      $s->where("CAST(STRFTIME('%Y', photo.date) AS INTEGER) <= ?", $annee);

    return $t->fetchAll($s)->current();
  }

  function findPhotosAleatoires($annee=null)
  {
    // Une photos aléatoire d'une activité où l'unité à participé

    $t = new Photos;
    $db = $t->getAdapter();
    $select = $t->select()
      ->setIntegrityCheck(false)
      ->distinct()
      ->from('photo')
      ->join('activite',
	     'activite.id = photo.activite', array())
      ->joinLeft(array('fille' => 'unite'),
		 $db->quoteInto('fille.parent = ?', $this->id),
		 array())
      ->joinLeft(array('petitefille' => 'unite'), 'petitefille.parent = fille.id', array())
      ->join('participation',
	     'participation.activite = activite.id'.
	     ' AND '.
	     $db->quoteInto('participation.unite IN (?, fille.id, petitefille.id)', intval($this->id))."\n",
	     array())
      ->limit(6) // paramétrable ?
      ->order('participation.unite') // Les unités parentes en priorité
      ->order("RANDOM()\n");

    if ($annee)
      $select->where("strftime('%Y', activite.debut, '-8 months') = ?", strval($annee));

    return $t->fetchAll($select);
  }

  function findActivitesMarquantes($annee = null, $count = 4)
  {
    $t = new Activites;
    $select = $t->select()
      ->setIntegrityCheck(false)
      ->distinct()
      ->from('activite')
      ->join('participation', 'participation.activite = activite.id', array())
      ->where('participation.unite = ?', $this->id)
      ->join('photo', 'photo.activite = participation.activite', array('photos' => 'COUNT(photo.id)'))
      ->group('activite.id')
      ->having('photos > 4')
      ->order('photos', 'activite.debut')
      ->limit($count);

    if ($annee)
      $select->where("strftime('%Y', activite.debut, '-8 months') = ?", strval($annee));

    return $t->fetchAll($select);
  }

  function getDerniereAnnee()
  {
    $t = new Appartenances;
    $db = $t->getAdapter();
    $s = $db->select()
      ->distinct()
      ->from('appartenance', array("MAX(STRFTIME('%Y', appartenance.fin))"))
      ->where('appartenance.unite = ?', $this->id);
    return $s->query()->fetchColumn();
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
    $t = new Individus;
    // DISTINCT ON dans SQLite est fait avec MIN() hors group by.
    $select = $t->select()
      ->setIntegrityCheck(false)
      ->from('appartenance',
	     array('debut' => "strftime('%Y', debut)",
		   'fin' => "strftime('%Y', fin)",
		   'unite' => 'appartenance.unite'))
      ->join('unite_role', 'unite_role.id = appartenance.role',
	     array('role' => 'unite_role.acl_role',
		   'ordre' => 'MIN(unite_role.ordre)'))
      ->join('individu',
	     'individu.id = appartenance.individu',
	     array('individu.*', 'homonymes' => 'COUNT(individu.prenom)'))
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

    $is = $t->fetchAll($select);
    $annees = array();
    $cette_annee = intval(strftime('%Y', time()-243*24*60*60));
    $homonymes = array();
    foreach($is as $individu) {
      /* pour le dernier chef en cours, inclure l'année courante *incluse* */
      $fin = $individu->fin ? $individu->fin : $cette_annee + 1;
      for($annee = $individu->debut; $annee < $fin; $annee++) {
	if (!array_key_exists($annee, $annees))
	  $annees[$annee] = null;

	if (is_object($chef = $annees[$annee]))
	  continue;

	if ($individu->unite == $this->id || ($virtuelle && $individu->unite == $this->parent)) {
	  if ($individu->role == 'chef') {
	    $annees[$annee] = $individu;
	    /* Récolte des homonymes */
	    if (!array_key_exists($individu->prenom, $homonymes))
	      $homonymes[$individu->prenom] = array($individu->slug);
	    else
	      array_push($homonymes[$individu->prenom], $individu->slug);
	  }
	  else // on a des assistant, mais pas de chef
	    $annees[$annee] = '##INCONNU##';
	}
      }
    }

    foreach($annees as $chef)
      if (is_object($chef))
	$chef->homonymes = count(array_unique($homonymes[$chef->prenom]));

    ksort($annees);
    return $annees;
  }

  protected $_type;

  function findParentTypesUnite()
  {
    /* Économise une cinquantaine de requête sur la page d'acceuil de SAQV */
    if (!$this->_type)
      $this->_type = parent::findParentTypesUnite();

    return $this->_type;
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
      ->where('unite_document.unite IN ?', $uids)
      ->order('date DESC');

    return $t->fetchAll($s);
  }

  function findParenteCandidates()
  {
    $t = $this->getTable();
    $s = $t->select()
      ->setIntegrityCheck(false)
      ->distinct()
      ->from('unite')
      ->where('unite.type = ?', $this->findParentTypesUnite()->parent);
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

  function findRolesCandidats($annee)
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
		 ('('.
		  $db->quoteInto('(appartenance.fin IS NULL OR appartenance.fin > ?)', ($annee+1).'-08-01').
		  ' AND '.
		  $db->quoteInto('appartenance.debut < ?', ($annee).'-08-01').
		  ')'),
		 array())
      ->where('unite.id = ?', $this->id)
      ->where('appartenance.id IS NULL');
    return $t->fetchAll($s);
  }

  function clearCacheSousUnites()
  {
    $cache = Zend_Registry::get('cache');

    $tags = array('sous_unites');
    foreach($cache->getIdsMatchingTags($tags) as $id) {
      $cache->remove($id);
    }
  }

  function _postInsert()
  {
    $this->clearCacheSousUnites();

    Zend_Registry::get('cache')->remove('strass_acl');
    /* Est-ce que ça vaut la peine de réinitialiser les ACL ? Vu qu'on
       va certainement faire un redirect juste après l'insert… */

  }

  function _postDelete()
  {
    $this->clearCacheSousUnites();

    Zend_Registry::get('cache')->remove('strass_acl');

    if ($i = $this->getImage())
      unlink($i);

    if ($w = $this->getWiki())
      unlink($w);
  }

  function _postUpdate()
  {
    $this->clearCacheSousUnites();
    Zend_Registry::get('cache')->remove('strass_acl');

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
    $select = $this->getAdapter()->select()->where('parent IS NULL');
    return $this->fetchAll($select);
  }
}

class TypeUnite extends Strass_Db_Table_Row_Abstract
{
  protected $_tableClass = 'TypesUnite';

  static function cleanIntitule($intitule)
  {
    return trim(preg_replace('/ +/', ' ', $intitule));
  }

  function getIntituleCourtActivite($activite)
  {
    if ($activite->intitule)
      $i = $activite->intitule;
    else {
      $type = $activite->getType();
      $i = $this->{'accr_'.$type}.' '.$activite->lieu;
    }

    return self::cleanIntitule($i);
  }

  function getIntituleActivite($activite)
  {
    if ($activite->intitule)
      $i = $activite->intitule;
    else {
      $type = $activite->getType();
      $i = $this->{'nom_'.$type};
      if ($type == 'camp') {
	$mois = substr($activite->debut, 5, 2);
	switch($mois) {
	case '01':
	case '02':
	  $i.= " d'hiver";
	  break;
	case '04':
	case '05':
	  $i.= ' de Pâques';
	  break;
	case '07':
	case '08':
	  $i.= " d'été";
	  break;
	case '10':
	case '11':
	  $i.= " de Toussaint";
	  break;
	case '12':
	  $i.= " de Noël";
	  break;
	}
      }
      $i.= ' '.$activite->lieu;
    }

    return self::cleanIntitule($i);
  }

  function getIntituleCompletActivite($activite)
  {
    /* Pas en base car ça ne dépend pas du type d'activité */
    static $datefmts = array('reunion' => '%e %b %Y',
			    'sortie' => '%b %Y',
			    'we' => '%b %Y',
			    'camp' => '%Y',
			    );
    if ($activite->intitule) {
      $i = $activite->intitule;
      /* On considère les intitulés explicites comme annuels, même si
	 ce sont des sorties ou des we. Par exemple : Rentrée, JN,
	 RNR, etc. */
      $datefmt = '%Y';
    }
    else {
      $type = $activite->getType();
      $i = $this->{'nom_'.$type};
      $datefmt = $datefmts[$type];
      if ($type == 'camp') {
	$mois = substr($activite->debut, 5, 2);
	switch($mois) {
	case '03':
	case '04':
	  $i.= ' de Pâques';
	  break;
	}
      }
      $i.= ' '.$activite->lieu;
    }
    $i .= ' '.strftime($datefmt, strtotime($activite->debut));

    return self::cleanIntitule($i);
  }

  function __toString()
  {
    return $this->nom;
  }

  function isTerminale()
  {
    return $this->findTypesUnite()->count() == 0;
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
  protected $_rowClass = 'DocUnite';
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

class DocUnite extends Strass_Db_Table_Row_Abstract
{
  protected $_tableClass = 'DocsUnite';
}