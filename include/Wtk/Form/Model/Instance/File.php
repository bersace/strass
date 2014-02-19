<?php

class Wtk_Form_Model_Instance_File extends Wtk_Form_Model_Instance
{
	function __construct ($path, $label, $value = NULL)
	{
		parent::__construct ($path, $label);
		if ($value) {
			$this->retrieve($value);
		}
	}

	function isUploaded()
	{
		return (bool) $this->value['tmp_name'];
	}

	function getTempFilename ()
	{
		return $this->value['tmp_name'];
	}

	function getMimeType()
	{
		return $this->value['type'];
	}

	function getBasename()
	{
		return $this->value['name'];
	}

	function retrieve($value)
	{
	  if ($this->readonly)
	    return true;

		if (!isset($value['name']))
			return FALSE;

		$this->value = $value;

		switch ($value['error']) {
		case UPLOAD_ERR_OK:
			return TRUE;
			break;
		case UPLOAD_ERR_INI_SIZE:
		case UPLOAD_ERR_FORM_SIZE:
			throw new Wtk_Form_Model_Exception("Le fichier %s est trop volumineux. ".
							   "La taille maximum permise est ".
							   ini_get('upload_max_filesize').".", $this);
			break;
		case UPLOAD_ERR_PARTIAL:
			throw new Wtk_Form_Model_Exception("Le fichier %s est incomplet.", $this);
			break;
		case UPLOAD_ERR_NO_FILE:
			return TRUE;
			break;
		case UPLOAD_ERR_NO_TMP_DIR:
			throw new Wtk_Form_Model_Exception("Erreur interne: impossible de recevoir le fichier %s ".
							   "(aucun dossier temporaire).",
							   $this);
			break;
		case UPLOAD_ERR_CANT_WRITE:
			throw new Wtk_Form_Model_Exception("Erreur interne: impossible de recevoir le fichier %s ".
							   "(écriture impossible).",
							   $this);
			break;
		case UPLOAD_ERR_EXTENSION:
			throw new Wtk_Form_Model_Exception("Erreur interne: impossible de recevoir le fichier %s ".
							   "(arrêt par l'extension).",
							   $this);
			break;
		}

		return FALSE;
	}
}
