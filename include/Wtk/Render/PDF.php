<?php

require_once 'fpdf/fpdf.php';

class Wtk_Render_PDF extends Wtk_Render
{

  protected	$template =	'pdf';
  protected	$mime = 	'application/pdf';

  function render ()
  {
    date_default_timezone_set ("Europe/Paris");
    $pdf = new FPDF ();
    $pdf->SetCompression (false);
    Pool::set ('fpdf', $pdf);
    parent::render ();
    return $pdf;
  }

  function output ($filename = NULL)
  {
    $pdf = $this->render ();

    if ($filename) {
      $pdf->Output ($filename, 'F');
    }
    else {
      $this->header ();
      $pdf->Output ('test.pdf', 'i');
    }
  }

//   function header ()
//   {
//     header ("Content-Disposition: attachment; filename=test.pdf");
//     parent::header ();
//   }
}