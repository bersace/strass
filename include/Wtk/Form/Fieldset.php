<?php

class Wtk_Form_Fieldset extends Wtk_Container {
	protected $title;
	protected $prefix;

	/**
	 * Define @model only if you want to use addChild convenient
	 * parameters.
	 *
	 */
	function __construct($title)
	{
		parent::__construct();
		$this->title = $title;
	}

	function _finalize()
	{
		$form = $this->getParent('Wtk_Form');
		$model = $form->getModel();

		try {
		  $i = $model->getInstance($this->title);
		  $this->title = $i->label;
		  $this->prefix = $i->path;
		  $this->setId(wtk_strtoid($this->prefix));
		}
		catch (Exception $e) {
		  $this->setId(wtk_strtoid($this->title));
		}

		$this->title = new Wtk_Inline($this->title);
		$this->_finalizeChildren();
	}

	/**
	 * Copy-pasted from Wtk_Form::addChild() :/
	 */
	function addChild($wid)
	{
		$args = func_get_args();
		if (count($args) == 1 && $wid instanceof Wtk_Element) {
			return parent::addChild($wid);
		}
		else {
			$wid = call_user_func_array(array($this->getParent('Wtk_Form'),
							  'addChild'), $args);
			$wid->reparent($this);
		}
	}

	function __call($method, $args)
	{
		$form = $this->getParent('Wtk_Form');
		$model = $form->getModel();

		if ($args) {
		  /* Tenter de résoudre le chemin relativement au groupe */
		  $path = $args[0];
		  if (is_string($path)) {
		    try {
		      $instance = $model->getInstance($this->title . '/' . $path);
		      $args[0] = $instance;
		    }
		    catch (Exception $e) { }
		  }
		}

		$cb = array($form, $method);
		$el = call_user_func_array($cb, $args);
		$el->reparent($this);
		return $el;
	}

	function template()
	{
		$tpl = $this->elementTemplate();
		$title = $this->title->template();
		$tpl->addChild('title', $title);
		$container = $this->containerTemplate();
		$tpl->addChild('content', $container);
		return $tpl;
	}
  }
