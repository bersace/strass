<?php

require_once 'Temple.php';
require_once 'Wtk/Utils.php';

/**
 * Classe racine de tout les elements Wtk. Cette class gère les
 * propriétés commune des elements que sont l'identifiant, et les
 * marqueurs d'un element.
 *
 * Les marqueurs d'un element se traduiront en class CSS.
 *
 *
 */
abstract class Wtk_Element
{
	/**
	 * Tableau des marqueurs. Les marqueurs sont des chaînes de
	 * caractères.
	 */
	public		$flags;

	/**
	 * Le composant de style à charger pour styliser ce element. (Permet
	 * d'éviter de charger le style pour formulaire s'il n'y a pas de
	 * formulaire dans la page.
	 */
	protected	$stylecomponent = 'common';

	/**
	 * La liste des scripts à exécuter pour améliorer l'utilisabilité de
	 * ce element.
	 */
	protected	$scripts = array();

	/**
	 * Table des données à passer à Temple.
	 */
	protected	$data;


	/**
	 * Parent element or NULL for top level element.
	 */
	protected	$parent = NULL;

	/**
	 * Créer un nouveau element avec l'id et les marqueurs données.
	 */
	function __construct($id = NULL, $flags = array())
	{
		static $count = 0;
		// register events
		$this->id	= $id ? $id : 'element'.$count++;
		$this->flags	= $flags;
		$this->data = array('id' => $id,
			            'flags' => &$this->flags);
		$this->dojoType = null;
		$this->tooltip = null;
		$this->addFlags(array_slice(explode('_', strtolower(get_class($this))), 1));
	}

	function finalize()
	{
		static $finalized = false;
		if (!$finalized)
			$this->_finalize();

		$this->finalized = true;
	}

	protected function _finalize()
	{
	}

	function parent($el)
	{
		if ($this->parent) {
			Orror::dump($this->id, $this->parent);
			throw new Exception("Can't have two parents");
		}
		$this->parent = $el;
	}

	function unparent()
	{
		if ($this->parent instanceof Wtk_Container) {
			$parent = $this->parent;
			$this->parent = null; // éviter la boucle car
					      // removeChild() va
					      // appeler cette
					      // fonction.
			$parent->removeChild($this);
		}
	}

	function reparent($parent)
	{
		$this->unparent();
		$parent->addChild($this);
	}

	function getParent($class = null)
	{
		if (!$this->parent)
			return NULL;

		if (!$class)
			return $this->parent;

		if ($this->parent instanceof $class)
			return $this->parent;

		return $this->parent->getParent($class);
	}


	function setId($id)
	{
		$this->id = $id;
		return $this;
	}

	function addFlags($flag = null)
	{
		$flags = func_get_args();
		foreach($flags as $flag) {
			if(is_array($flag))
				call_user_func_array(array($this, __FUNCTION__), $flag);
			else if(is_string($flag))
				array_push($this->data['flags'], $flag);
		}
		$this->data['flags'] = array_unique($this->data['flags']);

		return $this;
	}

	function hasFlag($flag)
	{
	  return in_array($flag, $this->flags);
	}

	function setDojoType($type)
	{
		$this->dojoType = $type;
	}

	protected function getDojoType()
	{
		return $this->dojoType;
	}

	function __set($name, $value)
	{
		$this->data[$name] = $value;
	}

	function __get($name)
	{
		return isset($this->data[$name]) ? $this->data[$name] : NULL;
	}

	/**
	 * Retourne le modèle racine pour représenter le element.
	 */
	function template()
	{
		return $this->elementTemplate();
	}

	/**
	 * Le fichier modèle est déterminé par le nom de la classe de la
	 * même manière que le fichier de classe. Les fichiers de modèles
	 * répondent eux aussi à un espaces de nommage.
	 *
	 * Cette fonction génère le modèle à partir de la classe de l'objet
	 * instancié ou de la classe passée en argument. Cette méthode
	 * permet de facilement choisir le fichier modèle tout en permettatn
	 * d'écraser la fonction template().
	 */
	function elementTemplate($class = NULL)
	{
		$klass = $class ? $class : get_called_class();
		$template = str_replace('_', '/', strtolower($klass));
		return new Temple($template, $this->data);
	}

	function getStyleComponent()
	{
		return $this->stylecomponent;
	}

	function getScripts()
	{
		return $this->scripts;
	}
}
