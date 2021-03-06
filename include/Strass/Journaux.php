<?php

require_once 'Strass/Commentaires.php';

class Journaux extends Strass_Db_Table_Abstract
{
    protected $_name = 'journal';
    protected $_rowClass = 'Journal';
    protected $_referenceMap = array(
        'Unite' => array(
            'columns' => 'unite',
            'refTableClass' => 'Unites',
            'refColumns' => 'id',
            'onUpdate' => self::CASCADE,
            'onDelete' => self::CASCADE));
}

class Journal extends Strass_Db_Table_Row_Abstract implements Zend_Acl_Resource_Interface
{
    protected $_privileges = array(
        array('chef',		null),
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
               ->where('article.journal = ?', $this->id);
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
    protected $_referenceMap = array(
        'Articles' => array(
            'columns' => 'article',
            'refTableClass' => 'Articles',
            'refColumns' => 'id',
            'onUpdate' => self::CASCADE,
            'onDelete' => self::CASCADE));
}


class Articles extends Strass_Db_Table_Abstract
{
    protected $_name = 'article';
    protected $_rowClass = 'Article';
    protected $_dependentTables = array('Etiquettes', 'DocsArticle');
    protected $_referenceMap = array(
        'Journal' => array(
            'columns' => 'journal',
            'refTableClass' => 'Journaux',
            'refColumns' => 'id',
            'onUpdate' => self::CASCADE,
            'onDelete' => self::CASCADE),
        'Description' => array(
            'columns' => 'commentaires',
            'refTableClass' => 'Commentaires',
            'refColumns' => 'id',
            'onUpdate' => self::CASCADE,
            'onDelete' => self::CASCADE));

    function fetchAll($where=NULL, $order=NULL, $count=NULL, $offset=NULL)
    {
        $args = func_get_args();
        if ($args && $args[0] instanceof Zend_Db_Table_Select) {
            $args[0] = clone $args[0];
            $this->_ordonner($args[0]);
        }
        return call_user_func_array(array('parent', 'fetchAll'), $args);
    }

    protected function _ordonner($select)
    {
        $select->distinct()
               ->join(
                   array('strass_article_ordre' => 'commentaire'),
                   'strass_article_ordre.id = article.commentaires'."\n",
                   array())
               ->order('strass_article_ordre.date DESC');
    }
}

class Article extends Strass_Db_Table_Row_Abstract implements Zend_Acl_Resource_Interface
{
    protected $_tableClass = 'Articles';
    protected $_privileges = array(
        array('chef', NULL),
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
            $acl->allow(
                $auteur, $this, array('editer', 'supprimer'));

        if ($this->public)
            $acl->allow(null, $this, 'voir');
    }

    function findDocument()
    {
        $t = new Documents;
        $db = $t->getAdapter();
        $s = $t->select()
               ->setIntegrityCheck(false)
               ->distinct()
               ->from('document')
               ->join('article_document', 'article_document.document = document.id', array())
               ->where('article_document.article = ?', $this->id)
               ->limit(1);

        return $t->fetchOne($s);
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
            mkdir($dossier, 0755, true);

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
        else if ($generate)
            return $this->article;
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
        return $this->titre;
    }


    function _postUpdate()
    {
        $from = $this->getDossier($this->_cleanData);
        $to = $this->getDossier();
        if ($from != $to && is_readable($from)) {
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
            $path = $dossier.'/'.$fichier;
            if (!@unlink($path)) {
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

class DocsArticle extends Strass_Db_Table_Abstract
{
    protected $_name = 'article_document';
    protected $_rowClass = 'DocArticle';
    protected $_referenceMap = array(
        'Document' => array(
            'columns' => 'document',
            'refTableClass' => 'Documents',
            'refColumns' => 'id',
            'onUpdate' => self::CASCADE,
            'onDelete' => self::CASCADE),
        'Article' => array(
            'columns' => 'article',
            'refTableClass' => 'Articles',
            'refColumns' => 'id',
            'onUpdate' => self::CASCADE,
            'onDelete' => self::CASCADE));
}

class DocArticle extends Strass_Db_Table_Row_Abstract
{
    protected $_tableClass = 'DocsArticle';
}
