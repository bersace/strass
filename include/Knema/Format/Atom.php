<?php

class Knema_Format_Atom extends Knema_Format_Feed
{
	protected	$_suffix	= 'atom';
	protected	$_title		= 'Flux Atom';
	protected	$_mimeType	= 'application/atom+xml';
	protected	$_feedClass	= 'Atom';
}