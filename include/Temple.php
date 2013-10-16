<?php
  /**
   * Brief description of the file.
   *
   * PHP version 5.
   *
   * LICENSE: This program is free software; you can redistribute it and/or modify
   * it under the terms of the GNU General Public License as published by
   * the Free Software Foundation; either version 2 of the License, or
   * (at your option) any later version.
   * 
   * This program is distributed in the hope that it will be useful,
   * but WITHOUT ANY WARRANTY; without even the implied warranty of
   * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   * GNU General Public License for more details.
   * 
   * You should have received a copy of the GNU General Public License
   * along with this program; if not, write to the Free Software
   * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
   *
   * @author      Étienne Bersac <bersace03@free.fr>
   * @copyright	© 2005 Étienne Bersac
   * @license	http://www.gnu.org/licenses/gpl.txt GPL
   */

  /**
   * Ce moteur de template a pour but d'être simple. Son but est
   * seulement d'exécuter un fichier PHP dans un contexte de
   * données. Une seule fonctionnalité est implémanté : l'arborescence
   * des templates.
   */
class Temple implements Iterator, Countable
{

	private	$path;
	private	$data;
	private	$_children;
	private $_allChildren;
	public	$conf;

	/**
	 * Assigne les valeur nécéssaire à l'exécution d'un template
	 *
	 * @param   string  Le fichier template.
	 * @param   array   Le contexte des données.
	 */
	function __construct($path, $data = array(), $conf = array(), $children = array())
	{
		$this->path		= $path;
		$this->data		= $data;
		$this->_children	= array();
		$this->_allChildren	= array();

		// Configuration par défaut.
		$default = array();
		$default['format']		= 'xhtml';
		$default['template_dir']	= 'include/templates';

		$this->conf = array_merge($default, $conf);

		$this->addChildren($children);
	}

	/**
	 *
	 */
	function addData ($data)
	{
		$this->data = array_merge ($this->data, $data);
	}

	/**
	 * Ajoute un enfant dans la liste. L'ID d'un enfant est la chaine
	 * par laquelle le templates appelera l'exécution du template
	 * enfant (et non son fichier).
	 *
	 * @param   string  L'ID unique de l'enfant.
	 * @param   string  Le chemin du template de l'enfant.
	 * @param   array   Le contexte de données de l'enfant.
	 * @return  object  L'objet Temple de l'enfant.
	 */
	function addNewChild($id, $path, $data = array(), $conf = array(), $children = array())
	{
		$tpl = get_class($this);
		$child = new $tpl($path, $data, array_merge($this->conf, $conf), $children);
		$this->addChild ($id, $child);
		return $child; 
	}

	/**
	 * Ajouter un objet Temple comme enfant du template
	 * courant. Vous pouvez accedez à tous les enfants d'id nul en
	 * bouclant sur l'objet. Vous devez accéder explicitement aux
	 * enfants identifiés.
	 *
	 * @param	string	Id de l'enfant ou null
	 * @param	object	L'objet enfant.
	 */
	function addChild ($id, $child)
	{
		if ($child instanceof Temple) {
			if ($id == null) {
				$id = 'child'.count($this->_children);
				array_push ($this->_children, $id);
			}

			array_push($this->_allChildren, $id);

			$this->$id = $child;
		}

		return $child;
	}

	function addChildren($children)
	{
		foreach($children as $id => $child)
			$this->addChild((is_string($id) ? $id : null), $child);
	}

	/**
	 * Ce moteur a été conçu dans le but de pouvoir répartir le
	 * template sur plusieurs fichiers. Le template est donc exécuter
	 * dans son propre dossier.
	 *
	 * @return  La sortie.
	 */
	function render($format = null)
	{
		ob_start();
		$res = $this->output($format);
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}

	protected function renderChildren()
	{
		ob_start();
		foreach($this as $child)
			$child->output();
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}


	/**
	 * Affiche directement le rendu du template.
	 */
	function output($format = null)
	{
		$format = $format ? $format : $this->conf['format'];
		foreach($this->_allChildren as $child)
			$this->$child->conf['format'] = $format;

		$file = $this->buildTemplateFilename($this->path, $format);

		if (file_exists($file)) {
			// Importation des datas pour le template. Le template est
			// exécuté dans un context local (de la fonction
			// render()).
			if (is_array ($this->data))
				extract($this->data, EXTR_OVERWRITE);

			// exécution du templates.
			$res = include $file;
		}
		else
			throw new Exception ($file." does not exists !");

		return $res;
	}

	protected function outputChildren()
	{
		foreach($this as $child)
			$child->output();
	}

	/**
	 * Construit le chemin vers le templates. Actuellement, c'est pas
	 * très personnalisable à moins d'étendre la classe pour réécrire
	 * la fonction. Les chemin vers les fichiers templates sont de la
	 * forme templates/<nom du template>.<format>.php.
	 */
	function buildTemplateFilename($path, $format)
	{
		return $this->conf['template_dir'].'/'.$path.'.'.$format.'.php';
	}

	// ITERATOR, COUNTABLE
	public function count()
	{
		return count($this->_children);
	}
  
	public function rewind()
	{
		$id = reset($this->_children);
		return isset($this->$id) ? $this->$id : false;
	}

	public function current()
	{
		$id = current($this->_children);
		return isset($this->$id) ? $this->$id : false;
	}

	public function key()
	{
		return current($this->_children);
	}

	public function next()
	{
		next($this->_children);
	}

	public function valid()
	{
		return $this->current() !== false;
	}
}
