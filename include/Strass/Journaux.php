<?php

class Journaux extends Zend_Db_Table_Abstract {
	protected $_name = 'journaux';
	protected $_rowClass = 'Journal';
	protected $_dependentTables = array('Rubriques', 'Articles');
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
	function __construct($config)
	{
		parent::__construct($config);
		$this->initResourceAcl();
	}

	protected function _initResourceAcl($acl)
	{
		$u = $this->findParentUnites();
		$acl->allow($u, $this, 'ecrire');       // permettre à toute l'unité de poster
		$acl->allow($u->getSousUnites(), $this, 'ecrire'); // et aux sous-unités
	}

	function getResourceId()
	{
		return 'journal-'.$this->id;
	}

	function __toString()
	{
		return $this->nom;
	}
}

class Rubriques extends Zend_Db_Table_Abstract
{
	protected $_name = 'rubriques';
	protected $_rowClass = 'Rubrique';
	protected $_dependentTables = array('Articles');
	protected $_referenceMap = array('Journal' => array('columns' => 'journal',
							    'refTableClass' => 'Journaux',
							    'refColumns' => 'id',
							    'onUpdate' => self::CASCADE,
							    'onDelete' => self::CASCADE));
}

class Rubrique extends Zend_Db_Table_Row_Abstract       // implements Zend_Acl_Resource_Interface
{
}

class Articles extends Strass_Db_Table_Abstract
{
	protected $_name = 'articles';
	protected $_rowClass = 'Article';
	protected $_referenceMap = array('Journal' => array('columns' => 'journal',
							    'refTableClass' => 'Journaux',
							    'refColumns' => 'id',
							    'onUpdate' => self::CASCADE,
							    'onDelete' => self::CASCADE),
					 'Rubrique' => array('columns' => 'rubrique',
							     'refTableClass' => 'Rubriques',
							     'refColumns' => 'id',
							     'onUpdate' => self::CASCADE,
							     'onDelete' => self::CASCADE),
					 'Auteur' => array('columns' => 'auteur',
							   'refTableClass' => 'Individus',
							   'refColumns' => 'id',
							   'onUpdate' => self::CASCADE,
							   'onDelete' => self::SET_NULL));

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

	function _initResourceAcl($acl)
	{
		// permettre à l'auteur d'éditer ou de supprimer son
		// article (pas de le publier, ça relève du poste dans
		// l'unité).
		$acl->allow($this->findParentIndividus(), $this,
			    array('editer', 'supprimer'));
	}

	function getResourceId()
	{
		return 'article-'.$this->journal.'-'.$this->rubrique.'-'.$this->id;
	}

	function getDossier($data = null) {
		$data = $data ? $data : $this->_data;
		return 'data/journaux/'.$data['journal'].'/'.
			strftime('%Y-%m-%d', strtotime($data['date'])).'/'.$data['id'].'/';
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

