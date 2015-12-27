<?php

/* Assigne un controller selon l'action. Cela permet de créer des URL
 * raccourcis comme /troupe/effectifs/2004 plutôt que
 * /unites/effectis/unite/troupe/annee/2004 ou encore /troupe/calendrier/2012
 * plutôt que /activites/calendrier/unite/troupe/annee/2012.
 */
class Strass_Controller_Router_Route_Alias extends Strass_Controller_Router_Route_Uri
{
    public $aliases;

	function __construct($vars, $uri, array $aliases)
	{
        $vars['__alias__'] = array(join('|', array_keys($aliases)), 'default');
        parent::__construct($vars, $uri);
        $this->aliases = $aliases;
	}

	function match($path)
	{
        $return = parent::match($path);

        if ($return) {
            $overrides = $this->aliases[$return['__alias__']];
            $return['controller'] = $overrides[0];
            $return['action'] = $overrides[1];
            unset($return['__alias__']);
        }
        return $return;
	}

	function assemble($data = array(), $reset = false, $encode = false)
	{
		if (!$reset) {
			$data = $data + $this->_params;
		}

		/**
		 * On veut compléter $data avec des données parmis defaults,
		 * values et params en fonction de $reset et de $data.
		 */
		foreach ($this->vars as $var) {
			if ($var != '__jocker__') {
				$resetVar = isset($data[$var]) && $data[$var] === null;

				if (isset($data[$var]) && !$resetVar) {
					$data[$var] = $data[$var];
				}
				else if (!$reset && !$resetVar && isset($this->_values[$var])) {
					$data[$var] = $this->_values[$var];
				}
				else if (isset($this->_defaults[$var])) {
					$data[$var] = $this->_defaults[$var];
				}
			}
		}

        $target = array($data['controller'], $data['action']);

        if (($alias = array_search($target, $this->aliases)) === false)
            return false;

        if (!isset($data['unite']))
            return false;

        $data['__alias__'] = $alias;

        return parent::assemble($data, $reset, $encode);
	}
}
