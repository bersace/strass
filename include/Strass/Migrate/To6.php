<?php

class Strass_Migrate_To6 extends Strass_MigrateHandler {
  function online($db) {
    $db->exec("ALTER TABLE individus RENAME TO tmp;");
    $db->exec("
CREATE TABLE `individu` (
  id            INTEGER PRIMARY KEY,
  slug          CHAR(64) UNIQUE,
  -- Valeur utilisée pour générer le digest. À partir de maintenant,
  -- c'est l'adelec.
  username      CHAR(64),
  -- On stocke un digest
  password      CHAR(32),
  admin         BOOLEAN DEFAULT FALSE,
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
  numero        CHAR(12)         DEFAULT NULL,
  -- Contact
  adresse       CHAR(255)       DEFAULT '',
  fixe          CHAR(14)        DEFAULT '',
  portable      CHAR(14)        DEFAULT '',
  adelec        CHAR(64)        DEFAULT '',
  notes         TEXT            DEFAULT '',
  FOREIGN KEY (pere) REFERENCES individu(id),
  FOREIGN KEY (mere) REFERENCES individu(id)
);");
    $db->exec("
INSERT INTO individu
(slug, username, password, admin,
 nom, prenom, sexe, naissance, totem,
 adresse, fixe, portable, adelec, numero, notes)
SELECT
 id, tmp.username, ha1, membership.groupname IS NOT NULL,
 nom, prenom, sexe, naissance, totem,
 adresse, fixe, portable, adelec, numero, notes
FROM tmp
LEFT JOIN users ON users.username = tmp.username
LEFT JOIN membership ON membership.username = tmp.username AND membership.groupname = 'admins'
;");
    $db->exec("DROP TABLE tmp;");
    $db->exec("DROP TABLE users;");
    $db->exec("DROP TABLE membership;");
    $db->exec("DROP TABLE groups;");
  }
}
