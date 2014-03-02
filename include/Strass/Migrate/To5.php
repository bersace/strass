<?php /*-*- sql -*-*/

class Strass_Migrate_To5 extends Strass_MigrateHandler {
  function online($db) {
    $db->exec(<<<'EOS'

CREATE TABLE `etape` (
	id		INTEGER		PRIMARY KEY,
	slug		CHAR(64)	UNIQUE,
	titre		CHAR(128),
	sexe		CHAR(1),
	participe_passe	CHAR(64),
	ordre		INT,
	age_min		INT
);

INSERT INTO etape
(slug, titre, sexe, participe_passe, ordre, age_min)
SELECT id, titre, sexe, participe_passe, ordre, age_min
FROM etapes
WHERE titre NOT LIKE 'Badge%';

CREATE TABLE `individu` (
	id		INTEGER		PRIMARY KEY,
	slug		CHAR(64)	UNIQUE,
	-- État civil
	titre		CHAR(64)	DEFAULT '',
	prenom		CHAR(64)	NOT NULL,
	nom		CHAR(64)	NOT NULL,
	sexe		CHAR(1)		NOT NULL,
	naissance	DATE		DEFAULT NULL,
	pere		INTEGER		REFERENCES individu(id),
	mere		INTEGER		REFERENCES individu(id),
	-- c'est élémentaire !
	totem		CHAR(64),
	etape		INTEGER		REFERENCES etape(id),
	-- Numéro adhérent dans l'association
	numero		CHAR(12),
	-- Contact
	adresse		CHAR(255),
	fixe		CHAR(14),
	portable	CHAR(14),
	adelec		CHAR(64),
	notes		TEXT
);

INSERT INTO individu
(slug,
 nom, prenom, sexe, naissance,
 totem, etape, numero,
 adresse, fixe, portable, adelec, notes)
SELECT
 ancien.id,
 nom, prenom, ancien.sexe, naissance,
 totem, MAX(etape.id), numero,
 adresse, fixe, portable, adelec, notes
FROM individus AS ancien
LEFT OUTER JOIN progression ON progression.individu = ancien.id
LEFT OUTER JOIN etape ON etape.slug = progression.etape
GROUP BY ancien.id
ORDER BY ancien.ROWID;

CREATE TABLE `user` (
	id			INTEGER 	PRIMARY KEY,
	individu		INTEGER 	UNIQUE REFERENCES individu(id),
	-- Valeur utilisée pour générer le digest. À partir de maintenant,
	-- c'est l'adelec.
	username		CHAR(64)	UNIQUE NOT NULL,
	-- On stocke un digest.
	password		CHAR(32)	NOT NULL,
	admin			BOOLEAN		DEFAULT 0,
	recover_token		CHAR(36),
	recover_deadline	DATETIME,
	last_login		DATETIME
);

INSERT INTO `user`
(individu, username, password, admin)
SELECT
 individu.id, ancien.username, ha1, (membership.groupname IS NOT NULL) AS admin
FROM users AS ancien
JOIN individus ON individus.username = ancien.username
JOIN individu ON individu.slug = individus.id
LEFT JOIN membership ON membership.username = ancien.username AND membership.groupname = 'admins';

DROP TABLE diplomes;
DROP TABLE formation;
DROP TABLE progression;
DROP TABLE etapes;
DROP TABLE individus;
DROP TABLE users;
DROP TABLE membership;
DROP TABLE groups;

CREATE VIEW vindividus AS
SELECT
        individu.id, individu.slug,
        TRIM(individu.titre || ' ' || prenom || ' ' || nom) AS nom,
        etape.titre AS etape
FROM individu
LEFT OUTER JOIN etape ON etape.id = individu.etape;
EOS
);
  }
}
