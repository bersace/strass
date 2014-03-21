<?php

require_once 'Strass/Individus.php';

class ImagickTest extends PHPUnit_Framework_TestCase
{
  function testIndividu()
  {
    $o = new Individu;
    $o->slug = 'imagick';
    $o->storeImage(dirname(__FILE__).'/images/petite.jpeg');
    $o->storeImage(dirname(__FILE__).'/images/transparente.png');
  }

  function testUnite()
  {
    $o = new Unite;
    $o->slug = 'imagick';
    $o->storeImage(dirname(__FILE__).'/images/petite.jpeg');
    $o->storeImage(dirname(__FILE__).'/images/transparente.png');
  }

  function testDocument()
  {
    $o = new Document;
    $o->slug = 'imagick';
    $o->suffixe = 'jpeg';
    $o->storeFile(dirname(__FILE__).'/images/petite.jpeg');
    $o->suffixe = 'png';
    $o->storeFile(dirname(__FILE__).'/images/transparente.png');
    $o->suffixe = 'pdf';
    $o->storeFile(dirname(__FILE__).'/images/multipage.pdf');
  }

  function testPhoto()
  {
    $a = new Activite;
    $a->slug = 'activite';
    $a->debut = new Zend_Db_Expr('CURRENT_TIMESTAMP');
    $a->fin = new Zend_Db_Expr('CURRENT_TIMESTAMP');
    $a->save();

    $c = new Commentaire;
    $c->message = '';
    $c->save();

    $o = new Photo;
    $o->activite = $a->id;
    $o->commentaires = $c->id;
    $o->slug = 'imagick';
    $o->storeFile(dirname(__FILE__).'/images/petite.jpeg');
    $o->storeFile(dirname(__FILE__).'/images/transparente.png');
  }
}
