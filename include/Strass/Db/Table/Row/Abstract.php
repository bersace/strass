<?php

abstract class Strass_Db_Table_Row_Abstract extends Zend_Db_Table_Row_Abstract
{
  protected $_privileges = array();

  function init()
  {
    if (!$this->_data) {
      $this->_data = array();
      foreach($this->getTable()->getColumns() as $name)
	$this->_data[$name] = null;
    }
  }

  function initPrivileges($acl, $unites)
  {
    foreach ($unites as $unite) {
      foreach ($this->_privileges as $priv) {
	list($role, $privileges) = $priv;
	$role = $unite->getRoleId(is_null($role) ? 'membre' : $role);
	if ($acl->hasRole($role))
	  $acl->allow($role, $this, $privileges);
      }
    }
  }


  /**
   * Set the table object, to re-establish a live connection
   * to the database for a Row that has been de-serialized.
   *
   * @param Zend_Db_Table_Abstract $table
   * @return boolean
   * @throws Zend_Db_Table_Row_Exception
   */
  public function setTable(Zend_Db_Table_Abstract $table = null)
  {
    if ($table == null) {
      $this->_table = null;
      $this->_connected = false;
      return false;
    }

    $tableClass = get_class($table);
    if (! $table instanceof $this->_tableClass) {
      require_once 'Zend/Db/Table/Row/Exception.php';
      throw new Zend_Db_Table_Row_Exception("The specified Table is of class $tableClass, expecting class to be instance of $this->_tableClass");
    }

    $this->_table = $table;
    $this->_tableClass = $tableClass;

    $info = $this->_table->info();

    if (array_intersect($info['cols'], array_keys($this->_data)) != $info['cols']) {
      require_once 'Zend/Db/Table/Row/Exception.php';
      throw new Zend_Db_Table_Row_Exception('The specified Table does not have the same columns as the Row');
    }

    if (! array_intersect((array) $this->_primary, $info['primary']) == (array) $this->_primary) {
      require_once 'Zend/Db/Table/Row/Exception.php';
      throw new Zend_Db_Table_Row_Exception("The specified Table '$tableClass' does not have the same primary key as the Row");
    }

    $this->_connected = true;
    return true;
  }

  function __wakeup()
  {
    parent::__wakeup();
    $this->setTable(new $this->_tableClass);
  }
}
