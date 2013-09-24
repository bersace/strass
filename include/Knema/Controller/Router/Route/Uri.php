<?php

if (! function_exists("array_fill_keys")) {
	function array_fill_keys(array $keys, $value) {
		return array_combine($keys, array_fill(0, count($keys), $value));
	}
 }


/**
 * Cette classes une bâtarde entre Zend_Controller_Router_Route,
 * Zend_Controller_Router_Route_Regex et la classe Uri de David
 * Duret <dduret@gmail.com>
 *
 * * Tout comme Zend_Controller_Router_Route_Regex et la classe Uri,
 *   elle permet l'utilisatin de regex dans l'Uri. Et notamment des
 *   séparateur arbitraire.
 *
 * * Tout comme Zend_Controller_Router_Route, un jocker est
 *   disponible permettant d'ajouter un nombre indéfini de paire
 *   var/val à l'Uri.
 *
 * * L'assemblage des Url est aussi propre qu'Uri, c'est à dire que
 *   les valeurs par défaut sont omises. Contrairement à
 *   Zend_Controller_Router_Route_Regex.
 *
 *
 * L'implémentation consiste ajoute les fonctionnalités d'Uri à
 * Zend_Controller_Router_Route_Regex, le sous-bassement métier est
 * donc dans Zend_Controller_Router_Route_Regex.
 *
 */
class Knema_Controller_Router_Route_Uri extends Zend_Controller_Router_Route_Regex
{
	// Liste des variables à prendre en compte
	protected	$vars;

	// Liste des patterns de chaque variable
	protected	$patterns;

	// Modèle du format d'entrée
	protected	$inputUri;

	// Modèle du format de sortie
	protected	$outputUri;
    
	// Pattern de sortie des paramètre
	protected	$outputUriTemplates = array ();

	// Tableau associatif contenant pour chaque paramètre, les
	// sous-paramètres qui doivent être explicité même si leur valeur
	// est celle par défaut.
	protected	$inputUriDeps;

	/**
	 * $vars est un table contenant la configuration de chaques
	 * paramètre explicitement attendue, inclue la valeur jocker
	 * (dièse). Chaque configuration de paramètre est un tableau
	 * contenant le masque de capture et la valeur par défaut.
	 *
	 * $inputUri est le masque de capture d'une Uri avec les paramètre
	 * marqué comme %<param>%. Les crochet indique une partie
	 * optionnelle de l'Uri.
	 *
	 */
	function __construct($vars, $inputUri, $outputUri = '')
	{
		$this->vars		= array();
    
		$have_jocker = strpos($inputUri, '*') !== FALSE;
		if ($have_jocker) {
			$vars['#'] = array('((?:/[[:alnum:]]+/[^/]+)*)', '');
			$inputUri = str_replace ('*', '[%#%]', $inputUri);
			$outputUri = str_replace ('*', '[%#%]', $outputUri);
		}
    
		$i = 1;
		foreach($vars as $var => $conf) {
			$this->vars[] = $var;
			$this->patterns[$var] = $conf[0];
			$this->_defaults[$var] = $conf[1];
		}

		$this->_params = array();

		$this->inputUri = $inputUri;
		$this->outputUri = $outputUri ? $outputUri : $inputUri;

		$this->buildInputUriPattern();
		$this->buildOutputUriTemplates(null);
	}

	protected function buildInputUriPattern ()
	{
		// Détermination de l'ordre d'apparition des parametres       
		$this->inputUriDeps = $this->getUriDeps($this->inputUri);
		$this->inputUriDeps = array_merge(array_fill_keys($this->vars, array()),
						  $this->inputUriDeps);

		// Transformation du modèle en pattern
		$inputUri = $this->inputUri;
		$inputUri = str_replace (array ('[', ']'), array ('ééé', 'èèè'), $inputUri);
		$inputUri = preg_quote ($inputUri);
		$inputUri = str_replace (array ('ééé', 'èèè'), array ('(?:', ')?'), $inputUri);
		$inputUri = $inputUri;

		// Détermination de l'ordre d'apparition des parametres
		$this->inputUriOrder = $this->getDepsOrder();
		$this->inputUriOrder = array_unique(array_merge($this->inputUriOrder,
								$this->vars));

		ksort($this->inputUriOrder);
		$this->_map = array_combine(range(1, count($this->inputUriOrder)),
					    array_values($this->inputUriOrder));

		// Construction du pattern global
		$patterns = array ();
		foreach ($this->inputUriOrder as $value) {
			$patterns[$value] = $this->patterns[$value];
		}

		// config de Zend_Controller_Router_Route_Regex
		$this->_regex = preg_replace ('/%('.join ('|', $this->vars).')%/e', '$patterns["\\1"]', $inputUri);
	}

