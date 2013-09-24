<?php
$p = $_context->parent;
$a = $p->appendChild(new Dio_Text_A(null, wtk_abs_href($href)));
$_context->pushParent($a);
wtk_children_context($this, $_context);
$this->outputChildren();
$_context->popParent();