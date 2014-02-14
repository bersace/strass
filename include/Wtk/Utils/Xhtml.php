<?php

function wtk_attr ($name, $value)
{
	echo $value || is_int ($value) ? " ".$name."=\"".htmlspecialchars ($value)."\"" : "";
}

function wtk_id ($id)
{
	wtk_attr ('id', $id);
}

function wtk_djt($type)
{
	wtk_attr('dojoType', $type);
}

function flatten_array($array)
{
	$flat = array();
	foreach ($array as $cell) {
		if (is_array($cell))
			$flat = array_merge($flat, flatten_array($cell));
		else
			array_push($flat, $cell);
	}
	return $flat;
}

function wtk_classes ($classes)
{
	$args = func_get_args();
	$classes = flatten_array($args);
	wtk_attr ('class', implode(' ', $classes));
}

function wtk_id_classes ($id, $classes, $type=null, $tooltip=null)
{
	wtk_id ($id);
	wtk_classes ($classes);
	wtk_djt($type);
	wtk_attr('title', $tooltip);
}
