<?php /*-*- sql -*-*/

class Strass_Migrate_To10 extends Strass_MigrateHandler {
  function online($db) {
    $db->exec(<<<'EOS'
--

ALTER TABLE activites RENAME TO tmp;
CREATE TABLE `activites` (
       id       CHAR(128),
       intitule CHAR(128),
       lieu     CHAR(128),
       debut    CHAR(16)        NOT NULL,
       fin      CHAR(16)        NOT NULL,
       message  TEXT,
       PRIMARY KEY (id)
);

INSERT INTO activites
(id, intitule, lieu, debut, fin, message)
SELECT id, intitule, lieu, debut, fin, message FROM tmp;

DROP TABLE tmp;

EOS
);
  }
}
