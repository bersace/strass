<?php

require_once 'Strass/Activites.php';

class Strass_Controller_Action_Helper_Article extends Zend_Controller_Action_Helper_Abstract
{
	function direct($throw = true)
	{
		$articles = new Articles();
		$id = $this->getRequest()->getParam('article');
		$j = $this->getRequest()->getParam('journal');
		$d = $this->getRequest()->getParam('date');
		$article = $articles->find($id, $d, $j)->current();

		if (!$article && $throw)
			throw new Knema_Controller_Action_Exception_Notice("Article  inconnu");

		if ($article) {
			$this->_actionController->branche->append($article->titre,
								  array('controller'	=> 'journaux',
									'action'	=> 'lire',
									'journal'	=> $j,
									'date'		=> $d,
									'article'	=> $id),
								  array(),
								  true);
		}
		return $article;
	}
}
