<?php

class Strass_Migrate_To3 extends Strass_MigrateHandler {
  function online($db) {
    $db->exec("ALTER TABLE citations RENAME TO citation_tmp;");
    $db->exec("
CREATE TABLE citation (
id INTEGER PRIMARY KEY,
texte TEXT,
auteur CHAR(128),
date CHAR(16),
posteur CHAR(128)
);");
    $db->exec("
INSERT INTO citation (texte, auteur, date)
SELECT citation, auteur, date FROM citation_tmp ORDER BY date;");
    $db->exec("DROP TABLE citation_tmp;");
  }
}
