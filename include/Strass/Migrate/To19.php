<?php /*-*- sql -*-*/

class Strass_Migrate_To19 extends Strass_MigrateHandler {
  function online($db) {
    $db->exec(<<<'EOS'
--

ALTER TABLE individu RENAME TO tmp;
CREATE TABLE `individu` (
       id               INTEGER         PRIMARY KEY,
       slug             CHAR(64)        UNIQUE,
       -- État civil
       titre            CHAR(64)        DEFAULT '',
       prenom           CHAR(64)        NOT NULL,
       nom              CHAR(64)        NOT NULL,
       sexe             CHAR(1)         NOT NULL,
       naissance        TIMESTAMP	DEFAULT NULL,
       pere             INTEGER         REFERENCES individu(id),
       mere             INTEGER         REFERENCES individu(id),
       -- c'est élémentaire !
       totem            CHAR(64),
       etape            INTEGER         REFERENCES etape(id),
       -- Numéro adhérent dans l'association
       numero           CHAR(12),
       -- Contact
       adresse          CHAR(255),
       fixe             CHAR(14),
       portable         CHAR(14),
       adelec           CHAR(64),
       notes            TEXT
);


--

INSERT INTO individu
(slug, prenom, nom, sexe, naissance,
 totem, numero, adresse, fixe, portable, adelec, notes, etape)
SELECT
	slug, prenom, nom, sexe, CAST(strftime('%s', naissance) AS INTEGER),
        totem, numero, adresse, fixe, portable, adelec, notes, etape
FROM tmp;

UPDATE individu SET naissance = NULL WHERE naissance = 0;

DROP TABLE tmp;

EOS
);
  }
}
