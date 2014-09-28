<?php
$pad_count = count($row_path) - 1;
?>
<?php for($i = 0; $i < $pad_count; $i++): ?>
<span class="treecellpad">&nbsp;</span>
<?php endfor; ?>
<span class="treecellnode">&#x25B6;</span>
<?php
$this->outputChildren();
