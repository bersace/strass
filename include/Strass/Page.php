<?php

class Strass_Page implements Iterator, Countable
{
  public	$metas;
  public	$addons;
  public	$formats = array();
  public	$format;

  function __construct($metas)
  {
    $this->metas = $metas;
    $this->addons = array();
    $this->download = null;
  }

  public function addon(Strass_Addon $addon)
  {
    $this->addons[] = $addon;
    return $addon;
  }

  public function addFormat($format)
  {
    $this->formats[$format->suffix] = $format;
  }

  public function selectFormat($format)
  {
    $this->format = $this->formats[$format];
  }

    public function setDownloadUrl($url) {
        $this->download = $url;
    }

  function count()	{ return count($this->addons); }
  function rewind()	{ return reset($this->addons); }
  function current()	{ return current($this->addons); }
  function key()	{ return key($this->addons); }
  function next()	{ return next($this->addons); }
  function valid()	{ return $this->current() !== false; }
}
