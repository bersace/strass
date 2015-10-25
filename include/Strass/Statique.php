<?php

require_once 'Wtk.php';


class Statique implements Zend_Acl_Resource_Interface
{
    protected $id;
    protected $title;


    function __construct($id)
    {
        $this->id = $id;
        $this->path = 'private/statiques/'.$id.'.wiki';

        try {
            Zend_Registry::get('acl')->add($this);
        }
        catch (Zend_Acl_Exception $e) {
            /* déjà ajoutée */
        }

        $this->title = preg_match("`^\+\+ (.*)$`m", $this->read(), $res) ? $res[1] : wtk_ucfirst($this->id);
    }

    function getId()
    {
        return $this->id;
    }

    function getFilename()
    {
        return $this->path;
    }

    function getResourceId()
    {
        return 'page-statique-'.$this->id;
    }

    function getTitle()
    {
        return $this->title;
    }

    function readable()
    {
        return is_readable($this->getFilename());
    }

    function read()
    {
        if ($this->readable())
            return @file_get_contents($this->getFilename());
    }

    function write($contents)
    {
        file_put_contents($this->getFilename(), $contents);
    }

    function delete()
    {
        unlink($this->getFilename());
    }
}
