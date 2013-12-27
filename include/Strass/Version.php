<?php

final class Strass_Version {
  const VERSION = 8;

  static $filename = 'private/STRASS_VERSION';

  static function current() {
    if (file_exists(Strass_Version::$filename)) {
      return (int) trim(@file_get_contents('private/STRASS_VERSION'));
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
    file_put_contents(Strass_Version::$filename, (string) $version);
  }
}
