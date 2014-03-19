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
	throw new Strass_Controller_Action_Exception_Notice("Article ".$slug." inconnu");
      else
	return null;
    }

    $this->_actionController->metas(array('DC.Title' => $article->titre));
    $this->setBranche($article);

    return $article;
  }

  function setBranche($article)
  {
    $this->_actionController->_helper->Journal->setBranche($article->findParentJournaux());

    $this->_actionController->branche->append($article->titre,
					      array('controller'=> 'journaux',
						    'action'	=> 'consulter',
						    'article'	=> $article->slug),
						array(),
						true);
  }
}
