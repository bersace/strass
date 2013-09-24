<?php

$_w = intval($_width/$cols);

$largeurs = array_fill(0, $cols, 0);

// tenir compte de la largeur des entêtes.
if ($show_header) {
  $j = 0;
  foreach($columns as $col) {
    $largeurs[$j] = mb_strlen($col->getTitle());
    $j++;
  }
 }

$hauteurs = array_fill(0, $rows, 0);

// récupérer les largeur et hauteur des cellules.
for($i = 0; $i < $rows; $i++) {
  for ($j = 0; $j < $cols; $j++) {
    $id = 'cell-'.$i.'-'.$j;
    wtk_txt_data($this->$id, '', $_w);
    $td = $this->$id->render();
    $hauteurs[$i] = max($hauteurs[$i], wtk_txt_height($td));
    $largeurs[$j] = max($largeurs[$j], wtk_txt_width($td));
  }
 }

// AFFICHAGE
ob_start();
if ($show_header) {
  $cells = array();
  $j = 0;
  foreach($columns as $col) {
    $cells[] = wtk_txt_center($col->getTitle(), $largeurs[$j], true);
    $j++;
  }
  echo $_indent.implode(' | ', $cells)."\n";
  echo $_indent.wtk_txt_pad('', array_sum($largeurs) + 3 * (count($largeurs)-1), '—')."\n";
 }


for($i = 0; $i < $rows; $i++) {
  $cells = array();
  for ($j = 0; $j < $cols; $j++) {
    $id = 'cell-'.$i.'-'.$j;
    wtk_txt_data($this->$id, '', $largeurs[$j]);
    $td = $this->$id->render();
    $cells[] = explode("\n", $td);
  }

  for($k = 0; $k < $hauteurs[$i]; $k++) {
    $cs = array();
    for($j = 0; $j < $cols; $j++) {
      $td = isset($cells[$j][$k]) ? $cells[$j][$k] : "";
      $cs[] = wtk_txt_pad($td, $largeurs[$j]);
    }
    echo $_indent.implode(' | ', $cs)."\n";
  }
 }

$table = ob_get_contents();
ob_end_clean();

echo wtk_txt_center($table, $_width);