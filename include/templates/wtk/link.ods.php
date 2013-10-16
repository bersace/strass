<?php
if ($metas->label)
	$_context->table->put($_context->x, $_context->y, new Dio_Text_A($metas->label, wtk_abs_href($href)));