<?php

class Documents extends Zend_Db_Table_Abstract {
	protected $_name = 'documents';
	protected $_rowClass = 'Document';
	protected $_dependentTables = array('DocsUnite', 'DocsActivite');

	function getDependentTablesName()
	{
		return $this->_dependentTables;
	}
  }

class Document extends Strass_Db_Table_Row_Abstract implements Zend_Acl_Resource_Interface
{
	public function __construct(array $config = array()) {
		parent::__construct($config);
		$this->initResourceAcl($this->findUnitesViaDocsUnite());
	}

	public function getResourceId()
	{
		return $this->id;
	}

	public function getUri()
	{
		return '/'.$this->getFichier();
	}
    
	function getFichier($data = null)
	{
		return 'data/documents/'.($data ? $data['id'] : $this->id).'.'.($data ? $data['suffixe'] : $this->suffixe);
	}
    
	function _postDelete()
	{
		unlink($this->getFichier());
	}

	function _postUpdate()
	{
		rename($this->getFichier($this->_cleanData),
		       $this->getFichier());
	}

	function countLiaisons($tables = null)
	{
		$count = 0;

		if (is_null($tables))
			$tables = $this->getTable()->getDependentTablesName();
		if (is_string($tables))
			$tables = array($tables);

		foreach($tables as $tn) {
			$func = 'find'.$tn;
			$count+= call_user_func(array($this, $func))->count();
		}
		return $count;
	}
}

class DocsUnite extends Zend_Db_Table_Abstract
{
	protected $_name = 'doc_unite';
	protected $_rowClass = 'DocUnite';
	protected $_referenceMap = array('Document' => array('columns' => 'document',
							     'refTableClass' => 'Documents', 
							     'refColumns' => 'id',
							     'onUpdate' => self::CASCADE,
							     'onDelete'  => self::CASCADE),
					 'Unite' => array('columns' => 'unite',
							  'refTableClass' => 'Unites', 
							  'refColumns' => 'id',
							  'onUpdate' => self::CASCADE,
							  'onDelete' => self::CASCADE));
}

class DocUnite extends Zend_Db_Table_Row_Abstract
{
}



class DocsActivite extends Zend_Db_Table_Abstract
{
	protected $_name = 'doc_activite';
	protected $_rowClass = 'DocActivite';
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

class DocActivite extends Zend_Db_Table_Row_Abstract
{
}

