<?php

require_once 'Strass/Activites.php';
require_once 'Strass/Commentaires.php';

class Photos extends Strass_Db_Table_Abstract
{
  protected $_name		= 'photo';
  protected $_rowClass		= 'Photo';
  protected $_referenceMap	= array('Activite' => array('columns'		=> 'activite',
							    'refTableClass'	=> 'Activites',
							    'refColumns'	=> 'id',
							    'onUpdate'		=> self::CASCADE,
							    'onDelete'		=> self::CASCADE),
					'Description' => array('columns' => 'commentaires',
							       'refTableClass' => 'Commentaires',
							       'refColumns' => 'id',
							       'onUpdate' => self::CASCADE,
							       'onDelete' => self::CASCADE));
}

class Photo extends Strass_Db_Table_Row_Abstract implements Zend_Acl_Resource_Interface
{
  protected $_privileges	= array(array('chef',		NULL),
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
      $a = parent::findParentActivites();
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

  function getCheminImage($data = null)
  {
    return $this->getChemin($data).'.jpeg';
  }

  function getCheminVignette($data = null)
  {
    return $this->getChemin($data).'-vignette.jpeg';
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
      preg_match("`(\d{4})[:-](\d{2})[:-](\d{2}) (\d{2}):(\d{2}):(\d{2})`",
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
    $fichier = $dossier.'/'.$this->slug.$suffixe;
    $vignette = $dossier.'/'.$this->slug.'-vignette'.$suffixe;

    $config = Zend_Registry::get('config');

    $photo = new Imagick($path);
    $width = $photo->getImageWidth();
    $height = $photo->getImageHeight();

    $image = new Imagick;
    $image->newImage($width, $height, "white", 'jpeg');
    $image->setImageCompression(Imagick::COMPRESSION_JPEG);
    $image->setImageCompressionQuality($config->get('photo/qualite', 85));
    $image->compositeImage($photo, Imagick::COMPOSITE_OVER, 0, 0);

    $MAX = $config->get('photo/taille', 2048);
    if (min($width, $height) > $MAX)
      $image->scaleImage($MAX, $MAX, true);
    $image->writeImage($fichier);

    $MAX = $config->get('photo/taille_vignette', 256);
    if (min($width, $height) > $MAX)
      $image->cropThumbnailImage($MAX, $MAX);
    $image->writeImage($vignette);

    unset($image);

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
    return wtk_ucfirst($this->titre);
  }

  function _postUpdate()
  {
    rename($this->getCheminImage($this->_cleanData), $this->getCheminImage());
    rename($this->getCheminVignette($this->_cleanData), $this->getCheminVignette());
  }

  function _postDelete()
  {
    unlink($this->getCheminVignette());
    unlink($this->getCheminImage());
  }
}
