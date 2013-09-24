<?php


  /* Pagine un texte en le découpant par titre de premier niveau si
   * possible ou alors par paquets de paragraphes dont chaque paquets
   * ne dépasse pas un certains nombre de caractères.
   */
class Wtk_Pages_Model_Text extends Wtk_Pages_Model
{
	protected	$pages;
	public		$titles;
	protected	$pointer;

	function __construct($text, $current = null, $len = 4096)
	{
		$pages = array();
		$text = trim(str_replace("\r", "", $text)); // :(
		$titles = array();
		if (strpos($text, "\n++ ") === false) {
			$pattern = "/^(.{1,".$len."}\n\n).{".($len/2).",}/s";
			$i = 1;
			while (preg_match($pattern, $text, $res)) {
				$text = str_replace($res[1], '', $text);
				$titles[$i] = $i;
				$pages[$i++] = $res[1];
			}
			$pages[$i] = $text;
			$titles[$i] = $i;
		}
		else {
			$slices = preg_split('/^\+\+ (.*)$/m', $text, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
			for($i = 0; $i < count($slices); $i+=2) {
				$id = wtk_strtoid($slices[$i]);
				$titles[$id] = $slices[$i];
				$pages[$id] = "++ ".$slices[$i].$slices[$i+1];
			}
		}

		parent::__construct($pages, 1, $current);
		$this->titles = $titles;
		$this->pages_id	= array_keys($titles);
		$this->current	= in_array($current, $this->pages_id) ? $current : reset($this->pages_id);
	}

	function getPrevId($ref = null)
	{
		return $this->getRelId($ref, -1);
	}

	function getNextId($ref = null)
	{
		return $this->getRelId($ref, +1);
	}

	protected function getRelId($ref, $sens)
	{
		$r = array_flip($this->pages_id);
		$ref = isset($r[$ref]) ? $ref : $this->current;
		$i = $r[$ref]+$sens;
		return array_key_exists($i, $this->pages_id) ? $this->pages_id[$i] : null;
	}

	function fetch($id = null)
	{
		$id = $id ? $id : $this->getCurrentPageId();
		return $this->data[$id];
	}


	/*
	 * Return le nombre d'item de la page courante
	 */
	public function count()
	{
		return 1;
	}

	/*
	 * réinitialise au début de la page courante.
	 */
	public function rewind()
	{
		$this->pointer = $this->current;
	}
  
	public function current()
	{
		return $this->data[$this->pointer];
	}

	/*
	 * Retourne la clef relativement à la page courante
	 */
	public function key()
	{
		return $this->pointer;
	}

	public function next()
	{
		$this->pointer = null;
	}

	public function valid()
	{
		return array_key_exists($this->pointer, $this->data);
	}
}

