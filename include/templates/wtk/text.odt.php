<?php

$p = $_context->parent;
if (!$p instanceof Dio_Text_P) {
	$p = $p->appendChild(new Dio_Text_P);
}
$p->appendChild($p->ownerDocument->createTextNode($text));