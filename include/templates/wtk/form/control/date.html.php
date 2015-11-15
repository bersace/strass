<?php

$items = array ();
$var = false;
$sep = '';

$len = strlen ($format);
for ($i = 0; $i < $len; $i++) {
  $char = $format{$i};

  switch ($char) {
  case '%':
    $var = true;
    array_push ($items, $sep);
    $sep = '';
    break;
  default:
    if ($var) {
      switch ($char) {
      case 'Y':
	array_push ($items, 'year');
	break;
      case 'm':
	array_push ($items, 'month');
	break;
      case 'd':
      case 'e':
	array_push ($items, 'day');
	break;
      case 'H':
	array_push ($items, 'hour');
	break;
      case 'M':
	array_push ($items, 'min');
	break;
      case 'S':
	array_push ($items, 'sec');
	break;
      default:
	break;
      }
      $var = false;
    }
    else {
      $sep.= $char;
    }
  }
}
if ($sep) {
    array_push($items, $sep);
}

$minvalues = array(
    'month' => 1,
    'day' => 1,
    'hour' => 0,
    'min' => 0,
    'sec' => 0,
);

$maxvalues = array(
    'month' => 12,
    'day' => 31,
    'hour' => 23,
    'min' => 59,
    'sec' => 60,
);
?>
<?php foreach ($items as $item): ?>
<?php if ($item): ?>
<?php
switch ($item) {
 case 'year':
   echo "<input size=\"5\" maxlength=\"4\"";
   wtk_classes($flags, $item);
   wtk_attr ('name', $name.'[year]');
   wtk_attr ('value', $year);
   wtk_attr ('type', 'number');
   echo " />";
   break;
 case 'month':
 case 'day':
 case 'hour':
 case 'min':
 case 'sec':
   echo "<input size=\"2\" maxlength=\"2\"";
   wtk_classes($flags, $item);
   wtk_attr('name', $name.'['.$item.']');
   wtk_attr('value', $$item);
   wtk_attr('type', 'number');
   wtk_attr('min', $minvalues[$item]);
   wtk_attr('max', $maxvalues[$item]);
   echo " />";
   break;
 default:
   echo "<span>".$item."</span>\n";
   break;
}
?>
<?php endif; ?>
<?php endforeach; ?>
