<?php

class Strass_Format_RSS extends Strass_Format_Feed
{
	protected	$_suffix	= 'rss';
	protected	$_title		= 'Flux RSS';
	protected	$_mimeType	= 'application/rss+xml';
	protected	$_feedClass	= 'Rss';
}