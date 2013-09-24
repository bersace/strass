<?php
class Uri {

  // Liste des variables à prendre en compte
  public $vars = array();
    
  // Liste des valeurs par défaut pour les variables
  public $defaults = array();
    
  // Liste des patterns de chaque variable
  public $patterns = array();
    
  // Modèle du format d'entrée
  public $inputUri = '';
    
  // Modèle du format de sortie
  public $outputUri = '';

  // Pattern du format d'entrée
  public $inputUriPattern = '';
    
  // Pattern du format de sortie
  public $outputUriTemplates = array ();

  // Liste des variables dans l'ordre d'apparition dans l'uri d'entrée
  public $inputUriOrder = array ();


  /**
   * Constructeur
   */
  function __construct ($conf = array())
  {

    // Enregistrement des variables à prendre en compte
    foreach ($conf['vars'] as $key => $params) {
            
      $this->vars[]   = $key;
      $this->patterns[$key] = $params['pattern'];
      $this->defaults[$key] = $params['default'];            
    }

    $this->inputUri     = $conf['inputUri'];
    $this->outputUri    = isset($conf['outputUri']) ? $conf['outputUri'] : $conf['inputUri'];

    // Initialisation
    $this->buildInputUriPattern ();
    $this->buildOutputUriTemplates ();
  } // end of '__construct()'


  function parse( $url = NULL )
  {

    // Si l'URL n'est pas passée, utilisation de $_SERVER['REQUEST_URI']
    if (is_null ($url)) $url = $_SERVER['REQUEST_URI'];
    // Si l'URL ne correspond pas au pattern, c'est qu'il y a une erreur
    if (!preg_match ($this->inputUriPattern, $url, $match)) return FALSE;

    // Doit correspondre à $_SERVER['REQUEST_URI'] (non utilisé)
    $current = array_shift ($match);

    // Stockage des parametres dans un tableau
    $data = array ();
    foreach ($this->inputUriOrder as $key => $value) {
      $data[$value] = isset($match[$key]) && $match[$key] ? $match[$key] : $this->defaults[$value];
    }
        
    $this->data = $data;

    return $data;

  } // end of 'parseUrl()'



  function build ($data = array())
  {
        
    // Détermination des données à utiliser
    if (is_string($data)) parse_str($data, $data);
    $data = array_merge ($this->defaults, $data);

    // Reconstruction de l'url
    $uri = $this->outputUri;
    $default = TRUE;
        
    foreach (array_reverse ($this->inputUriOrder) as $key) {
            
      $value = isset ($this->outputUriTemplates[$key]) ? $this->outputUriTemplates[$key] : '';

      if ($data[$key] == $this->defaults[$key]) {

	if (TRUE === $default) $replacement = '';
	else {
	  $replacement = preg_replace('`%('.$key.')%`e', '$data["\\1"]', $value);
	  //$default = FALSE;
	}
      }

      else {
	$replacement = preg_replace ('`%('.$key.')%`e', '$data["\\1"]', $value);
	$default = FALSE;
      }
            
      $uri = str_replace ($value, $replacement, $uri);

    }

    $uri = str_replace (array ('(', ')'), '', $uri);
    $uri = preg_replace ('`%('.join ('|', array_keys ($data)).')%`e', '$data["\\1"]', $uri);

    return $uri;

  } // end of 'buildUrl()'



  function buildInputUriPattern ()
  {

    // Transformation du modèle en pattern
    $inputUri = $this->inputUri;
    $inputUri = str_replace (array ('(', ')', '*'), array ('ééé', 'èèè', 'ûûû'), $inputUri);
    $inputUri = preg_quote ($inputUri);
    $inputUri = str_replace (array ('ééé', 'èèè', 'ûûû'), array ('(?:', ')?', '.*'), $inputUri);
    $inputUri = '^'.$inputUri.'$';

    // Détermination de l'ordre d'apparition des parametres       
    preg_match_all ('`%('.join ('|', $this->vars).')%`', $inputUri, $matches);
    $this->inputUriOrder = $matches[1];

    // Construction du pattern global
    $patterns = array ();
    foreach ($this->inputUriOrder as $value) {
      $patterns[$value] = $this->patterns[$value];
    }
    $this->inputUriPattern = '`'.preg_replace ('/%('.join ('|', $this->vars).')%/e', '$patterns["\\1"]', $inputUri).'`';

  } // end of 'buildInputUriPattern()'



  function buildOutputUriTemplates ($template = '')
  {
    $template = $template ? $template : $this->outputUri;

    if (preg_match ('`\(([^\(\)]*%('.join ('|', $this->vars).')%[^\(\)]*)\)`', $template, $match)) {

      // Stockage dans le tableau
      $this->outputUriTemplates[$match[2]] = $match[1];
          
      // Modification du template
      $template = str_replace ($match[0], '', $template);

      // Appel en recursif
      if ($template) {
	$this->buildOutputUriTemplates ($template);
      }
    }
  } // end of 'buildOutputUriTemplates()'



} // end of 'Uri{}'
?>
