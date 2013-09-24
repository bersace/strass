<?php

class Etape extends Knema_Db_Table_Abstract
{
	protected	$_name			=  'etapes';
	protected	$_dependentTables	= array('Progression');
	protected	$_referenceMap		= array('Etape'	=> array('columns'		=> array('depend'),
									 'refTableClass'	=> 'Etape',
									 'refColumns'		=> array('id')));
}

class Progression extends Knema_Db_Table_Abstract
{
	protected	$_name		= 'progression';
	protected	$_referenceMap	= array('Étape'		=> array('columns'	=> array('etape', 'sexe'),
									 'refTableClass'=> 'Etape',
									 'refColumns'	=> array('id', 'sexe')),
						'Individu'	=> array('columns'	=> 'individu',
									 'refTableClass'=> 'Individus',
									 'refColumns'	=> array('id'),	
									 'onUpdate' => self::CASCADE,
									 'onDelete' => self::CASCADE));
}