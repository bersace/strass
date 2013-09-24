<?php

class Knema_Format_RSS extends Knema_Format_Feed
{
	protected	$_suffix	= 'rss';
	protected	$_title		= 'Flux RSS';
	protected	$_mimeType	= 'application/rss+xml';
	protected	$_feedClass	= 'Rss';
}