<?php

class Strass_Vignette {
  static function charger($src, $dst, $flatten=false)
  {
    if (file_exists($dst))
      unlink($dst);

    $dossier = dirname($dst);
    if (!file_exists($dossier))
      mkdir($dossier, 0755, true);

    if (isset($_ENV['STRASS_IMAGE_BACKEND']))
      $api = $_ENV['STRASS_IMAGE_BACKEND'];
    else {
      if (class_exists('Imagick'))
	$api = 'Imagick';
      else
	$api = 'GD';
    }

    switch($api) {
    case 'GD':
      return new Strass_Vignette_GD($src, $dst, $flatten);
    case 'Imagick':
      return new Strass_Vignette_Imagick($src, $dst, $flatten);
    default:
      throw new Exception("Moteur d'image inconnu : ".$api);
    }
  }

  function __construct($chemin)
  {
    $this->chemin = $chemin;
    $format = pathinfo($this->chemin, PATHINFO_EXTENSION);
    if ($format == 'jpg')
      $format = 'jpeg';
    $this->format = $format;
  }

  function estGrande($key, $default)
  {
    $config = Zend_Registry::get('config');
    $width = $this->getWidth();
    $height = $this->getHeight();
    $MAX = $config->get($key, $default);
    return min($width, $height) > $MAX ? $MAX : null;
  }

  static function reduire($src, $dst, $flatten=false, $key='photo/taille_vignette', $default=256)
  {
    $image = self::charger($src, $dst, $flatten);

    if ($MAX = $image->estGrande($key, $default))
      $image->scale($MAX, $MAX);

    $image->ecrire();
  }

  static function decouper($src, $dst, $key='photo/taille_vignette', $default=256)
  {
    $config = Zend_Registry::get('config');
    $image = self::charger($src, $dst);

    if ($MAX = $image->estGrande($key, $default))
      $image->cropThumbnail($MAX, $MAX);

    $image->ecrire();
  }
}

class Strass_Vignette_Imagick extends Strass_Vignette {
  function __construct($src, $dst, $flatten)
  {
    parent::__construct($dst);

    if ($src instanceof Strass_Vignette_Imagick)
      $image = $src->image;
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

    $this->image = $image;
  }

  function getWidth()
  {
    return $this->image->getImageWidth();
  }

  function getHeight()
  {
    return $this->image->getImageHeight();
  }

  function scale($width, $height)
  {
    $this->image->scaleImage($width, $height, true);
  }

  function cropThumbnail($width, $height)
  {
    $this->image->cropThumbnailImage($width, $height);
  }

  function ecrire()
  {
    $config = Zend_Registry::get('config');
    if ($this->format == 'jpeg') {
      $this->image->setImageCompression(Imagick::COMPRESSION_JPEG);
      $this->image->setImageCompressionQuality($config->get('photo/qualite', 85));
    }

    $this->image->setImageFormat($this->format);
    $this->image->writeImage($this->chemin);
  }
}

class Strass_Vignette_GD extends Strass_Vignette {
  function __construct($src, $dst, $flatten)
  {
    parent::__construct($dst);

    if ($src instanceof self) {
      /* prendre la propriété de la resource */
      $this->orig = imagecreatetruecolor($src->getWidth(), $src->getHeight());
      imagecopy($this->orig, $src->orig, 0, 0, 0, 0, $src->getWidth(), $src->getHeight());
    }
    else {
      if (strpos($src, ']') !== false)
	throw new Exception("Multipage non supporté");

      $format = pathinfo(strtolower($src), PATHINFO_EXTENSION);
      switch($format) {
      case 'jpeg':
      case 'jpg':
	$this->orig = imagecreatefromjpeg($src);
	break;
      case 'png':
	$this->orig = imagecreatefrompng($src);
	break;
      case 'gif':
	$this->orig = imagecreatefromgif($src);
	break;
      default:
	$this->orig = imagecreatefromstring(file_get_contents($src));
      }

      if ($this->orig === false)
	throw new Exception("Impossible de charger le fichier ".$src);

    $this->target = $this->orig;
    }
  }

  function __destruct()
  {
    $destroy_target = $this->orig !== $this->target;
    imagedestroy($this->orig);
    if ($destroy_target)
      imagedestroy($this->target);
  }

  function getWidth()
  {
    return imagesx($this->orig);
  }

  function getHeight()
  {
    return imagesy($this->orig);
  }

  function scale($new_width, $new_height)
  {
    $orig_width = $this->getWidth();
    $x_ratio = $new_width / $orig_width;
    $orig_height = $this->getHeight();
    $y_ratio = $new_height / $orig_height;
    $ratio = min($x_ratio, $y_ratio);

    $new_width = $ratio * $orig_width;
    $new_height = $ratio * $orig_height;

    $this->target = imagecreatetruecolor($new_width, $new_height);
    $bg = imagecolorallocate($this->target, 0, 0, 0);
    imagefill($this->target, 0, 0, $bg);
    imagecolortransparent($this->target, $bg);
    imagecopyresampled($this->target, $this->orig,
				0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
  }

  function cropThumbnail($width, $height)
  {
    $orig_width = $this->getWidth();
    $orig_height = $this->getHeight();
    $x_ratio = $width / $orig_width;
    $y_ratio = $height / $orig_height;
    $ratio = max($x_ratio, $y_ratio);
    /* On se cale sur la dimension qui demande le plus de réduction */
    if ($x_ratio < $y_ratio) {
      $orig_x = intval(($orig_width - $orig_height) / 2);
      $orig_width = $orig_height;
      $orig_y = 0;
    }
    else {
      $orig_x = 0;
      $orig_y = intval(($orig_height - $orig_width) / 2);
      $orig_height = $orig_width;
    }

    $new_width = $orig_width > $width ? $width : $orig_width;
    $new_height = $orig_height > $height ? $height : $orig_height;
    $this->target = imagecreatetruecolor($new_width, $new_height);
    $bg = imagecolorallocate($this->target, 0, 0, 0);
    imagefill($this->target, 0, 0, $bg);
    imagecolortransparent($this->target, $bg);
    imagecopyresampled($this->target, $this->orig,
		       0, 0, $orig_x, $orig_y, $new_width, $new_height, $orig_width, $orig_height);
  }

  function ecrire()
  {
    $config = Zend_Registry::get('config');
    switch($this->format) {
    case 'png':
      $qualite = intval($config->get('photo/qualite', 85) / 10);
      imagepng($this->target, $this->chemin, $qualite);
      break;
    case 'jpeg':
      imagejpeg($this->target, $this->chemin, $config->get('photo/qualite', 85));
      break;
    default:
      throw new Exception("Format ".$this->format." non supporté");
    }
  }
}
