<?php

  /* About Wtk_Form
   *
   * Wtk_Form are inspired by XForm specs. It is designed to allow both
   * output as x?html and xform. Wtk_Form is the root element of the
   * form.
   *
   */
class Wtk_Form extends Wtk_Container {
	protected $errors;
	protected $stylecomponent = 'form';

	function __construct(&$model)
	{
		parent::__construct();
		$this->id = $model->id;
		$this->model = $model;
		$this->setDojoType("wtk.Form");

		if (count($this->data['model']->errors)) {
			$this->errors = new Wtk_Dialog('Erreurs de validation');
			$this->errors->addFlags('form errors');
			foreach($this->data['model']->errors as $error)
				$this->errors->addForm_Error($error);
		}

		// Add a hidden field 'validated' which ensure that at least one
		// data is sent if this form is validated. If you call validate()
		// before new Wtk_Form_Model(), you won't be annoyed by this
		// value.
		$this->addHidden('$$validated$$');
	}

	function getModel()
	{
		return $this->model;
	}

	/**
	 * Similarily to Wtk_Form_Model::addInstance, this function allow to
	 * pass only the un prefixed control element name and its argumet or
	 * the element instance.
	 */
	function addChild($wid)
	{
		if (is_string($wid)) {
			$args = func_get_args();
			$method = 'add'.array_shift($args);
			$wid = call_user_func_array(array($this, $method), $args);
			return $wid;
		}

		return parent::addChild($wid);
	}

	function __call($method, $args)
	{
		preg_match('/^add(.+)$/', $method, $matches);

		$class = 'Wtk_Form_Control_'.$matches[1];
		if (!@class_exists($class)) {
			return parent::__call($method, $args);
		}

		$instance = $i = array_shift($args);
		if (is_string($instance)) {
		  try {
		    $instance = $this->model->getInstance($instance);
		  }
		  catch (Exception $e) {
		    $instance = null;
		  }
		}

		if (!$instance) {
			throw new Exception("Can't retrieve instance '".$i."' ".
					    "in model '".$this->model->id."'.");
		}
		$path = $instance->path;

		array_unshift($args, $instance);

		$cargs = wtk_args_string("args", $args);
		$code = "\$el = new ".$class."(".implode(",", $cargs).");";
		eval($code);

		foreach($this->model->constraints as $cons)
			if ($cons->getInstance()->path == $instance->path)
				$el->addFlags('constrained',$cons->getFlags());

		return $this->addChild($el);
	}

	function template()
	{
		$tpl = $this->elementTemplate();
		if ($this->errors)
			$tpl->addChild('errors', $this->errors->template());

		$this->addChildrenTemplate($tpl);
		return $tpl;
	}
  }
