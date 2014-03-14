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
				 array('assistant',	null),
				 array('membre', 'ecrire'));

  function getResourceId()
  {
    return 'journal-'.$this->slug;
  }

  function initAclResource(&$acl)
  {
    $acl->add(new Zend_Acl_Resource($this->getResourceId()));
    $u = $this->findParentUnites();
    $this->initPrivileges($acl, array($u));
    // permettre à un scout d'écrire dans le blog de sa troupe
    foreach($u->findSousUnites() as $u)
      $acl->allow($u->getRoleId('membre'), $this, 'ecrire');
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
      ->order('commentaire.date DESC');
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
  protected $_name = 'article_etiquette';
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

  function getResourceId()
  {
    return 'article-'.$this->slug;
  }

  function initAclResource(&$acl)
  {
    $acl->add(new Zend_Acl_Resource($this->getResourceId()));
    $this->initPrivileges($acl, array($this->findUnite()));
    // permettre à l'auteur d'éditer ou de supprimer son
    // article (pas de le publier, ça relève du poste dans
    // l'unité).
    $auteur = $this->findAuteur();
    if ($acl->hasRole($auteur))
      $acl->allow($auteur, $this,
		  array('editer', 'supprimer'));
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
	$images[] = $fichier;
      }
    }
    return array_filter($images);
  }

  function getDate()
  {
    return $this->findParentCommentaires()->date;
  }

  function getBoulet($generate=false)
  {
    if ($this->boulet)
      return $this->boulet;
    elseif ($generate)
      return wtk_first_lines($this->article);
    else
      return null;
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

  function findUnite()
  {
    $t = new Unites;
    $s = $t->select()
      ->setIntegrityCheck(false)
      ->from('unite')
      ->join('journal', 'journal.unite = unite.id', array())
      ->where('journal.id = ?', $this->journal);
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
      if (!file_exists($fichier))
	continue;

      if (!@unlink($fichier)) {
	throw new Exception("Impossible de supprimer le fichier ".
			    $fichier);
      }
    }
    if (file_exists($dossier)) {
      if (!@rmdir($dossier)) {
	throw new Exception("Impossible de supprimer le dossier ".
			    $dossier);
      }
    }
  }
}
