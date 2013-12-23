<?php

class Strass_Format_Atom extends Strass_Format_Feed
{
	protected	$_suffix	= 'atom';
	protected	$_title		= 'Flux Atom';
	protected	$_mimeType	= 'application/atom+xml';
	protected	$_feedClass	= 'Atom';
}