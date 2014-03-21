<?php

final class Strass_Version {
  const PROJET = '2.0dev';
  const DATA = 15;

  static $version_filename = 'private/STRASS_VERSION';
  static $install_filename = 'private/INSTALLED';

  static function getRoot()
  {
    if (isset($_ENV['STRASS_ROOT']))
      return $_ENV['STRASS_ROOT'];
    else
      return '';
  }

  static function isInstalled()
  {
    return file_exists(Strass_Version::getRoot().self::$install_filename);
  }

  static function setInstalled()
  {
    return file_put_contents(Strass_Version::getRoot().self::$install_filename, strftime('%Y-%m-%d %H-%M'));
  }

  static function onMaintenance()
  {
    return file_exists('maintenance.html');
  }

  static function showMaintenance()
  {
    header('HTTP/1.0 503 Service Unavailable');
    readfile('maintenance.html');
    exit(0);
  }

  static function onDevelopment()
  {
    return file_exists('devel.php');
  }

  static function dataCurrent() {
    if (file_exists(Strass_Version::getRoot().self::$version_filename)) {
      return intval(trim(@file_get_contents(Strass_Version::getRoot().self::$version_filename)));
    }
    else if (file_exists('config/knema/db.php')) {
      /* Installation non versionnée (morel et suf1520) */
      return 1;
    }
    else {
      /* In principio erat version zero. Rien n'est installé */
      return 0;
    }
  }

  static function save($version) {
    file_put_contents(Strass_Version::getRoot().self::$version_filename, (string) $version);
  }
}
