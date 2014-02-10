<?php

class Documents extends Zend_Db_Table_Abstract {
  protected $_name = 'document';
  protected $_rowClass = 'Document';
  protected $_dependentTables = array('DocsUnite', 'PiecesJointes');
}


class Document extends Strass_Db_Table_Row_Abstract implements Zend_Acl_Resource_Interface
{
  public function __construct(array $config = array()) {
    parent::__construct($config);
    $this->initResourceAcl($this->findUnitesViaDocsUnite());
  }

  public function getResourceId()
  {
    return 'document-'.$this->slug;
  }

  public function getUri()
  {
    return '/'.$this->getFichier();
  }

  function getCheminVignette($data = null)
  {
    if (!$data) $data = $this->_cleanData;
    return 'data/documents/'.$data['slug'].'-vignette.jpeg';
  }

  function getFichier($data = null)
  {
    if (!$data) $data = $this->_cleanData;
    return 'data/documents/'.$data['slug'].'.'.$data['suffixe'];
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
