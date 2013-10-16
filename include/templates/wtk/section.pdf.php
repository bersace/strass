<?php
$pdf = Pool::get ('fpdf');

$pdf->Ln ();
$pdf->SetFont ('', 'B', 24 - ($level * 2));
$pdf->Cell ($pdf->GetStringWidth ($title), 4, $title, 0, 1);
$pdf->Ln ();

$margin = $pdf->GetX ();
$pdf->SetLeftMargin ($margin + 4);
$pdf->SetX ($margin + 4);
$pdf->SetFont ('', '', 12);
$this->content->output ('pdf');
$pdf->SetLeftMargin ($margin);
?>