<?php

class Journaux extends Strass_Db_Table_Abstract
{
  protected $_name = 'journal';
  protected $_rowClass = 'Journal';
  protected $_referenceMap = array('Unite' => array('columns' => 'unite',
						    'refTableClass' => 'Unites',
						    'refColumns' => 'id',
						    'onUpdate' => self::CASCADE,
						    'onDelete' => self::CASCADE));
}

class Journal extends Strass_Db_Table_Row_Abstract implements Zend_Acl_Resource_Interface
{
  protected $_privileges = array(array('chef',		null),
				 array('assistant',	null));

  public function __construct(array $config = array()) {
    parent::__construct($config);

    $this->initResourceAcl();
  }

  protected function _initResourceAcl(&$acl)
  {
    $u = $this->findParentUnites();
    $acl->allow($u, $this, 'ecrire');       // permettre à toute l'unité de poster
    $acl->allow($u->findSousUnites(), $this, 'ecrire'); // et aux sous-unités
  }

  function getResourceId()
  {
    return 'journal-'.$this->slug;
  }

  function __toString()
  {
    return $this->nom;
  }

  function getDossier()
  {
    return 'data/journaux/' . $this->slug;
  }

  function selectArticles()
  {
    $t = new Articles;
    $s = $t->select()
      ->setIntegrityCheck(false)
      ->from('article')
      ->join('commentaire', 'commentaire.id = article.commentaires', array())
      ->where('article.journal = ?', $this->id)
      ->order('commentaire.date');
    return $s;
  }

  function findArticles($select = null)
  {
    $t = new Articles;

    if (is_string($select)) {
      $select = $t->select()
	->where($select);
    }

    $select->setIntegrityCheck(false)
      ->from('article')
      ->where('article.journal = ?', $this->id);

    return $t->fetchAll($select);
  }
}


class Etiquettes extends Zend_Db_Table_Abstract
{
  protected $_name = 'article_etiquettes';
  protected $_referenceMap = array('Articles' => array('columns' => 'article',
						       'refTableClass' => 'Articles',
						       'refColumns' => 'id',
						       'onUpdate' => self::CASCADE,
						       'onDelete' => self::CASCADE));
}


class Articles extends Strass_Db_Table_Abstract
{
  protected $_name = 'article';
  protected $_rowClass = 'Article';
  protected $_dependentTables = array('Etiquettes');
  protected $_referenceMap = array('Journal' => array('columns' => 'journal',
						      'refTableClass' => 'Journaux',
						      'refColumns' => 'id',
						      'onUpdate' => self::CASCADE,
						      'onDelete' => self::CASCADE),
				   'Description' => array('columns' => 'commentaires',
							  'refTableClass' => 'Commentaires',
							  'refColumns' => 'id',
							  'onUpdate' => self::CASCADE,
							  'onDelete' => self::CASCADE));

  function fetchAll($where = NULL, $order = NULL, $count = NULL, $offset = NULL)
  {
    $args = func_get_args();
    if ($args && $args[0] instanceof Zend_Db_Table_Select)
      $this->_ordonner($args[0]);
    return call_user_func_array(array('parent', 'fetchAll'), $args);
  }

  protected function _ordonner($select)
  {
    $select->distinct()
      ->join('commentaire', 'commentaire.id = article.commentaires', array())
      ->order('commentaire.date DESC');
  }
}

class Article extends Strass_Db_Table_Row_Abstract implements Zend_Acl_Resource_Interface
{
  protected $_privileges = array(array('chef', NULL),
				 array('assistant', NULL));
  function __construct($config)
  {
    parent::__construct($config);
    $db = $this->getTable()->getAdapter();
    $this->initResourceAcl(array($this->findParentJournaux()->findParentUnites()));
  }

  function _initResourceAcl(&$acl)
  {
    // permettre à l'auteur d'éditer ou de supprimer son
    // article (pas de le publier, ça relève du poste dans
    // l'unité).
    $auteur = $this->findAuteur();
    if ($acl->hasRole($auteur))
      $acl->allow($auteur, $this,
		  array('editer', 'supprimer'));
  }

  function getResourceId()
  {
    return 'article-'.$this->slug;
  }

  function getDossier($data = null)
  {
    $j = $this->findParentJournaux();
    $r = $j->getDossier();
    $data = $data ? $data : $this->_data;
    return $r . '/' . $data['slug'] . '/';
  }

  function storeImage($tmp, $name)
  {
    $dossier = $this->getDossier();
    if (!is_readable($dossier))
      mkdir($dossier, 0750, true);

    $target = $dossier.$name;
    if (!move_uploaded_file($tmp, $target)) {
      throw new Exception("Impossible de récupérer l'image");
    }
  }

  function renameImage($from, $to)
  {
    rename($this->getDossier().$from, $this->getDossier().$to);
  }

  function deleteImage($name)
  {
    $path = $this->getDossier().$name;
    if (file_exists($path))
      unlink($path);
  }

  function getImages()
  {
    $dossier = $this->getDossier();
    $fichiers = (array) @scandir($dossier);
    $images = array();
    foreach($fichiers as $fichier) {
      if ($fichier != '.' && $fichier != '..') {
	$images[] = $dossier.'/'.$fichier;
      }
    }
    return $images;
  }

  function getDate()
  {
    return $this->findParentCommentaires()->date;
  }

  function findAuteur()
  {
    $t = new Individus;
    $s = $t->select()
      ->setIntegrityCheck(false)
      ->from('individu')
      ->join('commentaire', 'commentaire.auteur = individu.id', array())
      ->join('article', 'article.commentaires = commentaire.id', array())
      ->where('article.id = ?', $this->id);

    return $t->fetchOne($s);
  }

  function __toString()
  {
    return wtk_ucfirst($this->titre);
  }


  function _postUpdate()
  {
    $from = $this->getDossier($this->_cleanData);
    $to = $this->getDossier();
    if ($from != $to) {
      if (!is_readable($to))
	mkdir($to, 0755, true);

      if (!rename($from, $to))
	throw new Exception("Impossible de renomer le dossier ".$from.
			    " en ".$to);
    }

    // suppression de dossier vide.
    $p = dirname($from);
    $fs = scandir($p);
    if (count($fs) == 2) {
      rmdir($p);
    }
  }

  function _postDelete()
  {
    $dossier = $this->getDossier();
    $fichiers = $this->getImages();
    foreach($fichiers as $fichier) {
      if (!unlink($fichier)) {
	throw new Exception("Impossible de supprimer le fichier ".
			    $fichier);
      }
    }
    if (file_exists($dossier)) {
      if (!rmdir($dossier)) {
	throw new Exception("Impossible de supprimer le dossier ".
			    $dossier);
      }
    }
  }
}
