<?php

class Strass_Vignette {
  static function charger($src, $dst, $flatten=false)
  {
    if (file_exists($dst))
      unlink($dst);

    $dossier = dirname($dst);
    if (!file_exists($dossier))
      mkdir($dossier, 0700, true);

    $image = new Imagick;
    $image->setBackgroundColor(new ImagickPixel('transparent'));
    $image->readImage($src);
    if ($flatten) {
      $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_RESET);
      $image->setBackgroundColor('white');
    }

    return $image;
  }

  static protected function _estGrande($image)
  {
    $config = Zend_Registry::get('config');
    $width = $image->getImageWidth();
    $height = $image->getImageHeight();
    $MAX = $config->get('photo/taille_vignette', 256);
    return min($width, $height) > $MAX ? $MAX : null;
  }

  static function reduire($src, $dst, $flatten=false)
  {
    $image = self::charger($src, $dst, $flatten);

    if ($MAX = self::_estGrande($image))
      $image->scaleImage($MAX, $MAX, true);

    $image->setImageFormat('png');
    $image->writeImage($dst);
  }

  static function decouper($src, $dst)
  {
    $image = self::charger($src, $dst);

    if ($MAX = self::_estGrande($image))
      $image->cropThumbnailImage($MAX, $MAX);

    $image->setImageFormat('png');
    $image->writeImage($dst);
  }
}
