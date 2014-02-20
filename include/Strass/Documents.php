<?php

class Documents extends Strass_Db_Table_Abstract {
  protected $_name = 'document';
  protected $_rowClass = 'Document';
  protected $_dependentTables = array('DocsUnite', 'PiecesJointes');
}


class Document extends Strass_Db_Table_Row_Abstract implements Zend_Acl_Resource_Interface
{
  public function __construct(array $config = array()) {
    parent::__construct($config);
    $this->initResourceAcl($this->findUnitesViaDocsUnite());
  }

  public function getResourceId()
  {
    return 'document-'.$this->slug;
  }

  public function getUri()
  {
    return '/'.$this->getFichier();
  }

  function getCheminVignette($data = null)
  {
    if (!$data) $data = $this->_cleanData;
    return 'data/documents/'.$data['slug'].'-vignette.jpeg';
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

    if ($this->suffixe != 'pdf')
      return;

    $vignette = $this->getCheminVignette();
    $im = new Imagick($fichier . '[0]');
    $im->setImageAlphaChannel(Imagick::ALPHACHANNEL_RESET);
    $im->setImageFormat('png');
    $im->setBackgroundColor('white');
    $MAX = $config->get('photo/taille_vignette', 256);
    $im->thumbnailImage(0, $MAX);
    $im->writeImage($vignette);
  }

  function _postDelete()
  {
    unlink($this->getFichier());
  }

  function _postUpdate()
  {
    rename($this->getFichier($this->_cleanData),
	   $this->getFichier());
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
}
