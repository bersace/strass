<?php /*-*- sql -*-*/

class Strass_Migrate_To3 extends Strass_MigrateHandler {
  function online($db) {
    $db->exec(<<<'EOS'
CREATE TABLE citation (
  id        INTEGER PRIMARY KEY,
  texte     TEXT,
  auteur    CHAR(128),
  date      CHAR(16)
);

INSERT INTO citation
(texte, auteur, date)
SELECT citation, auteur, date
FROM citations
ORDER BY date;

DROP TABLE citations;
EOS
);
  }
}
