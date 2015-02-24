<?php

class Strass_Migrate_To16 extends Strass_MigrateHandler {
  function offline()
  {
    $root = Strass::getRoot();
    if ($root != 'data/')
      rename("data/", $root);

    /* le dossier est déjà recréé vide par le migrateur… */
    self::rrmdir($root."private/");
    rename("private/", $root."private/");

    if (file_exists('maint/strass.conf'))
      rename("maint/strass.conf", $root.'strass-remote.conf');
  }
}
