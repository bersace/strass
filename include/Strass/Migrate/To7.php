<?php /*-*- sql -*-*/

class Strass_Migrate_To7 extends Strass_MigrateHandler {
  function online($db) {
    $db->exec(<<<'EOS'
CREATE TABLE user (
  id            INTEGER PRIMARY KEY,
  individu      INTEGER UNIQUE,
  -- Valeur utilisée pour générer le digest. À partir de maintenant,
  -- c'est l'adelec.
  username      CHAR(64) UNIQUE NOT NULL,
  -- On stocke un digest
  password      CHAR(32) NOT NULL,
  admin         BOOLEAN DEFAULT FALSE,
  last_login    INTEGER,
  FOREIGN KEY (individu) REFERENCES individu(id)
);

INSERT INTO user
(individu, username, password, admin)
SELECT
 id, username, password, admin
FROM individu
WHERE individu.username IS NOT NULL;

ALTER TABLE individu RENAME TO tmp;
CREATE TABLE `individu` (
  id            INTEGER PRIMARY KEY,
  slug          CHAR(64) UNIQUE,
  -- État civil
  nom           CHAR(64)        NOT NULL,
  prenom        CHAR(64)        NOT NULL,
  sexe          CHAR(1)         NOT NULL,
  naissance     CHAR(10)        NOT NULL,
  totem         CHAR(64)        DEFAULT '',
  pere INTEGER,
  mere INTEGER,
  -- c'est élémentaire !
  -- Numéro adhérent dans l'association
  numero        CHAR(12)        DEFAULT NULL,
  -- Contact
  adresse       CHAR(255)       DEFAULT '',
  fixe          CHAR(14)        DEFAULT '',
  portable      CHAR(14)        DEFAULT '',
  adelec        CHAR(64)        DEFAULT '',
  notes         TEXT            DEFAULT '',
  FOREIGN KEY (pere) REFERENCES individu(id),
  FOREIGN KEY (mere) REFERENCES individu(id)
);

INSERT INTO individu
(slug, nom, prenom, sexe, naissance, totem,
 adresse, fixe, portable, adelec, numero, notes)
SELECT
 slug, nom, prenom, sexe, naissance, totem,
 adresse, fixe, portable, adelec, numero, notes
FROM tmp;
EOS
);
  }
}
