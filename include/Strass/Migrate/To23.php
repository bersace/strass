<?php /*-*- sql -*-*/

class Strass_Migrate_To23 extends Strass_MigrateHandler {
  function online($db) {
    $db->exec(<<<'EOS'
--

DROP TABLE `inscription`;

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

ALTER TABLE user RENAME TO tmp;
CREATE TABLE `user` (
       id            INTEGER PRIMARY KEY,
       individu      INTEGER UNIQUE REFERENCES individu(id),
       -- Valeur utilisée pour générer le digest. À partir de maintenant,
       -- c'est l'adelec.
       username      CHAR(64) UNIQUE NOT NULL,
       -- On stocke un digest
       password      CHAR(32) NOT NULL,
       admin         BOOLEAN DEFAULT 0,
       last_login    INTEGER,
       recover_token CHAR(36),
       recover_deadline DATETIME
);

INSERT INTO user
(id, individu, username, password, admin)
SELECT id, individu, username, password, admin
FROM tmp;

DROP TABLE tmp;
EOS
);
  }
}
