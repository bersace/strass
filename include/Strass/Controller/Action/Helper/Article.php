<?php

require_once 'Strass/Activites.php';

class Strass_Controller_Action_Helper_Article extends Zend_Controller_Action_Helper_Abstract
{
  function direct($throw = true)
  {
    $articles = new Articles();
    $slug = $this->getRequest()->getParam('article');
    try {
      $article = $articles->findBySlug($slug);
    }
    catch (Strass_Db_Table_NotFound $e) {
      if ($throw)
	throw new Strass_Controller_Action_Exception_NotFound("Article ".$slug." inconnu");
      else
	return null;
    }

    $this->_actionController->metas(array('DC.Title' => $article->titre));
    $this->setBranche($article);

    return $article;
  }

  function setBranche($article)
  {
    $j = $article->findParentJournaux();
    $this->_actionController->_helper->Journal->setBranche($j);

    if (!$article->public)
      $this->_actionController->branche->append('Brouillons',
						array('controller'=> 'journaux',
						      'action'	=> 'brouillons',
						      'journal' => $j->slug),
						array(),
						true);

    $this->_actionController->branche->append($article->titre,
					      array('controller'=> 'journaux',
						    'action'	=> 'consulter',
						    'article'	=> $article->slug),
						array(),
						true);
  }
}
