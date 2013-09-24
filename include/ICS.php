<?php

  // blabla … GPL v3 … blabla
  // Implement http://tools.ietf.org/html/rfc2445

class ICS
{
	protected	$_method;
	protected	$_prodid	= "-//bersace//NONSGML ICS class//EN";
	protected	$_version	= "2.0";
	protected	$_events	= array();
	protected	$_title;
	protected	$_tzid;

	function __construct($title = '', $tz = 'Europe/Paris', $method = "PUBLISH")
	{
		$this->_title = $title;
		$this->_method = $method;
		$this->_tzid = '/softwarestudio.org/Tzfile/'.$tz;
	}

	function addEvent($start,
			  $end,
			  $location,
			  $summary,
			  $description,
			  $attachments= array())
	{
		array_push($this->_events,
			   array('start'	=> $start,
				 'end'		=> $end,
				 'location'	=> $location,
				 'summary'	=> $summary,
				 'description'	=> $description,
				 'attachments'	=> $attachments));
	}

	function render()
	{
		ob_start();
		$this->output();
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	protected function _escape($val)
	{
		$table = array(','	=> '\,',
			       ';'	=> '\;',
			       "\n"	=> '\n');
		return str_replace(array_keys($table),
				   array_values($table),
				   $val);
	}


	// ajouter un champ
	protected function _p($key, $val)
	{
		if ($val)
			echo wordwrap($key.":".$this->_escape($val)."\r\n",
				      75, "\r\n ", true);
	}

	// ajouter un champ de type 'date'
	protected function _d($key, $val)
	{
		$this->_p($key.';TZID='.$this->_tzid, $val);
	}

	function output()
	{
		echo "BEGIN:VCALENDAR\r\n";
		$this->_p("PRODID",	$this->_prodid);
		$this->_p("VERSION",	$this->_version);
		$this->_p("METHOD",	$this->_method);
		$this->_p("TZID",	$this->_tzid);
		foreach($this->_events as $event) {
			extract($event);
			echo "BEGIN:VEVENT\r\n";
			$this->_d('DTSTART',	strftime('%Y%m%dT%H%M%SZ', $start)); 
			$this->_d('DTEND',	strftime('%Y%m%dT%H%M%SZ', $end)); 
			$this->_p('LOCATION',	$location);
			$this->_p('SUMMARY',	$summary);
			$this->_p('DESCRIPTION',$description);
			foreach($event['attachments'] as $attachment)
				$this->_p('ATTACH', $attachment);
			echo "END:VEVENT\r\n";
		}
		echo "END:VCALENDAR\r\n";
	}

	function getFilename()
	{
		return str_replace(' ', '_', $this->_title).'.ics';
	}
}