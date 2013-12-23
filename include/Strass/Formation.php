<?php

class Diplomes extends Strass_Db_Table_Abstract
{
	protected	$_name			=  'diplomes';
	protected	$_dependentTables	= array('Formation');
	protected	$_rowClass		= 'Diplome';
}

class Diplome extends Zend_Db_Table_Row_Abstract
{
	static protected $branches = array('l'	=> 'louveteau',
					   'j'	=> 'jeannette',
					   'e'	=> 'éclaireur',
					   'g'	=> 'guide',
					   'r'	=> 'routier',
					   'a'	=> 'guide-aînée',
					   '' => '');


	function getBranche()
	{
		return self::$branches[$this->branche];
	}
}

class Formation extends Strass_Db_Table_Abstract
{
	protected	$_name		= 'formation';
	protected	$_referenceMap	= array('Diplome'	=> array('columns'	=> array('diplome', 'branche'),
									 'refTableClass'=> 'Diplomes',
									 'refColumns'	=> array('id', 'branche')),
						'Individu'	=> array('columns'	=> 'individu',
									 'refTableClass'=> 'Individus',
									 'refColumns'	=> array('id'),
									 'onUpdate' => self::CASCADE,
									 'onDelete' => self::CASCADE));
}
