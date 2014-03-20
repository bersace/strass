<?php

class Strass_Vignette {
  static function charger($src, $dst, $flatten=false)
  {
    if (file_exists($dst))
      unlink($dst);

    $dossier = dirname($dst);
    if (!file_exists($dossier))
      mkdir($dossier, 0700, true);

    if ($src instanceof Imagick)
      $image = $src;
    else {
      $image = new Imagick;
      $image->setBackgroundColor(new ImagickPixel('transparent'));
      $image->readImage($src);
      if ($flatten) {
	$photo = $image;
	$image = new Imagick;
	$image->newImage($photo->getImageWidth(), $photo->getImageHeight(), "white");
	$image->compositeImage($photo, Imagick::COMPOSITE_OVER, 0, 0);
      }
    }

    return $image;
  }

  static function estGrande($image)
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

    if ($MAX = self::estGrande($image))
      $image->scaleImage($MAX, $MAX, true);

    $image->setImageFormat(pathinfo($dst, PATHINFO_EXTENSION));
    $image->writeImage($dst);
  }

  static function decouper($src, $dst)
  {
    $config = Zend_Registry::get('config');
    $image = self::charger($src, $dst);

    if ($MAX = self::estGrande($image))
      $image->cropThumbnailImage($MAX, $MAX);

    $format = pathinfo($dst, PATHINFO_EXTENSION);
    if ($format == 'jpeg') {
      $image->setImageCompression(Imagick::COMPRESSION_JPEG);
      $image->setImageCompressionQuality($config->get('photo/qualite', 85));
    }

    $image->setImageFormat($format);
    $image->writeImage($dst);
  }
}
