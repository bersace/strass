<?php /*-*- sql -*-*/

class Strass_Migrate_To4 extends Strass_MigrateHandler {
  function online($db) {
    $db->exec(<<<'EOS'
--

ALTER TABLE livredor RENAME TO tmp;
CREATE TABLE `livredor` (
	id INTEGER PRIMARY KEY,
	auteur		CHAR(128)	NOT NULL,
	date		DATETIME	DEFAULT CURRENT_TIMESTAMP,
	public		BOOLEAN		DEFAULT 0,
	contenu		TEXT		NOT NULL
);

INSERT INTO livredor
(auteur, date, public, contenu)
SELECT auteur, date, public, message FROM tmp ORDER BY date;

DROP TABLE tmp;
EOS
);
  }
}
