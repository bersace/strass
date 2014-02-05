<?php

require_once 'Strass/Activites.php';

class Photos extends Strass_Db_Table_Abstract
{
  protected $_name		= 'photo';
  protected $_rowClass		= 'Photo';
  protected $_referenceMap	= array('Activite' => array('columns'		=> 'activite',
							    'refTableClass'	=> 'Activites',
							    'refColumns'	=> 'id',
							    'onUpdate'		=> self::CASCADE,
							    'onDelete'		=> self::CASCADE),
					'Description' => array('columns' => 'commentaires',
							       'refTableClass' => 'Commentaires',
							       'refColumns' => 'id',
							       'onUpdate' => self::CASCADE,
							       'onDelete' => self::CASCADE));

  function findBySlugs($activite, $photo) {
    $s = $this->select()
      ->setIntegrityCheck(false)
      ->from('photo')
      ->join('activite', 'activite.id = photo.activite', array())
      ->where('activite.slug = ?', $activite)
      ->where('photo.slug = ?', $photo);

    return $this->fetchOne($s);
  }

  function findPhotoAleatoireUnite(Unite $unite)
  {
    $db = $this->getAdapter();
    // Une photos aléatoire d'une activité où l'unité à
    // participé et où les autres unités sont des
    // sous-unités
    $s = $this->select()
      ->setIntegrityCheck(false)
      ->from('photo')
      ->join('activite',
	     'activite.id = photo.activite', array())
      ->join('participation',
	     'participation.activite = activite.id'.
	     ' AND '.
	     $db->quoteInto('participation.unite = ?', intval($unite->id)),
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
      ->order('RANDOM()')
      ->limit(1);
    return $this->fetchAll($s)->current();
  }


  function fetchPhotosAleatoiresForUnite(Unite $unite, $select = null)
  {
    // Une photos aléatoire d'une activité où l'unité à
    // participé et où les autres unités sont des
    // sous-unités
    $db = $this->getAdapter();
    if (!$select)
      $select = $this->select()
	->from('photo');

    $select->setIntegrityCheck(false)
      ->join('activite',
	     'activite.id = photo.activite', array())
      ->join('participation',
	     'participation.activite = activite.id'.
	     ' AND '.
	     $db->quoteInto('participation.unite = ?', intval($unite->id))."\n",
	     array())
      ->order("RANDOM()\n");
    return $this->fetchAll($select);
  }
}

class Photo extends Strass_Db_Table_Row_Abstract implements Zend_Acl_Resource_Interface
{
  protected $_privileges	= array(array('chef',		NULL),
					array('assistant',	NULL),
					array(NULL,		'commenter'));

  function __construct(array $config)
  {
    parent::__construct($config);
    $this->initResourceAcl($this->findParentActivites()->findUnitesViaParticipations());
  }

  function getResourceId()
  {
    return 'photo-'.$this->slug;
  }

  protected function getChemin($data = null)
  {
    if (is_null($data)) {
      $a = $this->findParentActivites();
    }
    else {
      $ta = new Activites;
      $a = $ta->find($data['activite']);
    }
    $d = $a->getDossierPhoto();

    return $d.'/'.($data ? $data['slug'] : $this->slug);
  }

  function getCheminImage($data = null)
  {
    return $this->getChemin($data).'.jpeg';
  }

  function getCheminVignette($data = null)
  {
    return $this->getChemin($data).'-vignette.jpeg';
  }

  function findCommentaires() {
    $t = new Commentaires;
    $parent = $t->findOne($this->commentaires);
    return $parent->findCommentaires();
  }

  function __toString()
  {
    return wtk_ucfirst($this->titre);
  }

  function _postUpdate()
  {
    rename($this->getCheminImage($this->_cleanData), $this->getCheminImage());
    rename($this->getCheminVignette($this->_cleanData), $this->getCheminVignette());
  }

  function _postDelete()
  {
    unlink($this->getCheminVignette());
    unlink($this->getCheminImage());
  }
}


class Commentaires extends Strass_Db_Table_Abstract
{
  protected $_name		= 'commentaire';
  protected $_rowClass		= 'Commentaire';
  protected $_dependentTables	= array('Photos', 'Commentaires');
  protected $_referenceMap	= array('Parent'	=> array('columns'		=> 'parent',
								 'refTableClass'	=> 'Commentaires',
								 'refColumns'		=> 'id',
								 'onUpdate'		=> self::CASCADE,
								 'onDelete'		=> self::CASCADE),
					'Auteur'	=> array('columns'		=> 'auteur',
								 'refTableClass'	=> 'Individus',
								 'refColumns'		=> 'id',
								 'onUpdate'		=> self::CASCADE,
								 'onDelete'		=> self::CASCADE));
}

class Commentaire extends Strass_Db_Table_Row_Abstract implements Zend_Acl_Resource_Interface
{
  function initResourceAcl($acl) {
    if (!$acl->has($this)) {
      $acl->add($this);
      $auteur = $this->findParentIndividus();
      if ($acl->hasRole($auteur))
	$acl->allow($auteur, $this, 'editer');
    }
  }

  function getResourceId()
  {
    return 'commentaire-'.$this->photo.'-'.$this->individu;
  }
}
