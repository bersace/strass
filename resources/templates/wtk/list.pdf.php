<?php
$pdf = Pool::get ('fpdf');
$margin = $pdf->GetX ();
foreach ($this->children as $child) {
  $pdf->SetLeftMargin ($margin + 4);
  $pdf->SetX ($margin + 4);
  $pdf->Rect ($pdf->GetX (),
	      $pdf->GetY () + 1.5,
	      1.25, 1.25,
	      "F");
  $pdf->SetLeftMargin ($margin + 6);
  $this->$child->output ('pdf');
  $pdf->Ln ();
}
$pdf->SetLeftMargin ($margin);

?>
