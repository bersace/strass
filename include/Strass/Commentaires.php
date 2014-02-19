<?php

class Commentaires extends Strass_Db_Table_Abstract
{
  protected $_name		= 'commentaire';
  protected $_rowClass		= 'Commentaire';
  protected $_dependentTables	= array('Photos', 'Commentaires');
  protected $_referenceMap	= array('Parent' => array('columns'		=> 'parent',
							  'refTableClass'	=> 'Commentaires',
							  'refColumns'		=> 'id',
							  'onUpdate'		=> self::CASCADE,
							  'onDelete'		=> self::CASCADE),
					'Auteur' => array('columns'		=> 'auteur',
							  'refTableClass'	=> 'Individus',
							  'refColumns'		=> 'id',
							  'onUpdate'		=> self::CASCADE,
							  'onDelete'		=> self::CASCADE));
}

class Commentaire extends Strass_Db_Table_Row_Abstract implements Zend_Acl_Resource_Interface
{
  function __construct($config)
  {
    parent::__construct($config);

    $this->initResourceAcl(array());
  }

  function _initResourceAcl($acl) {
    $auteur = $this->findParentIndividus();
    if ($acl->hasRole($auteur))
      $acl->allow($auteur, $this, 'editer');
  }

  function getResourceId()
  {
    return 'commentaire-'.$this->id;
  }
}
