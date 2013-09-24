<?php

class Wtk_Form_Model_Submission
{
	const METHOD_POST		= 0;
	const METHOD_GET		= 1;
	const METHOD_PUT		= 2;
	const METHOD_MULTIPART	= 3;
	const METHOD_FORM_DATA	= 4;
	const METHOD_URLENCODED	= 5;

	public	$id;
	public	$label;
	public	$handler;
	public	$method;

	// BEWARE ! Handler MUST NOT CONTAINER HOSTNAME, Only REQUEST_URI !!
	function __construct ($id, $label, $handler = NULL, $method = NULL)
	{
		$this->id		= $id;
		$this->label	= $label;
		$this->handler	= $handler ? $handler : $_SERVER['REQUEST_URI']; // an URI
		$this->method	= $method;  // Valid value are post, get, put,
		// multipart-post, form-data-post
		// and urlencoded-post. see
		// http://www.w3.org/TR/2006/REC-xforms-20060314/slice11.html
	}

	/*
	 * If the model has been submitted using this submission, the values are returned. else FALSE.
	 */
	function isSubmitted ($model)
	{
		if ($_SERVER['REQUEST_URI'] != $this->handler) {
			return FALSE;
		}

		switch ($this->method) {
		case Wtk_Form_Model_Submission::METHOD_URLENCODED:
			$array = $_POST;
			break;
		case Wtk_Form_Model_Submission::METHOD_POST:
		case Wtk_Form_Model_Submission::METHOD_FORM_DATA:
		case Wtk_Form_Model_Submission::METHOD_MULTIPART:
			if (isset($_POST[$model->id]) || isset($_FILES[$model->id])) {
				$files = array ();
				$array = $_POST;
				foreach ($_FILES as $prefix => $value) {
					if (is_array($value['name'])) {
						$files[$prefix] = array_merge_recursive($files, $this->fixFilesArray($value));
					}
					else {
						$files[$prefix] = $value;
					}
				}
				$array = $this->mergeFilesInfos($array, $files);
			}
			else {
				$array = array();
			}
			break;      
		case Wtk_Form_Model_Submission::METHOD_GET:
			$array = $_GET;
			break;
		default:
			return FALSE;
			break;
		}

		if (isset ($array[$model->id])) {
			return $array[$model->id];
		}
		else {
			return FALSE;
		}
	}

	protected function mergeFilesInfos($array, $files)
	{
		$pathes = self::findPathes($files);
		$tpathes = array();
		foreach ($pathes as $path) {
			$path = implode('/', array_slice(explode('/', $path), 0, -1));
			array_push($tpathes, $path);
		}
		$pathes = array_unique($tpathes);

		foreach($pathes as $path) {
			$info = self::findData($path, $files);
			self::setData($array, $path, $info);
		}
		return $array;
	}

	/*
	 * fix $_FILES[key][name][key][key] instead of
	 * $_FILES[key][key][key][name]
	 */
	protected function fixFilesArray($files)
	{
		$finals = array();
		$pathes = self::findPathes($files['name']);
		$keys = array_keys($files);

		$data = array();
		foreach($pathes as $path) {
			foreach($keys as $key) {
				$data[$key] = self::findData($path, $files[$key]);
			}
			self::setData($finals, $path, $data);
		}
		return $finals;
	}

	/*
	 * Récupère la liste des fichier téléchargé sous forme de chemin
	 * key/key/key
	 */
	static protected function findPathes($array)
	{
		$pathes = array();
		if (is_array($array)) {
			foreach($array as $id => $child) {
				$cp = self::findPathes($child);
				if ($cp) {
					foreach($cp as $p) {
						array_push($pathes, $id.'/'.$p);
					}
				}
				else {
					array_push($pathes, $id);
				}
			}
			return $pathes;
		}
		else {
			return NULL;
		}
	}

	/*
	 * Retrieve a data from an array at $path
	 */
	static protected function findData ($path, $array)
	{
		if (!$path) {
			return $array;
		}

		$ids = explode ('/', $path);
		$root = array_shift($ids);

		return self::findData(implode ('/', $ids), $array[$root]);
	}

	/*
	 * create an array of $path with $data as leef.
	 */
	static protected function setData (&$array, $path, $data)
	{
		$ids = explode ('/', $path);
		if (count($ids) == 1) {
			$array[$path] = $data;
			return;
		}

		$root = array_shift ($ids);
		if (!isset($array[$root])) {
			$array[$root] = array();
		}

		self::setData($array[$root], implode ('/', $ids), $data);
	}
}