<?php

class Wtk_Form_Control_Entry extends Wtk_Form_Control
{
	function __construct ($instance, $width=12, $height=1, $suffix='')
	{
		parent::__construct ($instance);
		$this->setSize($width, $height);
		$this->useSuffix($suffix);

        /* Calcule le type HTML depuis le nom de la classe. */
        $klass = get_called_class();
        if ($klass == get_class())
            $this->type = 'text';
        else {
            $parts = explode('_', $klass);
            $this->type = strtolower(end($parts));
        }

        $this->placeholder = null;
	}

	function useSuffix($suffix)
	{
		$this->suffix = $suffix;
	}

    function setPlaceHolder($placeholder=null)
    {
        if (is_null($placeholder))
            $this->placeholder = $this->instance->label;
        else
            $this->placeholder = $placeholder;
    }


	function setSize ($width, $height)
	{
		$this->width = is_null ($width) ? $this->width : $width;
		$this->height = is_null ($height) ? $this->height : $height;
	}

	function controlTemplate()
	{
        /* On utilise le template Entry, même pour les sous-classes. */
		return parent::elementTemplate(get_class());
	}
}
