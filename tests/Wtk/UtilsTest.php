<?php

require_once('Wtk/Utils.php');

class UtilsTest extends PHPUnit_Framework_TestCase
{
  function slugifyFixtures()
  {
    return array(array('Toto', 'toto'),
		 array('Toto-', 'toto'),
		 array('To-To', 'to-to'),
		 array('Éternel', 'eternel'),
		 array('Deux mots', 'deux-mots'),
		 array('deux  espaces', 'deux-espaces'),
		 array('De la mer°…', 'de-la-mer'),
		 );
  }

  /**
   * @dataProvider slugifyFixtures
   */
  function testSlugify($label, $slug)
  {
    $this->assertEquals(wtk_strtoid($label), $slug);
  }


  function ucfirstFixtures()
  {
    return array(array('toto', 'Toto'),
		 array('to-To', 'To-To'),
		 array('éternel', 'Éternel'),
		 array('158ème', '158ème'),
		 );
  }

  /**
   * @dataProvider ucfirstFixtures
   */
  function testUcfirst($label, $capsed)
  {
    $this->assertEquals(wtk_ucfirst($label), $capsed);
  }
}