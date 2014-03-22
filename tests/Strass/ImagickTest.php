<?php

require_once 'Strass/Individus.php';

class ImagickTest extends PHPUnit_Framework_TestCase
{
  function testIndividuJpeg()
  {
    $o = new Individu;
    $o->slug = 'imagick-jpeg';
    $o->storeImage(dirname(__FILE__).'/images/petite.jpeg');
  }

  function testIndividuPng()
  {
    $o = new Individu;
    $o->slug = 'imagick-png';
    $o->storeImage(dirname(__FILE__).'/images/transparente.png');
  }

  function testUniteJpeg()
  {
    $o = new Unite;
    $o->slug = 'imagick-jpeg';
    $o->storeImage(dirname(__FILE__).'/images/petite.jpeg');
  }

  function testUnitePng()
  {
    $o = new Unite;
    $o->slug = 'imagick-png';
    $o->storeImage(dirname(__FILE__).'/images/transparente.png');
  }

  function testDocumentJpeg()
  {
    $o = new Document;
    $o->slug = 'imagick-jpeg';
    $o->suffixe = 'jpeg';
    $o->storeFile(dirname(__FILE__).'/images/petite.jpeg');
  }

  function testDocumentPng()
  {
    $o = new Document;
    $o->slug = 'imagick-png';
    $o->suffixe = 'png';
    $o->storeFile(dirname(__FILE__).'/images/transparente.png');
  }

  function testDocumentPdf()
  {
    $o = new Document;
    $o->slug = 'imagick-pdf';
    $o->suffixe = 'pdf';
    $o->storeFile(dirname(__FILE__).'/images/multipage.pdf');
  }

  function testPhotoJpeg()
  {
    $a = new Activite;
    $a->slug = 'imagick-jpeg';
    $a->debut = new Zend_Db_Expr('CURRENT_TIMESTAMP');
    $a->fin = new Zend_Db_Expr('CURRENT_TIMESTAMP');
    $a->save();

    $c = new Commentaire;
    $c->message = '';
    $c->save();

    $o = new Photo;
    $o->activite = $a->id;
    $o->commentaires = $c->id;
    $o->slug = 'photo-jpeg';
    $o->storeFile(dirname(__FILE__).'/images/petite.jpeg');
  }

  function testPhotoPng()
  {
    $a = new Activite;
    $a->slug = 'imagick-png';
    $a->debut = new Zend_Db_Expr('CURRENT_TIMESTAMP');
    $a->fin = new Zend_Db_Expr('CURRENT_TIMESTAMP');
    $a->save();

    $c = new Commentaire;
    $c->message = '';
    $c->save();

    $o = new Photo;
    $o->activite = $a->id;
    $o->commentaires = $c->id;
    $o->slug = 'photo-png';
    $o->storeFile(dirname(__FILE__).'/images/transparente.png');
  }
}
