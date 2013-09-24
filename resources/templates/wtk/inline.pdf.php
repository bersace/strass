<?php
$pdf = Pool::get ('fpdf');
$pdf->Cell ($pdf->GetStringWidth ($text), 5, $text);
?>
