<?php

final class Strass_Version {
  const VERSION = 21;

  static $version_filename = 'private/STRASS_VERSION';
  static $install_filename = 'private/INSTALLED';

  static function isInstalled()
  {
    return file_exists(self::$install_filename);
  }

  static function setInstalled()
  {
    return file_put_contents(self::$install_filename, strftime('%Y-%m-%d %H-%M'));
  }

  static function current() {
    if (file_exists(self::$version_filename)) {
      return (int) trim(@file_get_contents(self::$version_filename));
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
    file_put_contents(self::$version_filename, (string) $version);
  }
}
