<?php

  /*
   * About Wtk_Form_Model.
   *
   * Wtk_Form_Model object contains instance, constraint (on instance)
   * and submissions.
   */


class Wtk_Form_Model
{
  public	$id;
  public	$instance;
  public	$constraints;
  public	$submissions;
  public	$errors;

  function __construct ($id)
  {
    $this->id		= $id;
    $this->instance	= new Wtk_Form_Model_Instance_Group ($id, '');
    $this->constraints	= array();
    $this->submissions	= array();
    $this->errors	= array();

    $this->addBool('$$validated$$', 'Validated', true);
  }

  /**
   * Add a field instance to the model. This can be done in two
   * manners. Either passe a Wtk_Form_Model_Instance object or passe
   * the type of the instance as string without the prefix
   * (e.g. 'Boolean', 'Enum') and the parameters along. The new object
   * will be created using the appended parameters and returned.
   *
   */
  function &addInstance ($instance)
    {
      $args = func_get_args();
      $instance = call_user_func_array(array($this->instance, 'addChild'), $args);

      return $instance;
    }

  function hasInstanceByType($class)
  {
    return $this->instance->hasInstanceByType($class);
  }

  function &getInstance ($path = '')
    {
      return $this->instance->getChild($path);
    }

  // SUBMISSIONS
  function &addSubmission (Wtk_Form_Model_Submission &$submission)
    {
      if ($submission instanceof Wtk_Form_Model_Submission) {
	$this->submissions[$submission->id] = $submission;
      }
      return $submission;
    }

  /**
   * Convenient function that create a new Submission and add it using
   * addSubmission().
   */
  function &addNewSubmission ($id, $label, $handler = null, $method = Wtk_Form_Model_Submission::METHOD_MULTIPART)
    {
      $submission = new Wtk_Form_Model_Submission ($id, $label, $handler, $method);
      $this->addSubmission ($submission);
      return $submission;
    }

  function &getSubmission ($id)
    {
      return $this->submissions[$id];
    }

  // CONSTRAINTS

  function &addConstraint ($constraint)
    {
      if ($constraint instanceof Wtk_Form_Model_Constraint) {
	array_push ($this->constraints, $constraint);
      }
      return $constraint;
    }


  function __call($method, $args)
  {
    if (preg_match("/^addConstraint(.*)$/", $method, $matches)) {
      $class = "Wtk_Form_Model_Constraint_".$matches[1];
      if (is_string($args[0])) {
	$args[0] = $this->getInstance($args[0]);
      }
      $cons = wtk_new($class, $args);
      return $this->addConstraint($cons);
    }
    // add instance
    else {
      return call_user_func_array(array($this->instance, $method), $args);
    }
  }

  /*
   * Wether data has been submitted.
   */
  function validate()
  {
    if (!count($this->submissions)) {
      throw new Exception("No submissions set for model ".$this->id);
    }

    $this->sent_submission = null;
    foreach ($this->submissions as $submission) {
      if ($values = $submission->isSubmitted($this)) {
	$this->sent_submission = $submission;
	break;
      }
    }

    if (!$values) {
      return FALSE;
    }

    // retrieve values
    // validate all constraints and gather errors
    $this->errors = array ();

    try {
      $this->instance->retrieve($values);
    }
    catch (Wtk_Form_Model_Exception $e) {
      array_push($this->errors, $e);
    }

    $valid = true;
    foreach ($this->constraints as $constraint) {
      try {
	$valid  = $valid && $constraint->validate();
      }
      catch (Wtk_Form_Model_Exception $e) {
	array_push ($this->errors, $e);
      }
    }

    $validated = $this->get('$$validated$$');

    return !count($this->errors) && $valid && $validated;
  }

  function get ($path = NULL)
  {
    if ($path) {
      $child = $this->instance->getChild($path);
      if($child) {
	return $child->get ();
      }
    }
    else {
      return $this->instance->get ($path);
    }
  }

  function __get($nom)
  {
    return $this->get($nom);
  }
}


// global exception for all constraints errors
class Wtk_Form_ModelException extends Exception implements Iterator
{
  protected $model;
  protected $errors;

  function __construct ($model)
  {
    parent::__construct ($model->id." n'est pas valide.");
    $this->model = $model;
  }

  // ITERATOR

  public function rewind () {
    return reset($this->model->errors);
  }

  public function current () {
    return current ($this->model->errors);
  }

  public function key () {
    return key ($this->model->errors);
  }

  public function next () {
    return next ($this->model->errors);
  }

  public function valid () {
    return $this->current () !== false;
  }
}

?>