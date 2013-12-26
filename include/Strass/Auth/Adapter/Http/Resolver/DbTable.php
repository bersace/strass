<?php
require_once 'Zend/Auth/Adapter/Http/Resolver/Interface.php';

/* Fork de Zend_Auth_Adapter_DbTable pour s'intégrer avec Digest */
class Strass_Auth_Adapter_Http_Resolver_DbTable implements Zend_Auth_Adapter_Http_Resolver_Interface
{
  /**
   * Database Connection
   *
   * @var Zend_Db_Adapter_Abstract
   */
  protected $_zendDb = null;

  /**
   * $_tableName - the table name to check
   *
   * @var string
   */
  protected $_tableName = null;

  /**
   * $_identityColumn - the column to use as the identity
   *
   * @var string
   */
  protected $_identityColumn = null;

  /**
   * $_credentialColumns - columns to be used as the credentials
   *
   * @var string
   */
  protected $_credentialColumn = null;

  /**
   * __construct() - Sets configuration options
   *
   * @param  Zend_Db_Adapter_Abstract $zendDb
   * @param  string                   $tableName
   * @param  string                   $identityColumn
   * @param  string                   $credentialColumn
   * @param  string                   $credentialTreatment
   * @return void
   */
  public function __construct(Zend_Db_Adapter_Abstract $zendDb, $tableName = null, $identityColumn = null,
			      $credentialColumn = null)
  {
    $this->_zendDb = $zendDb;

    if (null !== $tableName) {
      $this->setTableName($tableName);
    }

    if (null !== $identityColumn) {
      $this->setIdentityColumn($identityColumn);
    }

    if (null !== $credentialColumn) {
      $this->setCredentialColumn($credentialColumn);
    }
  }

  /**
   * setTableName() - set the table name to be used in the select query
   *
   * @param  string $tableName
   * @return Zend_Auth_Adapter_DbTable
   */
  public function setTableName($tableName)
  {
    $this->_tableName = $tableName;
    return $this;
  }

  /**
   * setIdentityColumn() - set the column name to be used as the identity column
   *
   * @param  string $identityColumn
   * @return Zend_Auth_Adapter_DbTable
   */
  public function setIdentityColumn($identityColumn)
  {
    $this->_identityColumn = $identityColumn;
    return $this;
  }

  /**
   * setCredentialColumn() - set the column name to be used as the credential column
   *
   * @param  string $credentialColumn
   * @return Zend_Auth_Adapter_DbTable
   */
  public function setCredentialColumn($credentialColumn)
  {
    $this->_credentialColumn = $credentialColumn;
    return $this;
  }

  /**
   * getResultRowObject() - Returns the result row as a stdClass object
   *
   * @param  string|array $returnColumns
   * @param  string|array $omitColumns
   * @return stdClass
   */
  public function getResultRowObject($returnColumns = null, $omitColumns = null)
  {
    $returnObject = new stdClass();

    if (null !== $returnColumns) {

      $availableColumns = array_keys($this->_resultRow);
      foreach ( (array) $returnColumns as $returnColumn) {
	if (in_array($returnColumn, $availableColumns)) {
	  $returnObject->{$returnColumn} = $this->_resultRow[$returnColumn];
	}
      }
      return $returnObject;

    } elseif (null !== $omitColumns) {

      $omitColumns = (array) $omitColumns;
      foreach ($this->_resultRow as $resultColumn => $resultValue) {
	if (!in_array($resultColumn, $omitColumns)) {
	  $returnObject->{$resultColumn} = $resultValue;
	}
      }
      return $returnObject;

    } else {

      foreach ($this->_resultRow as $resultColumn => $resultValue) {
	$returnObject->{$resultColumn} = $resultValue;
      }
      return $returnObject;

    }
  }

  /**
   * Resolve credentials
   *
   * Only the first matching username/realm combination in the file is
   * returned. If the file contains credentials for Digest authentication,
   * the returned string is the password hash, or h(a1) from RFC 2617. The
   * returned string is the plain-text password for Basic authentication.
   *
   * The expected format of the file is:
   *   username:realm:sharedSecret
   *
   * That is, each line consists of the user's username, the applicable
   * authentication realm, and the password or hash, each delimited by
   * colons.
   *
   * @param  string $username Username
   * @param  string $realm    Authentication Realm
   * @throws Zend_Auth_Adapter_Http_Resolver_Exception
   * @return string|false User's shared secret, if the user is found in the
   *         realm, false otherwise.
   */
  public function resolve($username, $realm)
  {
    $exception = null;

    if ($this->_tableName == '') {
      $exception = 'A table must be supplied for the Zend_Auth_Adapter_DbTable authentication adapter.';
    } elseif ($this->_identityColumn == '') {
      $exception = 'An identity column must be supplied for the Zend_Auth_Adapter_DbTable authentication adapter.';
    } elseif ($this->_credentialColumn == '') {
      $exception = 'A credential column must be supplied for the Zend_Auth_Adapter_DbTable authentication adapter.';
    }

    if (null !== $exception) {
      throw new Zend_Auth_Adapter_Http_Resolver_Exception($exception);
    }

    // create result array
    $authResult = array('code'     => Zend_Auth_Result::FAILURE,
			'identity' => $username,
			'messages' => array());


    // get select
    $select = $this->_zendDb->select();
    $select
      ->from($this->_tableName,
	     array('credential' => $this->_credentialColumn))
      ->where($this->_zendDb->quoteIdentifier($this->_identityColumn) . ' = ?', $username);

    // query for the identity
    try {
      $resultIdentities = $this->_zendDb->fetchAll($select->__toString());
    } catch (Exception $e) {
      /**
       * @see Zend_Auth_Adapter_Exception
       */
      require_once 'Zend/Auth/Adapter/Exception.php';
      throw new Zend_Auth_Adapter_Exception('The supplied parameters to Zend_Auth_Adapter_DbTable failed to '
					    . 'produce a valid sql statement, please check table and column names '
					    . 'for validity.');
    }

    if (count($resultIdentities) != 1) {
      return false;
    }

    $resultIdentity = $resultIdentities[0];

    return $resultIdentity['credential'];
  }
}
