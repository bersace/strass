<?php

final class Strass_Version {
  const PROJET = '2.0dev';
  const DATA = 16;

  static $version_filename = 'private/STRASS_VERSION';

  static function dataCurrent() {
    /* Jusqu'en version 15 */
    if (file_exists(self::$version_filename)) {
      return intval(trim(@file_get_contents(self::$version_filename)));
    }
    else if (file_exists(Strass::getRoot().self::$version_filename)) {
      return intval(trim(@file_get_contents(Strass::getRoot().self::$version_filename)));
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
    file_put_contents(Strass::getRoot().self::$version_filename, (string) $version);
  }
}
