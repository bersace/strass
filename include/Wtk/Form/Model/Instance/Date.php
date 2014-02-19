<?php

class Wtk_Form_Model_Instance_Date extends Wtk_Form_Model_Instance
{
	protected $format;

	/**
	 * @param value	Date au format SQL.
	 */
	function __construct ($path, $label, $value = NULL, $format = '%Y-%m-%d')
	{
		parent::__construct ($path, $label, $value);
		$this->format = $format;
	}

	/* On devrait utiliser Zend_Date ? */
	protected function timeToDateArray($time)
	{
		list($year, $month, $day, $hour, $min, $sec) = $time ? explode('/', strftime('%Y/%m/%d/%H/%M/%S', $time))
			: array(null, null, null, null, null, null);
		return array('year' => $year,
			     'month' => $month,
			     'day' => $day,
			     'hour' => $hour,
			     'min' => $min,
			     'sec' => $sec);
	}

	protected function dateArrayToTime($date)
	{
		$default = $this->timeToDateArray(60);
		$date = array_merge($default, $date);
		extract($date);
		return strtotime($year.'-'.$month.'-'.$day.' '.$hour.':'.$min.':'.$sec);
	}

	function set($value)
	{
	  switch(gettype($value)) {
	  case 'null':
	  case 'NULL':
	    $value = $this->timeToDateArray(time());
	    break;
	  case 'integer':
	    $value = $this->timeToDateArray($value);
	    break;
	  case 'string':
	    $value = $this->timeToDateArray(strtotime($value));
	    break;
	  case 'array':
	    break;
	  default:
	    throw new Exception("Impossible de récupérer la date $path");
	  }
	  parent::set($value);
	}

	function retrieve ($value)
	{
	  if ($this->readonly)
	    return true;

		if (!$value)
			return false;

		if (!is_array($value)) {
			list($year, $month, $day, $hour, $min, $sec) = explode('/',
									       strftime('%Y/%m/%d/%H/%M/%S',
											strtotime($value)));
			$value = array('year'	=> $year,
				       'month'	=> $month,
				       'day'	=> $day,
				       'hour'	=> $hour,
				       'min'	=> $min,
				       'sec'	=> $sec);
		}
		else
			extract ($value);

		if (isset ($year) && isset ($month) && isset ($day)) {
			$this->value = $value;
			return TRUE;
		}
		else
			return FALSE;
	}

	function getDateArray()
	{
		return $this->value;
	}

	function get()
	{
		return strftime($this->format,
				$this->dateArrayToTime($this->value));
	}
}
