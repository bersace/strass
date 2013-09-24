<?php
$pdf = Pool::get ('fpdf');
$pdf->SetFont ('', '', 12);
$this->content->output ('pdf');
?>
