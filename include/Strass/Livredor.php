<?php

class Livredor extends Strass_Db_Table_Abstract implements Zend_Acl_Resource_Interface
{
    protected	$_name			= 'livredor';
    protected	$_rowClass		= 'Message';

    function initAclResource($acl)
    {
        $acl->add($this);
    }

    function getResourceId()
    {
        return 'livredor';
    }

    function selectAmoderer()
    {
        return $this->select()
                    ->where('public = 0 OR public IS NULL')
                    ->order('date DESC');
    }
}

class Message extends Zend_Db_Table_Row_Abstract implements Zend_Acl_Resource_Interface
{
    function initAclResource($acl)
    {
        $acl->add($this);
    }

    function getResourceId()
    {
        return 'livredor-message-'.$this->id;
    }
}
