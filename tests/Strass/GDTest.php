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
			     'gd-petite-jpeg-mini.jpeg');
  }

  function testReduireGrandePng2Jpeg()
  {
    Strass_Vignette::reduire(dirname(__FILE__).'/images/grande.png',
			     'gd-grande-png-mini.jpeg');
  }

  function testReduireJpeg2Png()
  {
    Strass_Vignette::reduire(dirname(__FILE__).'/images/petite.jpeg',
			     'gd-petite-jpeg-mini.png');
  }

  function testReduirePng2Jpeg()
  {
    Strass_Vignette::reduire(dirname(__FILE__).'/images/transparente.png',
			     'gd-png-mini.jpeg');
  }

  function testReduirePng2Png()
  {
    Strass_Vignette::reduire(dirname(__FILE__).'/images/transparente.png',
			     'gd-png-mini.png');
  }

  function testDecouperJpeg2Jpeg()
  {
    Strass_Vignette::decouper(dirname(__FILE__).'/images/petite.jpeg',
			      'gd-petite-jpeg-crop.jpeg');
  }

  function testDecouperJpeg2Png()
  {
    Strass_Vignette::decouper(dirname(__FILE__).'/images/petite.jpeg',
			      'gd-petite-jpeg-crop.png');
  }

  function testDecouperInconnu2Jpeg()
  {
    Strass_Vignette::decouper(dirname(__FILE__).'/images/jpeg-sans-suffixe',
			      'gd-inconnu-crop.jpeg');
  }

  function testDecouperPng2Jpeg()
  {
    Strass_Vignette::decouper(dirname(__FILE__).'/images/transparente.png',
			      'gd-png-crop.jpeg');
  }

  function testDecouperPng2Png()
  {
    Strass_Vignette::decouper(dirname(__FILE__).'/images/transparente.png',
			      'gd-png-crop.png');
  }
}