	protected function getUriDeps($uri)
	{
		if (strpos($uri, '%') === FALSE) {
			return array();
		}

		$nd = '[^\[\]]';			    // non délimiteurs
		$p = "%(?:".join ('|', $this->vars).")%"; // param
		$pc = "%(".join ('|', $this->vars).")%";  // param capturé
		$f = "\[$nd*$p$nd*\]";		    // param facultatif
		$fc = "\[$nd*$pc$nd*\]";		    // param facultatif capturé
		$nf = "(?:$nd|$f)*";		    // param facultatif ou non délimiteur


		// capturer les params finaux indépendants
		$pattern = "`\[$nf($fc)$nf\]`"; //'`%('.join ('|', $this->vars).')%`';
		$children = array();
		$cont = $uri;
		$conts = array();
		$finals = array();
		while(preg_match($pattern, $cont, $match)) {
			$finals[] = $match[2];
			$conts[] = $match[0];
			$cont = str_replace($match[1], '', $match[0]);
			$conts[] = $cont;
		}
		$conts = array_unique($conts);

		// capture le parent
		$deps = array();
		$pattern = "`$pc`";
		$cont = end($conts);
		preg_match_all($pattern, $cont, $matches);
		foreach($finals as $final) {
			$deps[$final] = $matches[1];
		}

		$uri = str_replace(reset($conts), end($conts), $uri);
		if ($conts) {
			$deps = array_merge($deps, $this->getUriDeps($uri));
		}

		return $deps;
	}

	/*
	 * Détermine l'ordre de traitement des paramètre selon les dépendances.
	 */
	protected function getDepsOrder($key = null)
	{
		$key = $key ? $key : reset($this->vars);

		$order = array();
		foreach($this->inputUriDeps[$key] as $dep) {
			$order = array_merge($order, $this->getDepsOrder($dep));
		}
		$order[] = $key;

		return array_unique($order);
	}

	protected function buildOutputUriTemplates($template = '')
	{
		$template = $template ? $template : $this->outputUri;

		if (preg_match ('`\[([^\[\]]*%('.join ('|', $this->vars).')%[^\[\]]*)\]`', $template, $match)) {

			// Stockage dans le tableau
			$this->outputUriTemplates[$match[2]] = $match[1];
          
			// Modification du template
			$template = str_replace ($match[0], '', $template);

			// Appel en recursif
			if ($template) {
				$this->buildOutputUriTemplates ($template);
			}
		}
	}

	function match($path)
	{
		$return = parent::match($path);

		if (!$return)
			return $this->_defaults;

		$this->_values = $return;

		// on ignore les valeur non définie.
		foreach($this->_values as $k => $v) {
			if (!$v)
				unset($this->_values[$k]);
		}

		// lecture des paramètre jocker. Stockage dans $this->_params;
		if (array_key_exists('#', $this->_values)) {
			preg_match_all("`([[:alpha:]]+/[^/]+)`", $this->_values['#'], $res);
			foreach($res[1] as $r) {
				list($k, $v) = explode('/', $r);
				// don't overwrite existing fields
				if (!isset($return[$k])) {
					$this->_params[$k] = $v;
				}
			}

			unset($this->_values['#']);
		}

		$return = $this->_values + $this->_params + $this->_defaults;

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
			if ($var != '#') {
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

		// recréer le paramètre # à partir des paramètres inconnus.

		$wcs = array();
		foreach($data as $k => $v) {
			// les valeur nulle seront remplacées par les valeurs par défaut.
			if (!$v) {
				unset($data[$k]);
			}
			// si la variable est inconnu, la mettre dans le paramètre.
			else if (!in_array($k, $this->vars)) {
				$wcs[]= $k.'/'.$v;
				unset($data[$k]);
			}
		}

		if ($wcs) {
			$data['#'] = '/'.implode('/', $wcs);
		}
    
		$data = $data + $this->_defaults;


		// Reconstruction de l'url -- partie largement copié de la classe Uri.
		$uri = $this->outputUri;
		$defaults = array_fill_keys($this->vars, TRUE);

		foreach (array_reverse($this->inputUriOrder) as $key) {
			$value = isset($this->outputUriTemplates[$key]) ? $this->outputUriTemplates[$key] : '';

			// Si la valeur est celle par défaut et ceci pour les paramètre dépendant, …
			if ($defaults[$key] && $data[$key] == $this->_defaults[$key]) {
				$replacement = '';
			}
			else {
				$replacement = preg_replace('`%('.$key.')%`e', '$data["\\1"]', $value);
				$this->setDefault($defaults, $key, FALSE);
			}
       
			$uri = str_replace($value, $replacement, $uri);
		}

		$uri = str_replace (array ('[', ']'), '', $uri);
		$uri = preg_replace ('`%('.join ('|', array_keys ($data)).')%`e', '$data["\\1"]', $uri);

		return $uri;
	}

	/*
	 * Propage la définition de l'utilisation d'une valeur par défaut.
	 */
	protected function setDefault(&$defaults, $key, $value)
	{
		$defaults[$key] = $value;
		foreach($this->inputUriDeps[$key] as $dep) {
			$this->setDefault($defaults, $dep, $value);
		}
	}
}
