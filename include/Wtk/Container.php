<?php

class Wtk_Container extends Wtk_Element implements Wtk_Container_Interface, Iterator, Countable {
	protected $children;

	function __construct($child0 = null)
	{
		parent::__construct();
		$this->children = array();

		$children = is_array($child0) ? $child0 : func_get_args();
		foreach($children as $child)
			$this->addChild($child);
	}

	function addChild($element, $id = NULL) {
		if (is_string ($element))
			$element = new Wtk_RawText($element);

		if ($element instanceof Wtk_Element) {
			if ($id)
				$this->children[$id] = $element;
			else
				array_push($this->children, $element);

			$element->parent($this);
		}
		return $element;
	}

	function removeChild($el)
	{
		foreach($this->children as $id => $child) {
			// recherche l'objet dans les enfants.
			if (serialize($child) == serialize($el)) {
				$child->unparent();
				unset($this->children[$id]);
				return;
			}
		}
		throw new Exception("Unable to remove child");
	}

	function addChildren($element = NULL) {
		$elements = func_get_args();
		foreach($elements as $element)
			$this->addChild($element);
	}

	/**
	 * la fonction add<nom-de-classe>($args) ajoute un nouvel
	 * objet de type Wtk_<nom-de-classe> en passant $args au
	 * constructeur.
	 */
	public function __call($method, $args)
	{
		if (preg_match('`add(.+)`', $method, $match)) {
			$wid = wtk_new('Wtk_'.$match[1], $args);
			return $this->addChild($wid);
		}
	}

	protected function _finalize()
	{
		$this->_finalizeChildren();
	}

	protected function _finalizeChildren()
	{
		foreach($this->children as $child)
			$child->_finalize();
	}

	function template()
	{
		return $this->containerTemplate(get_class($this));
	}

	/**
	 * Génère un template de conteneur de classe $class et en y
	 * ajoutant les templates enfants.
	 */
	protected function containerTemplate($class = __CLASS__) {
		$tpl = $this->elementTemplate($class);
		$this->addChildrenTemplate($tpl);
		return $tpl;
	}

	/**
	 * Ajoute les templates des enfant de ce conteneur au template $tpl;
	 */
	protected function addChildrenTemplate($tpl)
	{
		foreach($this->children as $id => $child) {
			$id = is_string($id) ? $id : null;
			$child_tpl = $child->template();
			$tpl->addChild($id, $child_tpl);
		}
	}

	/**
	 * retourne la liste récursive des composants de style du
	 * conteneur et de ses enfants.
	 */
	function getStyleComponents()
	{
		$cmp = array($this->getStyleComponent());
		foreach($this->children as $child) {
			if ($child instanceof Wtk_Container_Interface)
				$cmp = array_merge($cmp, $child->getStyleComponents());
			else
				array_push($cmp, $child->getStyleComponent());
		}

		return $cmp;
	}

	/**
	 * Retourne la liste récursive des scripts du conteneur et de
	 * ses enfants.
	 */
	function getDojoType()
	{
		$djts = array($this->dojoType);
		foreach($this->children as $child) {
			$djts = array_merge($djts, (array) $child->getDojoType());
		}
		return $djts;
	}


	// ITERATOR, COUNTABLE
	public function count()
	{
		return count($this->children);
	}
	public function rewind()
	{
		return reset($this->children);
	}
	public function current()
	{
		return current($this->children);
	}
	public function key()
	{
		return key($this->children);
	}
	public function next()
	{
		return next($this->children);
	}
	public function valid()
	{
		return $this->current() !== false;
	}

	function __toString()
	{
		$str = "";
		foreach($this as $child)
			$str.= (string) $child;
		return $str;
	}
  }
