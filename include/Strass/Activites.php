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
