<?php
$pdf = Pool::get ('fpdf');

$pdf->SetFont ('', 'B', 12);
$this->title->output ('pdf');
$pdf->Ln ();

$margin = $pdf->GetX ();
$pdf->SetLeftMargin ($margin + 4);
$pdf->setFont ('', '', 10);
$this->content->output ('pdf');
$pdf->SetLeftMargin ($margin);
?>
