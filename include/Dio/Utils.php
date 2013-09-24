<?php




  // kind of call_user_func_array, but for new
function dio_new_user_class_array($class, $args)
{
	$code = '$obj = new '.$class.' ('.implode(', ', dio_args_string('args', $args)).');';
	eval($code);
	return $obj;
}


/**
 * Returne un tableau contenant les chaînes de chaque arguments à passer à
 * eval() pour faire un appel de fonction avec les valeurs de $args.
 */
function dio_args_string($name, $args)
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
 * Generate an id from a string by replacing special chars with ascii
 * correspondant, and replacing all non alnum chars by hyphen.
 */
function dio_strtoid($string)
{
	static $table = array(	// minuscule
			      'á' => 'a', 'à' => 'a',
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
			      // majuscules
			      'Á' => 'A', 'À' => 'A',
			      'Â' => 'A', 'Ä' => 'A',
			      'Å' => 'A',
			      'É' => 'E', 'È' => 'E',
			      'Ê' => 'E', 'Ë' => 'E',
			      'Ì' => 'I', 'Í' => 'I',
			      'Î' => 'I', 'Ï' => 'I',
			      'Ó' => 'O', 'Ô' => 'O',
			      'Ö' => 'O', 'Ø' => 'O',
			      'Ò' => 'O',
			      'Ú' => 'U', 'Ù' => 'U',
			      'Û' => 'U', 'Ü' => 'U',
			      'Ç' => 'C',
			      'Œ' => 'OE', 'Æ' => 'AE',
			      '«' => '"', '»' => '"',
			      "‘" => "'", "’" => "'",
			      '“' => '"', '”' => '"',
			      '—' => '-', '–' => '-',
			      ' ' => ' ', "\t"=> ' ');

	$string = str_replace(array_keys($table),
			      array_values($table),
			      $string);
	$string = preg_replace('/[[:punct:][:space:]]+/', '_', $string);
	$string = trim($string, '_');

	return $string;
}
