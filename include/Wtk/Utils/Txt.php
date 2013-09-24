<?php

function wtk_txt_data($child, $indent, $width)
{
	$child->addData(array('_indent'	=> $indent,
			      '_width'	=> $width));
}

function wtk_txt_children_data($parent, $indent, $width)
{
	foreach($parent as $child)
		wtk_txt_data($child, $indent, $width);
}

function wtk_txt_width($text)
{
	return max(array_map('mb_strlen', explode("\n", $text)));
}

function wtk_txt_wrap($text, $w, $i = '')
{
	return $i.trim(str_replace("\n", "\n".$i, wordwrap($text, $w, "\n", false)))."\n";
}

function wtk_txt_height($text)
{
	return count(explode("\n", $text));
}

function wtk_txt_center($text, $width, $pad = false)
{
	$lines = explode("\n", $text);
	foreach($lines as $i => $line) {
		$il = intval(($width - mb_strlen($line)) / 2);
		if ($il) {
			$indent = implode('', array_fill(0, $il, ' '));
		}
		else {
			$indent = '';
		}
		$lines[$i] = $indent.$line;
		if ($pad) {
			$lines[$i] = wtk_txt_pad($lines[$i], $width);
		}
	}
	return implode("\n", $lines);
}

function wtk_txt_pad($text, $width, $pad = ' ')
{
	$text = $text;
	$len = mb_strlen($text);
	for($i = $len; $i < $width; $i++) {
		$text.= $pad;
	}
	return $text;
}