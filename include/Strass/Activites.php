<?php

require_once 'Strass/Unites.php';
require_once 'Strass/Photos.php';
require_once 'Strass/Documents.php';

class Activites extends Strass_Db_Table_Abstract
{
  protected $_name = 'activite';
  protected $_rowClass = 'Activite';
  protected $_dependentTables = array('Participations', 'Photos', 'PiecesJointes',
				      'Commentaires');

  function findActivites($futures = TRUE)
  {
    // +2 => heures été/hivers. :/
    return $this->fetchAll("debut ".($futures ? '>' : '<').
			   " STRFTIME('%Y-%m-%d %H:%M', CURRENT_TIMESTAMP, '+2 HOURS')");
  }

  function findAlbums($annee)
  {
    $db = $this->getAdapter();
    $select = $this->select()
      ->setIntegrityCheck(false)
      ->distinct()
      ->from('activite')
      ->join('photo', 'photo.activite = activite.id', array())
      ->where("? < activite.debut", $annee.'-08-31')
      ->where("activite.debut < ?", ($annee+1).'-08-31')
      ->where("activite.debut < STRFTIME('%Y-%m-%d %H:%M', CURRENT_TIMESTAMP)")
      ->order('fin');
    return $this->fetchAll($select);
  }

  // Retourne les clefs primaires des unités qui ne sont pas
  // implicetement participante d'une activité via une unité parente.
  static function findUnitesParticipantesExplicites($participantes)
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

  static protected $types = array('diner' => 'Dîner',
				  'sortie' => 'Sortie',
				  'chasse' => 'Chasse',
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
				 array('assistant', array('editer',
							  'envoyer-photo',
							  'dossier')),
				 array(NULL, 'consulter'));
  protected $type;
  protected $annee;

  public function __construct(array $config = array())
  {
    parent::__construct($config);

    $this->initResourceAcl($this->findUnitesParticipantesExplicites());

    $this->type = self::findType($this->debut, $this->fin);
    $this->annee = intval(date('Y', strtotime($this->debut) - 243 * 24 * 60 * 60));
  }

  protected function _initResourceAcl(&$acl)
  {
    // permettre à tous de voir les détails des activités passées.
    if (!$this->isFuture())
      $acl->allow(null, $this, 'consulter');

    // permettre à toute les maîtrise d'envoyer des photos.
    $us = $this->findUnitesParticipantes();
    $chefs = array();
    foreach($us as $u) {
      $chefs[] = $u->getRoleId('chef');
      $chefs[] = $u->getRoleId('assistant');
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
    // Si l'intitulé est défini, alors ne pas le regénérer.
    if ($this->intitule) {
      $intitule = $this->intitule;
    }
    else {
      $intitule = self::generateIntitule($this->_data,
					 $this->findUnitesParticipantes(),
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
    return self::generateIntitule($this->toArray(),
				  $this->findUnitesParticipantes());
  }


  public static function generateIntitule(array $data, $unites,
					  $date = true, $location = true, $compact = false)
  {
    extract($data);
    $dt = strtotime($debut);
    $ft = strtotime($fin);

    $type = self::findType($debut, $fin);

    $tu = new Unites;
    $eids = Activites::findUnitesParticipantesExplicites($unites);

    $explicites = count($eids) > 1 ? $tu->findMany($eids) : $tu->findOne($eids[0]);

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
      $typu = $unite->findParentTypesUnite()->slug;

      if ($compact && $type == 'we') {
	switch ($typu) {
	case 'groupe':
	  $i.= "G";
	  break;
	case 'clan':
	  $i.= 'C';
	  break;
	case 'feu':
	  $i.= 'F';
	  break;
	case 'aines':
	  $i.= 'CA';
	  break;
	case 'troupe':
	  $i.= "T ".$unite->nom;
	  break;
	case 'hp':
	  $i.= 'HP';
	  break;
	case 'patrouille':
	  $i.= "P ".$unite->getName();
	  break;
	case 'compagnie':
	  $i.= 'Cie';
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
	  if ($typu == 'hp') {
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
	  switch ($typu) {
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

  function getDossierPhoto($slug = NULL)
  {
    return 'data/photos/'.($slug ? $slug : $this->slug);
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
      ->where($db->quoteInto('photos.activite = ?', $this->slug));
    $stmt = $db->query($select);
    return count($stmt->fetchAll());
  }

  protected function _postUpdate()
  {
    @rename($this->getDossierPhoto($this->_cleanData['slug']),
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

  function findUnitesParticipantes()
  {
    // mettre à jour les participations
    $rows = $this->findUnitesViaParticipations();

    // sélectionner *toutes* les sous-unités.
    $participantes = array();
    foreach($rows as $unite) {
      $participantes[] = $unite->id;
      $sus = $unite->findSousUnites(true, $this->getAnnee());
      foreach($sus as $su) {
	$participantes[] = $su->id;
      }
    }
    $participantes = array_unique($participantes);

    $tu = new Unites();
    return $tu->findMany($participantes);
  }

  function findUnitesParticipantesExplicites()
  {
    $tu = new Unites();
    $ids = Activites::findUnitesParticipantesExplicites($this->findUnitesParticipantes());
    return $tu->findMany($ids);
  }
}

class Participations extends Strass_Db_Table_Abstract
{
  protected $_name = 'participation';
  protected $_referenceMap = array('Unite' => array('columns' => 'unite',
						    'refTableClass' => 'Unites',
						    'refColumns' => 'id',
						    'onUpdate' => self::CASCADE,
						    'onDelete' => self::CASCADE),
				   'Activite' => array('columns' => 'activite',
						       'refTableClass' => 'Activites',
						       'refColumns' => 'id',
						       'onUpdate' => self::CASCADE,
						       'onDelete' => self::CASCADE));


  function updateActivite($activite, $participantes)
  {
    $tu = new Unites();

    // boucler sur *toutes* les unités existantes pour ajout ou
    // suppression de la participation.
    $rows = $tu->fetchAll();
    foreach($rows as $unite) {
      $s = $this->select()
	->setIntegrityCheck(false)
	->where('activite = ?', $activite->id)
	->where('unite = ?', $unite->id);

      if (in_array($unite->id, $participantes)) {
	// ajouter un unités nouvellement participante
	try {
	  $p = $this->fetchOne($s);
	} catch (Strass_Db_Table_NotFound $e) {
	  $id = $this->insert(array ('activite' => $activite->id,
				     'unite' => $unite->id));
	}
      }
      // supprimer une unité anciennement participante
      else {
	try {
	  $p = $this->fetchOne($s);
	  $p->delete();
	} catch (Strass_Db_Table_NotFound $e) {}
      }
    }
  }

}

class PiecesJointes extends Zend_Db_Table_Abstract
{
	protected $_name = 'activite_document';
	protected $_referenceMap = array('Document' => array('columns' => 'document',
							     'refTableClass' => 'Documents',
							     'refColumns' => 'id',
							     'onUpdate' => self::CASCADE,
							     'onDelete'  => self::CASCADE),
					 'Activite' => array('columns' => 'activite',
							     'refTableClass' => 'Activites',
							     'refColumns' => 'id',
							     'onUpdate' => self::CASCADE,
							     'onDelete' => self::CASCADE));
}
