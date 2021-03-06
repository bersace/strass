<?php

require_once 'Strass/Log.php';

class Strass_Logger
{
    function __construct($name = 'strass')
    {
        $this->name = $name;
    }

    function log($level, $message, $url=null, $detail=null)
    {
        $user = Zend_Registry::get('user');

        if (!$url)
            $url = $_SERVER['REQUEST_URI'];

        $data = array(
            'logger' => $this->name,
            'level' => $level,
            'user' => $user->id,
            'message' => $message,
            'url' => $url,
            'detail' => substr(serialize($detail), 0, 4096),
        );
        $t = new Logs;
        return $t->insert($data);
    }

    function info()
    {
        $args = func_get_args();
        array_unshift($args, Logs::LEVEL_INFO);
        return call_user_func_array(array($this, 'log'), $args);
    }

    function warn()
    {
        $args = func_get_args();
        array_unshift($args, Logs::LEVEL_WARNING);
        return call_user_func_array(array($this, 'log'), $args);
    }

    function error()
    {
        $args = func_get_args();
        array_unshift($args, Logs::LEVEL_ERROR);
        return call_user_func_array(array($this, 'log'), $args);
    }

    function critical()
    {
        $args = func_get_args();
        array_unshift($args, Logs::LEVEL_CRITICAL);
        return call_user_func_array(array($this, 'log'), $args);
    }
}
