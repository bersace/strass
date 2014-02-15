<?php /*-*- sql -*-*/

class Strass_Migrate_To21 extends Strass_MigrateHandler {
  function online($db) {
    $db->exec(<<<'EOS'
--

ALTER TABLE livredor RENAME TO tmp;
CREATE TABLE `livredor` (
       id INTEGER PRIMARY KEY,
       auteur           CHAR(128)	NOT NULL,
       date             TIMESTAMP	DEFAULT CURRENT_TIMESTAMP,
       public           BOOLEAN		DEFAULT 0,
       contenu          TEXT		NOT NULL
);

INSERT INTO livredor
(auteur, date, public, contenu)
SELECT auteur, strftime('%s', date), public, message
FROM tmp
ORDER BY id;

DROP TABLE tmp;

EOS
);
  }
}