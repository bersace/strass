<?php

class Strass_Vignette {
  static function reduire($src, $dst)
  {
    $config = Zend_Registry::get('config');

    if (file_exists($dst))
      unlink($dst);

    $dossier = dirname($dst);
    if (!file_exists($dossier))
      mkdir($dossier, 0700, true);

    $image = new Imagick;
    $image->setBackgroundColor(new ImagickPixel('transparent'));
    $image->readImage($src);
    $width = $image->getImageWidth();
    $height = $image->getImageHeight();

    $MAX = $config->get('photo/taille_vignette', 256);
    if (min($width, $height) > $MAX)
      $image->scaleImage($MAX, $MAX, true);

    $image->setImageFormat('png');
    $image->writeImage($dst);
  }
}
