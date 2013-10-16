<?php
if (!$preformated) {
   $text = $tw->transform ($text, 'Plain');
   $text = wordwrap($text, $_width-(strlen($_indent)), "\n", false);
   $text = $_indent.str_replace("\n", "\n".$_indent, $text);
 }

echo $text;

