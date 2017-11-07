<?php

require_once 'Strass/Activites.php';
require_once 'Strass/Commentaires.php';

class Photos extends Strass_Db_Table_Abstract
{
    protected $_name		= 'photo';
    protected $_rowClass		= 'Photo';
    protected $_referenceMap	= array(
        'Activite' => array(
            'columns'		=> 'activite',
            'refTableClass'	=> 'Activites',
            'refColumns'	=> 'id',
            'onUpdate'		=> self::CASCADE,
            'onDelete'		=> self::CASCADE),
        'Description' => array(
            'columns' => 'commentaires',
            'refTableClass' => 'Commentaires',
            'refColumns' => 'id',
            'onUpdate' => self::CASCADE,
            'onDelete' => self::CASCADE));
    protected $_dependentTables = array('Identifications');
}

class Photo extends Strass_Db_Table_Row_Abstract implements Zend_Acl_Resource_Interface
{
    protected $_tableClass = 'Photos';
    protected $_privileges	= array(
        array('chef',		NULL),
        array('assistant',	NULL),
        array(NULL,		'commenter'));

    function getResourceId()
    {
        return 'photo-'.$this->slug;
    }

    function initAclResource($acl)
    {
        $acl->add(new Zend_Acl_Resource($this->getResourceId()));
        $this->initPrivileges($acl, $this->findUnites());
    }

    function findParentActivites()
    {
        $cache = Strass_Db_Table_Abstract::$_rowCache;
        $id = 'activites-'.$this->activite;
        if (($a = $cache->load($id)) === false) {
            $a = parent::__call('findParentActivites', array());
            $cache->save($a, $id);
        }
        return $a;
    }

    function findUnites()
    {
        $t = new Unites;
        $s = $t->select()
               ->setIntegrityCheck(false)
               ->from('unite')
               ->join('participation', 'participation.unite = unite.id', array())
               ->where('participation.activite = ?', $this->activite);
        return $t->fetchAll($s);
    }

    protected function getChemin($data = null)
    {
        if (is_null($data)) {
            $a = $this->findParentActivites();
        }
        else {
            $ta = new Activites;
            $a = $ta->findOne($data['activite']);
        }
        $d = $a->getDossierPhoto();

        return $d.'/'.($data ? $data['slug'] : $this->slug);
    }

    protected function formatURL($path)
    {
        return $path . '?' . substr($this->date, 0, 10);
    }

    function getCheminImage($data = null)
    {
        return $this->getChemin($data).'.jpeg';
    }

    function getURLImage()
    {
        return $this->formatURL($this->getCheminImage());
    }

    function getCheminVignette($data = null)
    {
        return $this->getChemin($data).'-vignette.jpeg';
    }

    function getURLVignette()
    {
        return $this->formatURL($this->getCheminVignette());
    }

    function getDescription()
    {
        return $this->findParentCommentaires()->message;
    }

    function storeFile($path)
    {
        $activite = $this->findParentActivites();

        /* date */
        $exif = @exif_read_data($path);
        if ($exif && array_key_exists('DateTimeOriginal', $exif)) {
            preg_match(
                "`(\d{4})[:-](\d{2})[:-](\d{2}) (\d{2}):(\d{2}):(\d{2})`",
                $exif['DateTimeOriginal'], $match);
            $this->date = $match[1].'-'.$match[2].'-'.$match[3].' '.
                $match[4].':'.$match[5].':'.$match[6];
        }
        else
            $this->date = $activite->fin;

        $dossier = $activite->getDossierPhoto();
        if (!file_exists($dossier))
            mkdir($dossier, 0755, true);

        $suffixe = '.jpeg';
        $vignette = $dossier.'/'.$this->slug.'-vignette'.$suffixe;
        $fichier = $dossier.'/'.$this->slug.$suffixe;

        $config = Zend_Registry::get('config');

        $photo = Strass_Vignette::charger($path, $fichier, true);
        $width = $photo->getWidth();
        $height = $photo->getHeight();
        $MAX = $config->get('photo/taille', 2048);
        if (min($width, $height) > $MAX)
            $photo->scale($MAX, $MAX);
        $photo->ecrire();

        Strass_Vignette::decouper($photo, $vignette);

        $this->save();
    }

    function findCommentaires()
    {
        $t = new Commentaires;
        $parent = $t->findOne($this->commentaires);
        return $parent->findCommentaires();
    }

    function findCommentaire($individu)
    {
        $t = new Commentaires;
        $s = $t->select()
               ->setIntegrityCheck(false)
               ->distinct()
               ->from('commentaire')
               ->where('parent = ?', $this->commentaires)
               ->where('auteur = ?', $individu->id);
        return $t->fetchOne($s);
    }

    function __toString()
    {
        return $this->titre;
    }

    function clearCache()
    {
        $cache = Zend_Registry::get('cache');
        $cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, array('photos'));
    }

    function _postInsert()
    {
        $this->clearCache();
    }

    function _postUpdate()
    {
        $this->clearCache();

        $activite = $this->findParentActivites();
        $dossier = $activite->getDossierPhoto();
        if (!file_exists($dossier))
            mkdir($dossier, 0755, true);

        rename($this->getCheminImage($this->_cleanData), $this->getCheminImage());
        rename($this->getCheminVignette($this->_cleanData), $this->getCheminVignette());
    }

    function _postDelete()
    {
        $this->clearCache();
        unlink($this->getCheminVignette());
        unlink($this->getCheminImage());
    }
}


class Identifications extends Strass_Db_Table_Abstract
{
    protected $_name		= 'photo_identification';
    protected $_rowClass		= 'Identification';
    protected $_referenceMap	= array(
        'Photo' => array(
            'columns'		=> 'photo',
            'refTableClass'	=> 'Photos',
            'refColumns'	=> 'id',
            'onUpdate'		=> self::CASCADE,
            'onDelete'		=> self::CASCADE),
        'Unite' => array(
            'columns' => 'unite',
            'refTableClass' => 'Unites',
            'refColumns' => 'id',
            'onUpdate' => self::CASCADE,
            'onDelete' => self::CASCADE));
}

class Identification extends Strass_Db_Table_Row_Abstract
{
    protected $_tableClass = 'Identifications';
}