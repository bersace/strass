<?php
$pdf = Pool::get ('fpdf');

for ($string = ""; strlen ($string) < $width; $string.= "—");
$pdf->SetXY ($pdf->GetX () + 4, $pdf->GetY () + 1);
$pdf->SetDrawColor (127);
$pdf->Cell ($pdf->GetStringWidth ($string), $height * 4, "", 1, 0, "R");
?>
