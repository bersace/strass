<?php /*-*- sql -*-*/

class Strass_Migrate_To12 extends Strass_MigrateHandler {
  function online($db) {
    $db->exec(<<<'EOS'
--

DROP TABLE diplomes;
DROP TABLE formation;

CREATE TABLE etape (
       id		INTEGER		PRIMARY KEY,
       slug		CHAR(64)	UNIQUE,
       titre            CHAR(128),
       sexe             CHAR(1),
       participe_passe  CHAR(64),
       ordre            INT,
       age_min          INT
);

INSERT INTO etape
(slug, titre, sexe, participe_passe, ordre, age_min)
SELECT id, titre, sexe, participe_passe, ordre, age_min
FROM etapes
WHERE titre NOT LIKE 'Badge%';

ALTER TABLE individu RENAME TO tmp;
CREATE TABLE `individu` (
       id               INTEGER         PRIMARY KEY,
       slug             CHAR(64)        UNIQUE,
       -- État civil
       titre            CHAR(64)	DEFAULT '',
       prenom           CHAR(64)        NOT NULL,
       nom              CHAR(64)        NOT NULL,
       sexe             CHAR(1)         NOT NULL,
       naissance        CHAR(10)        NOT NULL,
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

INSERT INTO individu
(slug, nom, prenom, sexe, naissance, totem, numero, adresse, fixe, portable, adelec, notes, etape)
SELECT tmp.slug, nom, prenom, tmp.sexe, naissance, totem, numero, adresse, fixe, portable, adelec, notes, MAX(etape.id)
FROM tmp
LEFT OUTER JOIN progression ON progression.individu = tmp.slug
LEFT OUTER JOIN etape ON etape.slug = progression.etape
GROUP BY tmp.slug
ORDER BY tmp.id;

DROP TABLE tmp;
DROP TABLE progression;
DROP TABLE etapes;

CREATE VIEW vindividus AS
SELECT individu.id, individu.slug, TRIM(individu.titre || ' ' || prenom || ' ' || nom) AS nom, etape.titre
FROM individu
LEFT OUTER JOIN etape ON etape.id = individu.etape;
EOS
);
  }
}
