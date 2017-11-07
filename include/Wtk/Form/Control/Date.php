<?php

class Wtk_Form_Control_Date extends Wtk_Form_Control
{
	function __construct ($instance, $format = '%Y-%m-%d')
	{
		parent::__construct ($instance);
		$this->data = array_merge($this->data,
					  $instance->getDateArray());
		$this->setFormat ($format);
		$this->setDojoType('wtk.form.control.Date');
	}

	function setFormat ($format)
	{
		$this->data['format'] = $format;
	}
}
