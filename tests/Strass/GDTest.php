<?php

class GDTest extends PHPUnit_Framework_TestCase
{
  static function setUpBeforeClass()
  {
    $_ENV['STRASS_IMAGE_BACKEND'] = 'GD';
  }

  static function tearDownAfterClass()
  {
    unset($_ENV['STRASS_IMAGE_BACKEND']);
  }

  function testReduireJpeg2Jpeg()
  {
    Strass_Vignette::reduire(dirname(__FILE__).'/images/petite.jpeg',
			     Strass::getRoot().'gd-jpeg-mini.jpeg');
  }

  function testReduireJpeg2Png()
  {
    Strass_Vignette::reduire(dirname(__FILE__).'/images/petite.jpeg',
			     Strass::getRoot().'gd-jpeg-mini.png');
  }

  function testDecouperJpeg2Jpeg()
  {
    Strass_Vignette::decouper(dirname(__FILE__).'/images/petite.jpeg',
			      Strass::getRoot().'gd-jpeg-crop.jpeg');
  }

  function testDecouperJpeg2Png()
  {
    Strass_Vignette::decouper(dirname(__FILE__).'/images/petite.jpeg',
			      Strass::getRoot().'gd-jpeg-crop.png');
  }

  function testDecouperInconnu2Jpeg()
  {
    Strass_Vignette::decouper(dirname(__FILE__).'/images/jpeg-sans-suffixe',
			      Strass::getRoot().'gd-inconnu-crop.jpeg');
  }

  function testReduirePng2Jpeg()
  {
    Strass_Vignette::reduire(dirname(__FILE__).'/images/transparente.png',
			     Strass::getRoot().'gd-png-mini.jpeg');
  }

  function testReduirePng2Png()
  {
    Strass_Vignette::reduire(dirname(__FILE__).'/images/transparente.png',
			     Strass::getRoot().'gd-png-mini.png');
  }

  function testDecouperPng2Jpeg()
  {
    Strass_Vignette::decouper(dirname(__FILE__).'/images/transparente.png',
			      Strass::getRoot().'gd-png-crop.jpeg');
  }

  function testDecouperPng2Png()
  {
    Strass_Vignette::decouper(dirname(__FILE__).'/images/transparente.png',
			      Strass::getRoot().'gd-png-crop.png');
  }
}
