<?php

class Strass_Migrate_To5 extends Strass_MigrateHandler {
  function online($db) {
    $db->exec("ALTER TABLE livredor RENAME TO tmp;");
    $db->exec("
CREATE TABLE `livredor` (
  id       INTEGER PRIMARY KEY,
  auteur   CHAR(128),
  adelec   CHAR(128),
  date     CHAR(16),
  public   CHAR(1) DEFAULT NULL,
  message  TEXT    NOT NULL
);");
    $db->exec("
INSERT INTO livredor (auteur, adelec, date, public, message)
SELECT auteur, adelec, date, public, message FROM tmp ORDER BY date;");
    $db->exec("DROP TABLE tmp;");
  }
}
