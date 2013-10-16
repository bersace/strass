<?php

$c = $_context->parent;
$p = $c->appendChild(new Dio_Text_P);
$_context->pushParent($p);
wtk_children_context($this, $_context);
$this->outputChildren();
$_context->popParent();