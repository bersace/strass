<?php

class Documents extends Strass_Db_Table_Abstract {
  protected $_name = 'document';
  protected $_rowClass = 'Document';
  protected $_dependentTables = array('DocsUnite', 'PiecesJointes');
}


class Document extends Strass_Db_Table_Row_Abstract implements Zend_Acl_Resource_Interface
{
  protected $_tableClass = 'Documents';

  public function getResourceId()
  {
    return 'document-'.$this->slug;
  }

  function initAclResource($acl)
  {
    $acl->add(new Zend_Acl_Resource($this->getResourceId()));
    $this->initPrivileges($acl, $this->findUnitesViaDocsUnite());
  }

  public function getUri()
  {
    return '/'.$this->getFichier();
  }

  function getCheminVignette($data = null, $test = true)
  {
    if (!$data) $data = $this->_cleanData;
    $path = 'data/documents/'.$data['slug'].'-vignette.jpeg';
    if ($test && !file_exists($path))
      return null;
    else
      return $path;
  }

  function getFichier($data = null)
  {
    if (!$data) $data = $this->_cleanData;
    return 'data/documents/'.$data['slug'].'.'.$data['suffixe'];
  }

  function storeFile($tmp)
  {
    $config = Zend_Registry::get('config');

    $fichier = $this->getFichier();
    if (!file_exists($dossier = dirname($fichier)))
	mkdir($dossier, 0700, true);

    if (!move_uploaded_file($tmp, $fichier))
      throw new Exception("Impossible de copier le fichier !");

    $vignette = $this->getCheminVignette(null, false);
    $load = $fichier;
    if ($this->suffixe == 'pdf')
      $load .= '[0]';

    try {
      $im = new Imagick($load);
    }
    catch (ImagickException $e) {
      /* pas supporté par Imagick */
      return;
    }
    $im->setImageAlphaChannel(Imagick::ALPHACHANNEL_RESET);
    $im->setImageFormat('png');
    $im->setBackgroundColor('white');
    $MAX = $config->get('photo/taille_vignette', 256);
    $im->thumbnailImage(0, $MAX);
    $im->writeImage($vignette);
  }

  function _postDelete()
  {
    @unlink($this->getFichier());
    @unlink($this->getCheminVignette());
  }

  function _postUpdate()
  {
    rename($this->getFichier($this->_cleanData),
	   $this->getFichier());
    if ($from = $this->getCheminVignette($this->_cleanData))
      rename($from, $this->getCheminVignette());
  }

  function countLiaisons($tables = null)
  {
    $count = 0;

    if (is_null($tables))
      $tables = $this->getTable()->getDependentTablesName();
    if (is_string($tables))
      $tables = array($tables);

    foreach($tables as $tn) {
      $func = 'find'.$tn;
      $count+= call_user_func(array($this, $func))->count();
    }
    return $count;
  }

  function findUnite()
  {
    $t = new Unites;
    $s = $t->select()
      ->setIntegrityCheck(false)
      ->from('unite')
      ->join('unite_document', 'unite_document.unite = unite.id', array())
      ->where('unite_document.document = ?', $this->id);
    return $t->fetchOne($s);
  }
}
