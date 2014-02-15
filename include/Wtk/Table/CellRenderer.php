<?php

abstract class Wtk_Table_CellRenderer
{
  public $properties = array();
  public $keys = array();

  /*
   * Les arguments du constructeur sont l'ensemble de couple
   * propriété/clef indiquant quelles valeur du modèle vont définir
   * quelle propriétés.
   */
  function __construct ($prop = null, $key = null)
  {
    $args = func_get_args();
    for ($i = 0; $i < count ($args); $i+=2) {
      $this->keys[$args[$i]] = $args[$i+1];
    }
  }

  function getKeys()
  {
    return array_keys($this->keys);
  }

  function getProperties()
  {
    return array_values($this->keys);
  }

  function template ($tuple)
  {
    $data = $this->getDataFromTuple($tuple);
    $element = $this->element($data);
    return $element->template();
  }

  abstract function element($data);

  function getDataFromTuple($tuple)
  {
    $data = $this->properties;
    foreach ($this->keys as $prop => $keys) {
      if (is_array($keys)) {
	$data[$prop] = array();
	foreach ($keys as $key) {
	  if (array_key_exists($key, $tuple))
	    $data[$prop][] = $tuple[$key];
	  else /* permettre de passer directement des valeurs */
	    $data[$prop][] = $key;
	}
      }
      else {
	$key = $keys;
	if (array_key_exists($key, $tuple))
	  $data[$prop] = $tuple[$key];
	else /* permettre de passer directement des valeurs */
	  $data[$prop] = $key;
      }
    }
    return $data;
  }
}