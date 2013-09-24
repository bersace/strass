<?php

class Wtk_Form_Submit extends Wtk_Form_Button
{
	protected	$submission;

	function __construct (Wtk_Form_Model_Submission $submission)
	{
		parent::__construct($submission->label);
		$this->submission = $submission;
		$this->data['submission'] = $submission->id;
	}
}

