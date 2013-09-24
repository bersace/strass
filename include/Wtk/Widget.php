<?php

require_once 'Temple.php';
require_once 'Wtk/Utils.php';

/**
 * Classe racine de tout les widgets Wtk. Cette class gère les
 * propriétés commune des widgets que sont l'identifiant, et les
 * marqueurs d'un widget.
 *
 * Les marqueurs d'un widget se traduiront en class CSS.
 *
 *
 */
abstract class Wtk_Widget
{
  /**
   * Tableau des marqueurs. Les marqueurs sont des chaînes de
   * caractères.
   */
  public	$flags;

  /**
   * Le composant de style à charger pour styliser ce widget. (Permet
   * d'éviter de charger le style pour formulaire s'il n'y a pas de
   * formulaire dans la page.
   */
  protected	$stylecomponent = 'common';

  /**
   * La liste des scripts à exécuter pour améliorer l'utilisabilité de
   * ce widget.
   */
  protected	$scripts = array();

  /**
   * Table des données à passer à Temple.
   */
  protected	$data;


  /**
   * Parent widget or NULL for top level widget.
   */
  protected	$parent = NULL;

  /**
   * Créer un nouveau widget avec l'id et les marqueurs données.
   */
  function __construct($id = NULL, $flags = array())
  {
    static $count = 0;
    // register events
    $this->id		= $id ? $id : 'widget'.$count++;
    $this->flags	= $flags;
    $this->data = array('id' => $this->id,
			            'flags' => &$this->flags);
    $this->addFlags(array_slice(explode('_', strtolower(get_class($this))), 1));
  }

  function parent($wid)
  {
    if ($this->parent) {
      throw new Exception("Can't have two parents");
    }
 
    $this->parent = $wid;
  }

  function unparent()
  {
    if ($this instanceof Wtk_Form_Fieldset) {
        $dbg = debug_backtrace();
        foreach($dbg as $i => $stack) {
            $dbg[$i]['object'] = get_class($stack['object']);
            $dbg[$i]['args'] = array();
            foreach($stack['args'] as $arg) {
                $dbg[$i]['args'][] = is_object($arg) ? get_class($arg) : $arg;
            }
        }
        
        Orror::kill($dbg);
    }
    if ($this->parent instanceof Wtk_Container) {
      $parent = $this->parent;
      $this->parent = null; // éviter la boucle car removeChild va appeler cette
                            // fonction.
      $parent->removeChild($this);
    }
  }

  function reparent($parent)
  {
    $this->unparent();
    $parent->addChild($parent);
  }

  function getParent($class = null)
  {
    if (!$this->parent) {
      return NULL;
    }

    if (!$class) {
      return $this->parent;
    }

    if ($this->parent instanceof $class) {
      return $this->parent;
    }

    return $this->parent->getParent($class);
  }


  function setId($id)
  {
    $this->data['id'] = $id;
  }

  function addFlags($flag)
  {
    $flags = func_get_args();
    foreach($flags as $flag) {
      if(is_array($flag)) {
	call_user_func_array(array($this, __FUNCTION__), $flag);
      }
      else if(is_string($flag)) {
	array_push($this->data['flags'], $flag);
      }
    }
    $this->data['flags'] = array_unique($this->data['flags']);

    return $this;
  }

  protected function getScript()
  {
    return str_replace('_', '/', strtolower(get_class($this)));
  }

  function addScripts($script)
  {
    $scripts = func_get_args();
    foreach($scripts as $script) {
      if(is_array($script)) {
	call_user_func_array(array($this, __FUNCTION__), $script);
      }
      else if(is_string($script)) {
	array_push($this->scripts, $script);
      }
    }
    $this->scripts = array_unique($this->scripts);
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
   * Retourne le modèle racine pour représenter le widget.
   */
  function template()
  {
    return $this->widgetTemplate();
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
  function widgetTemplate($class = NULL)
  {
    $klass = $class ? $class : get_class($this);
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
?>
