<a<?php
wtk_id_classes($id, $flags);
wtk_attr('href', $href);
wtk_attr('title', $metas->title);
?>><?php foreach($this as $child) {$child->output();} ?></a>