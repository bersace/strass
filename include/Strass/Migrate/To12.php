<?php /*-*- sql -*-*/

class Strass_Migrate_To12 extends Strass_MigrateHandler {
  function online($db) {
    $db->exec(<<<'EOS'
--

CREATE TABLE `lien` (
       id		INTEGER		PRIMARY KEY,
       url              CHAR(256)	UNIQUE,
       nom              CHAR(128),
       description      CHAR(512)
);

INSERT INTO lien
(url, nom, description)
SELECT url, nom, description
FROM liens
ORDER BY ROWID;

DROP TABLE liens;

EOS
);
  }
}
