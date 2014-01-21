<?php

require_once 'Strass/Activites.php';

class Photos extends Strass_Db_Table_Abstract
{
  protected	$_name			= 'photos';
  protected	$_rowClass		= 'Photo';
  protected	$_dependentTables	= array('Commentaires');
  protected	$_referenceMap		= array('Activite'	=> array('columns'		=> 'activite',
									 'refTableClass'	=> 'Activites',
									 'refColumns'		=> 'slug',
									 'onUpdate'		=> self::CASCADE,
									 'onDelete'		=> self::CASCADE));

  function findPhotoAleatoireUnite(Unite $unite)
  {
    $db = $this->getAdapter();
    // Une photos aléatoire d'une activité où l'unité à
    // participé et où les autres unités sont des
    // sous-unités
    $s = $this->select()
      ->setIntegrityCheck(false)
      ->from('photos')
      ->join('activite',
	     'activite.slug = photos.activite', array())
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
	->from('photos');

    $select->setIntegrityCheck(false)
      ->join('activite',
	     'activite.slug = photos.activite', array())
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
  protected $a = null;
  protected $_privileges	= array(array('chef',		NULL),
					array('assistant',	NULL),
					array(NULL,		'commenter'));

  function __construct(array $config)
  {
    parent::__construct($config);
    $this->initResourceAcl($this->findParentActivites()->findUnitesViaParticipations());
  }

  function findParentActivites()
  {
    if (is_null($this->a)) {
      $this->a = $this->findParentRow('Activites');
    }
    return $this->a;
  }

  function getResourceId()
  {
    return 'photo-'.$this->id;
  }

  protected function getChemin($data = null)
  {
    return $this->findParentActivites()->getDossierPhoto($data ? $data['activite'] : $this->activite).'/'.($data ? $data['id'] : $this->id);
  }

  function getCheminImage($data = null)
  {
    return $this->getChemin($data).'.jpeg';
  }

  function getCheminVignette($data = null)
  {
    return $this->getChemin($data).'-vignette.jpeg';
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
  protected	$_name			= 'commentaires';
  protected	$_rowClass		= 'Commentaire';
  protected	$_referenceMap		= array('Activite'	=> array('columns'		=> 'activite',
									 'refTableClass'	=> 'Activites',
									 'refColumns'		=> 'id',
									 'onUpdate'		=> self::CASCADE,
									 'onDelete'		=> self::CASCADE),
						'Photo'		=> array('columns'		=> array('photo', 'activite'),
									 'refTableClass'	=> 'Photos',
									 'refColumns'		=> array('id', 'activite'),
									 'onUpdate'		=> self::CASCADE,
									 'onDelete'		=> self::CASCADE),
						'Auteur'	=> array('columns'		=> 'individu',
									 'refTableClass'	=> 'Individus',
									 'refColumns'		=> 'slug',
									 'onUpdate'		=> self::CASCADE,
									 'onDelete'		=> self::CASCADE));
}

class Commentaire extends Strass_Db_Table_Row_Abstract implements Zend_Acl_Resource_Interface
{
  function __construct(array $config)
  {
    parent::__construct($config);
    $acl = Zend_Registry::get('acl');
    if (!$acl->has($this)) {
      $acl->add($this);
      $auteur = $this->findParentIndividus();
      if ($acl->hasRole($auteur))
	$acl->allow($auteur, $this, 'editer');
    }
  }

  function getResourceId()
  {
    return 'commentaire-'.$this->activite.'-'.$this->photo.'-'.$this->individu;
  }
}
