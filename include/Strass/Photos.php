<?php

require_once 'Strass/Activites.php';

class Photos extends Strass_Db_Table_Abstract
{
	protected	$_name			= 'photos';
	protected	$_rowClass		= 'Photo';
	protected	$_dependentTables	= array('Commentaires');
	protected	$_referenceMap		= array('Activite'	=> array('columns'		=> 'activite',
										 'refTableClass'	=> 'Activites',
										 'refColumns'		=> 'id',
										 'onUpdate'		=> self::CASCADE,
										 'onDelete'		=> self::CASCADE));

	function findPhotoAleatoireUnite(Unite $unite)
	{
		$db = $this->getAdapter();
		// Une photos aléatoire d'une activité où l'unité à
		// participé et où les autres unités sont des
		// sous-unités
		$s = $db->select()
			->from('photos')
			->join('participe',
			       'participe.activite = photos.activite'.
			       ' AND '.
			       $db->quoteInto('participe.unite = ?', $unite->id),
			       array())
			->join('unites',
			       'unites.id = participe.unite',
			       array())
			->joinLeft(array('parent_participe' => 'participe'),
				   'parent_participe.activite = photos.activite'.
				   ' AND '.
				   'parent_participe.unite = unites.parent',
				   array())
			->where('parent_participe.unite IS NULL')
			->order('RANDOM()')
			->limit(1);
		return $this->fetchAll($s)->current();
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
			$acl->allow($this->findParentIndividus(), $this, 'editer');
		}
	}

	function getResourceId()
	{
		return 'commentaire-'.$this->activite.'-'.$this->photo.'-'.$this->individu;
	}
}
