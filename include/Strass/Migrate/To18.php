<?php /*-*- sql -*-*/

class Strass_Migrate_To18 extends Strass_MigrateHandler {
  function online($db) {
    $db->exec(<<<'EOS'
--

CREATE TABLE `inscription` (
       id               INTEGER         PRIMARY KEY,
       date             TIMESTAMP	DEFAULT CURRENT_TIMESTAMP,
       nom              CHAR(64),
       prenom           CHAR(64),
       sexe             CHAR(1)         NOT NULL,
       naissance        DATE            NOT NULL,
       adelec           CHAR(64)        NOT NULL UNIQUE,
       password         CHAR(32)        NOT NULL,
       presentation     TEXT
);

-- Valider les inscriptions avant de migrer ;-)

DROP TABLE inscriptions;

EOS
);
  }
}
