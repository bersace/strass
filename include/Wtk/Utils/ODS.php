<?php

function wtk_ods_table($child, $table, $x=0, $y=0)
{
	$child->addData(array('_table'	=> $table,
			      '_x'	=> $x,
			      '_y'    	=> $y));

}

