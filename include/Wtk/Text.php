<?php

require_once 'Text/Wiki.php';

class Wtk_Text extends Wtk_Element
{
  static protected $const_tw;

	function __construct ($text = '', $preformated = false)
	{
		parent::__construct ();
		$this->text		= $text;
		$this->preformated	= $preformated;

		if (!self::$const_tw) {
			self::$const_tw = $tw = new Text_Wiki;
			// Désactivation de certaine fonctionnalité peu sécurisé ou utiles dans
			// le cadre d'un wiki uniquement.
			$disable = array('phplookup', 'interwiki', 'wikilink', 'freelink', 'bold', 'italic',
					 'embed', 'include', 'toc');
			foreach($disable as $rule)
				$tw->disableRule($rule);

			$enable = array('code', 'translatehtml');
			foreach($enable as $rule)
				$tw->enableRule($rule);

			// Ajouter la gestion des url relative.
			$options = array('/', './', '../',
					 'http://', 'https://', 'ftp://',
					 'gopher://', 'news://', 'file://',
					 'irc://', 'mailto:', 'xmpp:', 'tel:',
					 );
			$tw->setParseConf('Url', 'schemes', $options);
			//$tw->setFormatConf('Xhtml', 'charset', 'utf-8');
			$tw->setFormatConf('Xhtml', 'translate', HTML_SPECIALCHARS);
			$tw->setRenderConf('Xhtml', 'image', 'base', './');
		}

		$this->tw = clone(self::$const_tw);
	}

	function setText ($text)
	{
		$this->text = $text;
	}

	function append($text)
	{
		$this->text.= $text;
	}

	function getTextWiki()
	{
		return $this->tw;
	}
}
