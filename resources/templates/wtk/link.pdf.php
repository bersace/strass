<?php
$pdf = Pool::get ('fpdf');
$this->label->output ('pdf');
$pdf->SetTextColor (0, 0, 127);
$pdf->SetDrawColor (0, 0, 127);
$label = "(".$href.")";
$pdf->Cell ($pdf->GetStringWidth ($label), 5, $label, "B", 0, "", 0, $href);
$pdf->SetTextColor (0);
?>