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

  function getCheminVignette($data = null, $test = null)
  {
    $test = $test === null ? (bool) !$data : $test;
    if (!$data) $data = $this->_cleanData;
    $path = 'data/documents/'.$data['slug'].'-vignette.jpeg';
    if ($test && !file_exists($path))
      return null;
    else
      return $path;
  }

  function getFichier($data = null)
  {
    if (!$data) $data = $this->_data;
    return 'data/documents/'.$data['slug'].'.'.$data['suffixe'];
  }

  function getTaille()
  {
    return filesize($this->getFichier());
  }

  function storeFile($tmp)
  {
    $fichier = $this->getFichier($this->_data);
    if (!file_exists($dossier = dirname($fichier)))
	mkdir($dossier, 0750, true);

    if (isset($_ENV['STRASS_UNIT_TEST']))
      $ret = copy($tmp, $fichier);
    else
      $ret = move_uploaded_file($tmp, $fichier);

    if ($ret === false)
      throw new Exception("Impossible de copier le fichier !");

    $vignette = $this->getCheminVignette($this->_data);
    $load = $fichier;
    if ($this->suffixe == 'pdf')
      $load .= '[0]';

    try {
      @Strass_Vignette::reduire($load, $vignette, true);
    }
    catch (Exception $e) {
      /* pas supporté par Imagick */
      error_log("Échec de la vignette de ".$load." : ".$e->getMessage());
    }
  }

  function _postDelete()
  {
    @unlink($this->getFichier());
    @unlink($this->getCheminVignette());
  }

  function _postUpdate()
  {
    rename($this->getFichier($this->_cleanData),
	   $this->getFichier(null, false));
    if ($from = $this->getCheminVignette())
      rename($from, $this->getCheminVignette($this->_data));
  }

  function countLiaisons($tables = null)
  {
    $count = 0;

    if (is_null($tables))
      $tables = $this->getTable()->getDependentTables();
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
