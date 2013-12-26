<?php

/* Adapte DbTable pour utiliser une identity Digest (username, realm) */
class Strass_Auth_Adapter_DbTable extends Zend_Auth_Adapter_DbTable implements Zend_Auth_Adapter_Interface
{
  public function setCredential($credential)
  {
    extract($this->_identity);
    $this->_credential = Users::hashPassword($username, $credential);
    return $this;
  }

  /**
   * _authenticateCreateSelect() - This method creates a Zend_Db_Select object that
   * is completely configured to be queried against the database.
   *
   * @return Zend_Db_Select
   */
  protected function _authenticateCreateSelect()
  {
    // build credential expression
    if (empty($this->_credentialTreatment) || (strpos($this->_credentialTreatment, '?') === false)) {
      $this->_credentialTreatment = '?';
    }

    $credentialExpression = new Zend_Db_Expr('(CASE WHEN ' .
					     $this->_zendDb->quoteInto($this->_zendDb->quoteIdentifier($this->_credentialColumn, true)
								       . ' = ' . $this->_credentialTreatment, $this->_credential)
					     . ' THEN 1 ELSE 0 END) AS '
					     . $this->_zendDb->quoteIdentifier('zend_auth_credential_match'));

        // get select
        $dbSelect = clone $this->getDbSelect();
        $dbSelect->from($this->_tableName, array('*', $credentialExpression))
                 ->where($this->_zendDb->quoteIdentifier($this->_identityColumn, true) . ' = ?',
			 $this->_identity['username']);

        return $dbSelect;
    }
}
