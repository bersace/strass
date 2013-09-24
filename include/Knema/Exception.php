<?php

class Knema_Exception extends Exception
{
  function __construct($msg, $file, $line, $class, $function, $level, $backtrace, $exception = NULL)
  {
    parent::__construct($msg, $level);
    $this->file = $file;
    $this->line = $line;
    $this->class = $class;
    $this->functtion = $function;
    $this->backtrace = $backtrace;
  }
}