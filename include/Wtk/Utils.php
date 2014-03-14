<?php

mb_internal_encoding('UTF-8');

function wtk_fdate($date, $sfmt = '%d-%m-%Y')
{
	return strftime($sfmt, strtotime($date));
}

function wtk_strtoarray($string)
{
	$array = array();
	$i = 0;
	$l = mb_strlen($string);
	for ($i=0 ; $i < $l; $i++) {
		$array[] = mb_substr($string, $i, 1);
	}
	return $array;
}

/**
 * voir http://fr2.php.net/glob
 */
function wtk_glob($pattern, $flags = 0) {
	$split=explode('/',$pattern);
	$match=array_pop($split);
	$path=implode('/',$split);
	if (($dir=opendir($path))!==false) {
		$glob=array();
		while(($file=readdir($dir))!==false) {
			if (fnmatch($match,$file)) {
				if ((is_dir("$path/$file"))||(!($flags&GLOB_ONLYDIR))) {
					if ($flags&GLOB_MARK) $file.='/';
					$glob[]=$path.'/'.$file;
				}
			}
		}
		closedir($dir);
		if (!($flags&GLOB_NOSORT)) sort($glob);
		return $glob;
	} else {
		return false;
	}
}

/*
 * Remplace les espace par des espaces insécables.
 */
function wtk_nbsp($string)
{
	return str_replace(' ', ' ', $string);
}

/*
 * tronque $full à maximum $length sans couper de mot. Ajoute $ellipse
 * à la fin du resultat si besoin.
 */
function wtk_first_words($full, $length = 512, $ellipse = " […]")
{
	$firsts = current(explode("^^^", wordwrap($full, $length, "^^^", false)));
	if ($ellipse && $firsts != $full)
		$firsts.= $ellipse;

	return $firsts;
}

/*
 * tronque $full à maximum $length sans couper de lignesf. Ajout $ellipse
 * à la fin du resultat si besoin.
 */
function wtk_first_lines($full, $length = 512, $maxlines=5, $ellipse = "…")
{
  $lines = explode("\n", $full);
  $count = 0;
  $out = "";
  foreach($lines as $i => $line) {
    $out .= $line."\n";
    if (strlen($out) >= $length)
      break;

    if ($i >= $maxlines)
      break;
  }

  if ($ellipse && $out != $full)
    $out.= $ellipse;

  return $out;
}

/**
 * Implémentation de mb_ucfirst() en utilisant mb_convert_case(). En
 * attendant PHP 6 …
 */
function wtk_ucfirst($string)
{
	$mots = preg_split('`([- ])`', $string, 2, PREG_SPLIT_DELIM_CAPTURE);
	$incipit = $mots[0];
	if (!preg_match('/[[:digit:]]/', $incipit[0]))
	  $mots[0] = mb_convert_case($incipit, MB_CASE_TITLE);
	return implode('', $mots);
}

/**
 * Convert a shorthandbyte notation to int (e.g. 1k => 1024).
 *
 * See http://fr2.php.net/manual/fr/faq.using.php#faq.using.shorthandbytes
 *
 */
function wtk_steno_to_int($str)
{
	preg_match('/([[:digit:]]+)([[:alpha:]])?/', $str, $res);
	$n = $res[1];
	$m = 1;

	switch(strtolower($res[2])) {
	case 't':
		$m*= 1024;
	case 'g':
		$m*= 1024;
	case 'm':
		$m*= 1024;
	case 'k':
		$m*= 1024;
	default:
		$m*= 1;
	}

	return $n * $m;
}

/* on pourrai appeler cette fonction instiancate_user_class_array() */
function wtk_new($class, $args)
{
  if (!class_exists($class))
    throw new Exception("Class $class inexistant");

	$code = '$obj = new '.$class.' ('.implode(', ', wtk_args_string('args', $args)).');';
	eval($code);
	return $obj;
}

/**
 * Returne un tableau contenant les chaînes de chaque arguments à passer à
 * eval() pour faire un appel de fonction avec les valeurs de $args.
 */
function wtk_args_string($name, $args)
{
	$cargs = array();
	foreach ($args as $id => $arg) {
		switch (gettype($arg)) {
		case 'array':
		case 'object':
		case 'resource':
			$cargs[] = "\$".$name."[".$id."]";
			break;
		default:
			$cargs[] = var_export($arg, true);
			break;
		}
	}
	return $cargs;
}

/**
 * Generate an id from a string by lowerizing, replacing special chars
 * with ascii correspondant, and replacing all non alnum chars by
 * hyphen.
 */
function wtk_strtoid($string)
{
	static $table = array('á' => 'a', 'à' => 'a',
			      'â' => 'a', 'ä' => 'a',
			      'å' => 'a',
			      'é' => 'e', 'è' => 'e',
			      'ê' => 'e', 'ë' => 'e',
			      'ì' => 'i', 'í' => 'i',
			      'î' => 'i', 'ï' => 'i',
			      'ó' => 'o', 'ô' => 'o',
			      'ö' => 'o', 'ø' => 'o',
			      'ò' => 'o',
			      'ú' => 'u', 'ù' => 'u',
			      'û' => 'u', 'ü' => 'u',
			      'ç' => 'c',
			      'œ' => 'oe', 'æ' => 'ae',
			      '«' => '"', '»' => '"',
			      "‘" => "'", "’" => "'",
			      '“' => '"', '”' => '"',
			      '—' => '-', '–' => '-',
			      ' ' => ' ', "\t"=> ' ',
			      '…' => '...', '°' => '');
	return trim(preg_replace('/[[:punct:][:space:]]+/', '-',
				 str_replace(array_keys($table),
					     array_values($table),
					     mb_strtolower($string))),
		    '-');
}

function wtk_context($child, $context)
{
	$child->addData(array('_context' => $context));
}

function wtk_children_context($parent, $context)
{
	foreach($parent as $child)
		wtk_context($child, $context);
}

function wtk_abs_href($href)
{
	return (strpos($href, ':') < 3 ? 'http://'.$_SERVER['HTTP_HOST'] : '').$href;
}