<?php

class Wtk_Audio extends Wtk_Container {
  function __construct($src)
  {
    parent::__construct();

    $this->sources = array($src);
    $this->autoplay = false;
    $this->loop = false;
    $this->controls = true;
    $this->muted = false;
    $this->preload = false;
  }
}