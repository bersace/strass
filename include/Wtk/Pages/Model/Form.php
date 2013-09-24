<?php

  /**
   *
   * Ce modèle permet de paginer un formulaire en utilisant un groupe
   * de champs par page.
   *
   * Pour connaître si le formulaire a été complété, il faut utilise
   * la méthode isValid() du modèle de la **pagination**.
   *
   * Pour la rendu, il faut étendre Wtk_Pages_Rendrer_Form et
   * implémenter les fonction renderNomdegroupe($instance-groupe,
   * $formulaire). Il est donc impératif de ne pas utiliser de tirer
   * dans l'identifiant des groupes de champs du modèle de formulaire.
   */

class Wtk_Pages_Model_Form extends Wtk_Pages_Model
{
	protected	$root;
	protected	$loop;

	function __construct(Wtk_Form_Model $model, $current = null)
	{
		parent::__construct($model, 1, $current); // un groupe par page
		$this->root			= $model->getInstance();
		$this->pages_count		= $this->root->count() - 1; // minus $$validated$$

		foreach($this->root as $group)
			if ($group instanceof Wtk_Form_Model_Instance_Group)
				array_push($this->pages_id, $group->id);

		// avancer d'un groupe.
		$model->addString('$$current$$', 'Current group', null);
		$model->validate();
		$current = $this->root->get('$$current$$');

		if (!$current)
			$current = reset($this->pages_id);
		else {
			// suppression des fausses erreurs 
			foreach($model->errors as $i => $error) {
				$p = $error->getInstance()->path;
				if (strpos($p, $model->id."/".$current) === false)
					unset($model->errors[$i]);
			}

			// s'il y a des erreurs dans la première
			// étape, ne pas passer à la suivante.
			if (!count($model->errors)) {
				$pks = array_flip($this->pages_id);
				$i = $pks[$current]+1;
				if (isset($this->pages_id[$i]))
					$current = $this->pages_id[$i];
				else
					$current = '';
			}
		}

		$this->current = $current;
		$i = $this->root->getChild('$$current$$');
		$i->set($current);
		// on a besoin de $$current$$ uniquement dans cette fonction.
		if (isset($_POST[$model->id]))
			$_POST[$model->id]['$$current$$'] = $current; // beuaark;

		// le formulaire n'est valide que si la dernière étape est passée
		$model->addConstraintEqual($i, '', true); // silent
	}

	function getFormModel()
	{
		return $this->data;
	}

	function isValid()
	{
		return $this->current == '';
	}

	function pagesCount()
	{
		return $this->pages_count;
	}

	function getCurrentPageId()
	{
		return $this->current;
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
		$ref = $ref ? $ref : $this->current;
		$r = array_flip($this->pages_id);
		$i = $r[$ref]+$sens;
		return array_key_exists($i, $this->pages_id) ? $this->pages_id[$i] : null;
	}

	function fetch($id = null)
	{
		$id = $id ? $id : $this->getCurrentPageId();
		return $this->root->getInstance($id);
	}

	/*
	 * Retourne le nombre d'item par page : un groupe = une page. Si
	 * aucune page à afficher, aucun groupe.
	 */
	public function count()
	{
		return $this->current ? 1 : 0;
	}

	/*
	 * réinitialise la boucle.
	 */
	public function rewind()
	{
		$this->loop = true;
	}
  
	// retourne le groupe courant.
	public function current()
	{
		return $this->root->getChild($this->current);
	}

	/*
	 * Retourne la clef relativement à la page courante
	 */
	public function key()
	{
		return $this->current;
	}

	// on arrête la boucle dès la première itération.
	public function next()
	{
		$this->loop = false;
	}

	public function valid()
	{
		return $this->loop;
	}
}

