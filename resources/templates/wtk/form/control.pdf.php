<?php
$pdf = Pool::get ('fpdf');

if (isset ($this->caption)) {
  $this->caption->output ('pdf');
}

$pdf->SetX (80);
$this->control->output ('pdf');
$pdf->Ln ();
?>
