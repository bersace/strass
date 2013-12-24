<?php

class Strass_Migrate_To3 extends Strass_MigrateHandler {
  function online($db) {
    $db->exec("ALTER TABLE citations RENAME TO citation;");
  }
}