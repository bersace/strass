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
}


class Activite extends Strass_Db_Table_Row_Abstract implements Zend_Acl_Resource_Interface
{
  protected $_tableClass = 'Activites';
  protected $_privileges = array(array('chef', NULL),
				 array('assistant', array('editer',
							  'envoyer-photo',
							  'dossier')),
				 array(NULL, 'consulter'));

  function __toString()
  {
    return $this->getIntituleComplet();
  }

  public function getResourceId()
  {
    return 'activite-'.$this->slug;
  }

  function initAclResource($acl)
  {
    $acl->add(new Zend_Acl_Resource($this->getResourceId()));
    $this->initPrivileges($acl, $this->findUnitesParticipantesExplicites());

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

  public function getType()
  {
    $dt = strtotime($this->debut);
    $ft = strtotime($this->fin);
    $longueur = $ft - $dt;

    /* On considère qu'une sortie dure moins de 12 heures */
    if ($longueur < 12 * 3600) {
      if (date('H', $dt) >= 16 && date('H', $ft) >= 20) {
	$type = 'reunion';
      }
      else {
	$type = 'sortie';
      }
    }
    /* on considère un camp à partir de deux nuits, 36 heures */
    else if ($longueur < 36 * 3600) {
      $type = 'we';
    }
    else {
      $type = 'camp';
    }
    return $type;
  }

  function getIntitule()
  {
    return $this->findTypeUnite()->getIntituleActivite($this);
  }

  function getIntituleCourt()
  {
    return $this->findTypeUnite()->getIntituleCourtActivite($this);
  }

  function getIntituleComplet()
  {
    return $this->findTypeUnite()->getIntituleCompletActivite($this);
  }

  function getDate()
  {
    $dt = strtotime($this->debut);
    $ft = strtotime($this->fin);
    $monomois = substr($this->debut, 5, 2) == substr($this->fin, 5, 2);

    switch ($this->getType()) {
    case 'reunion':
    case 'sortie':
      return strftime('%e %B', $dt);
    case 'we':
      if ($monomois)
	return strftime('%e', $dt).' - '.strftime('%e %B', $ft);
      else
	return strftime('%e %B', $dt).' - '.strftime('%e %B', $ft);
    case 'camp':
      if ($monomois)
	return strftime('du %e au ', $dt).strftime('%e %B', $ft);
      else
	return strftime('du %e %B au ', $dt).strftime('%e %B', $ft);
    }
  }

  public function getAnnee()
  {
    return strftime('%Y', strtotime($this->debut) - 8 * 30 * 24 * 3600);
  }

  function isFuture()
  {
    return strtotime($this->debut) > time();
  }

  function findTypeUnite()
  {
    $t = new TypesUnite;
    $s = $t->select()
      ->setIntegrityCheck(false)
      ->distinct()
      ->from('unite_type')
      ->join('unite', 'unite.type = unite_type.id', array())
      ->join('participation', 'participation.unite = unite.id', array())
      ->where('participation.activite = ?', $this->id)
      ->order('unite_type.ordre')
      ->limit(1);

    return $t->fetchAll($s)->current();
  }

  function getDossierPhoto($slug = NULL)
  {
    return Strass_Version::getRoot().'data/photos/'.($slug ? $slug : $this->slug);
  }

  function getPhotoAleatoire()
  {
    return $this->findPhotosAleatoires(1)->current();
  }

  function findPhotosAleatoires($count=6)
  {
    $select = $this->getTable()->select()
      ->order('RANDOM()')
      ->limit($count);
    return $this->findPhotos($select);
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

    // desctruction des documents liés *uniquement* à cette activité.
    $pjs = $this->findPiecesJointes();
    foreach($pjs as $pj) {
      $doc = $pj->findParentDocuments();
      if ($doc->countLiaisons() == 1)
	$doc->delete();
    }
  }

  function findUnitesParticipantes()
  {
    $t = new Unites;
    $s = $t->select()
      ->setIntegrityCheck(false)
      ->distinct()
      ->from('unite')
      ->join('participation', 'participation.unite = unite.id', array())
      ->where('participation.activite = ?', $this->id);
    return $t->fetchAll($s);
  }

  function findUnitesParticipantesExplicites()
  {
    $t = new Unites;
    $s = $t->select()
      ->setIntegrityCheck(false)
      ->distinct()
      ->from('unite')
      ->joinLeft('participation', 'participation.unite = unite.id', array())
      ->joinLeft(array('mere' => 'unite'), 'mere.id = unite.parent', array())
      ->joinLeft(array('part_mere' => 'participation'),
		 'part_mere.unite = mere.id AND part_mere.activite = participation.activite', array())
      ->where('participation.activite = ?', $this->id)
      ->where('part_mere.id IS NULL');
    return $t->fetchAll($s);
  }

  function updateUnites($participantes)
  {
    $tu = new Unites;
    $annee = $this->getAnnee();

    // d'abord nettoyer l'ancien.
    foreach ($this->findParticipations() as $p)
      $p->delete();

    // Reconstruire la liste des participants
    foreach ($participantes as $parente) {
      $p = new Participation;
      $p->activite = $this->id;
      $p->unite = $parente->id;
      $p->save();

      foreach ($parente->findSousUnites($annee, true) as $unite) {
	$p = new Participation;
	$p->activite = $this->id;
	$p->unite = $unite->id;
	$p->save();
      }
    }
  }
}

class Participations extends Strass_Db_Table_Abstract
{
  protected $_name = 'participation';
  protected $_rowClass = 'Participation';
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
}

class Participation extends Strass_Db_Table_Row_Abstract
{
  protected $_tableClass = 'Participations';
}

class PiecesJointes extends Strass_Db_Table_Abstract
{
  protected $_name = 'activite_document';
  protected $_rowClass = 'PieceJointe';
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

class PieceJointe extends Strass_Db_Table_Row_Abstract
{
  protected $_tableClass = 'PiecesJointes';

  function _postDelete()
  {
    /* Récursion sur les pièces jointes exclusives */
    $d = $this->findParentDocuments();
    if ($d->countLiaisons() == 0)
      $d->delete();
  }
}
