<?php

require_once('Wtk/Table/Model/Tree.php');

class TreeTest extends PHPUnit_Framework_TestCase
{
  function pathCmpFixtures()
  {
    return array(array(array(0), array(0, 0), -1),
		 array(array(0, 1), array(0), 1),
		 array(array(1), array(0, 1), 1),
		 array(array(1, 0), array(0), 1),
		 );
  }

  /**
   * @dataProvider pathCmpFixtures
   */
  function testCmp($a, $b, $res)
  {
    $this->assertEquals(wtk_table_tree_path_cmp($a, $b), $res);
  }
}