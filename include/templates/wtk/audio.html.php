<audio <?php wtk_id_classes($id, $flags, $dojoType, $tooltip); ?>
<?php if ($controls): ?> controls<?php endif; ?>>
<?php foreach($sources as $src): ?>
  <source <?php wtk_attr('src', $src['url']); wtk_attr('type', $src['type']); ?>>
<?php endforeach; ?>
<?php $this->outputChildren(); ?>
</audio>
