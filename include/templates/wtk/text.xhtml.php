<?php if ($preformated): ?><!-- -*- SGML -*- -->
<pre>
<?php echo $text; ?>
</pre>
<?php else: ?>
<?php echo $tw->transform ($text, 'Xhtml'); ?>
<?php endif; ?>
