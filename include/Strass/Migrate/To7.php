<?php /*-*- sql -*-*/

class Strass_Migrate_To7 extends Strass_MigrateHandler {
  function online($db) {
    $db->exec(<<<'EOS'
--

DROP TABLE apporter;

CREATE TABLE `activite` (
       id		INTEGER		PRIMARY KEY,
       slug     	CHAR(128)	NOT NULL UNIQUE,
       type		CHAR(16),
       intitule 	CHAR(128),
       accronym		CHAR(8),
       date		CHAR(16),
       lieu     	CHAR(128),
       debut    	DATETIME       	NOT NULL,
       fin      	DATETIME       	NOT NULL,
       description	TEXT
);


INSERT INTO activite
(slug, intitule, lieu, debut, fin, description)
SELECT id, (CASE
WHEN intitule LIKE '%sortie%' THEN NULL
WHEN intitule LIKE '%camp%' THEN NULL
WHEN intitule LIKE '%week-end%' THEN NULL
WHEN intitule LIKE '%chasse%' THEN NULL
ELSE intitule
END) AS intitule
, lieu, debut, fin, message
FROM activites;

CREATE TABLE `participation` (
       id		INTEGER		PRIMARY KEY,
       activite		INTEGER		NOT NULL REFERENCES activite(id),
       unite		INTEGER		NOT NULL REFERENCES unite(id),
       UNIQUE(activite, unite)
);

INSERT INTO participation
(activite, unite)
SELECT activite.id, unite.id
FROM participe
JOIN activite ON activite.slug = participe.activite
JOIN unite ON unite.slug = participe.unite
ORDER BY debut;

DROP TABLE participe;
DROP TABLE activites;

CREATE VIEW vcalendrier AS
SELECT
	participation.id, strftime('%Y', debut, '-9 months') AS annee,
	activite.slug AS activite, unite.slug AS unite, debut, fin
FROM activite
JOIN participation ON participation.activite = activite.id
JOIN unite ON unite.id = participation.unite
ORDER BY debut;

CREATE TABLE activite_document (
       id		INTEGER		PRIMARY KEY,
       activite		INTEGER		NOT NULL REFERENCES activite(id),
       document		CHAR(128),
       UNIQUE (activite, document)
);

INSERT INTO activite_document
(activite, document)
SELECT activite.id, document
FROM doc_activite
JOIN activite ON activite.slug = activite;

DROP TABLE doc_activite;

EOS
);
  }
}
