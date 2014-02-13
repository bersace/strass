-- Fiche individu / utilisateur
CREATE TABLE `inscription` (
       id		INTEGER		PRIMARY KEY,
       password         CHAR(32)    	NOT NULL,
       -- état civil
       nom              CHAR(64),
       prenom           CHAR(64),
       sexe             CHAR(1)     	NOT NULL,
       naissance        DATE	    	NOT NULL,
       -- contact
       adresse          CHAR(128)   	NOT NULL UNIQUE,
       fixe             CHAR(14)    	NULL,
       portable         CHAR(14)    	NULL,
       adelec           CHAR(64)    	NOT NULL,
       progression      TEXT        	NULL,
       -- modération
       message          TEXT        	NOT NULL,
       scoutisme        TEXT        	NOT NULL

);

CREATE TABLE `etape` (
       id		INTEGER		PRIMARY KEY,
       slug		CHAR(64)	UNIQUE,
       titre            CHAR(128),
       sexe             CHAR(1),
       participe_passe  CHAR(64),
       ordre            INT,
       age_min          INT
);

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

CREATE VIEW vindividus AS
SELECT individu.id, individu.slug, TRIM(individu.titre || ' ' || prenom || ' ' || nom) AS nom, etape.titre
FROM individu
LEFT OUTER JOIN etape ON etape.id = individu.etape;

CREATE TABLE `user` (
       id            INTEGER PRIMARY KEY,
       individu      INTEGER UNIQUE REFERENCES individu(id),
       -- Valeur utilisée pour générer le digest. À partir de maintenant,
       -- c'est l'adelec.
       username      CHAR(64) UNIQUE NOT NULL,
       -- On stocke un digest
       password      CHAR(32) NOT NULL,
       admin         BOOLEAN DEFAULT FALSE,
       last_login    INTEGER, recover_token CHAR(36), recover_deadline INTEGER

);

-- Les unités

CREATE TABLE `unite_type`
(
	id            INTEGER	PRIMARY KEY,
	slug          CHAR(32)  NOT NULL UNIQUE,
	parent        INTEGER	REFERENCES unite_type(id),
	virtuelle     BOOLEAN	DEFAULT FALSE,
	nom           CHAR(32)  NOT NULL,
	ordre         INT(2)	NOT NULL,
	sexe          CHAR(1)   NOT NULL,
	age_min       INT(4),
	age_max       INT(4)
);

CREATE VIEW vtypes AS
SELECT t.id, t.slug, t.nom, t.virtuelle, t.sexe, t.age_min AS min, t.age_max AS max, p.nom AS parent
FROM unite_type AS t
LEFT JOIN unite_type AS p on p.id = t.parent
ORDER BY t.ordre, t.id;

CREATE TABLE `unite` (
       id            INTEGER PRIMARY KEY,
       slug          CHAR(128)       UNIQUE NOT NULL,
       parent        INTEGER REFERENCES unite(id),
       nom           CHAR(128)       NOT NULL,
       `type`        INTEGER REFERENCES unite_type(id),
       extra         CHAR(128)       NULL   -- cri de pat/équipe/nom de troupe/compagnie/guide
);

CREATE VIEW vunites AS
SELECT u.id, u.slug, t.nom AS type, u.nom, u.extra
FROM unite AS u
JOIN unite_type AS t ON t.id = u.type;

CREATE TABLE `unite_role` (
       id       INTEGER		PRIMARY KEY,
       slug     CHAR(16)	UNIQUE,
       type     INTEGER		REFERENCES unites_type(id),
       acl_role CHAR(16),
       titre    CHAR(64),
       accr     CHAR(6),
       ordre    INT(2),
       UNIQUE (slug, type)
);

CREATE VIEW vroles AS
SELECT r.id, r.slug, accr, r.titre, t.nom AS unite, acl_role AS acl
FROM unite_role AS r
JOIN unite_type AS t ON t.id = r.type
ORDER BY t.id, r.id;

CREATE TABLE `unite_titre` (
       id	INTEGER		PRIMARY KEY,
       slug	CHAR(128)	NOT NULL,
       role	INTEGER		REFERENCES unite_role(id),
       nom	CHAR(128)	NOT NULL,
       UNIQUE (role, nom),
       UNIQUE (role, slug)
);

CREATE VIEW vtitres AS
SELECT t.id, t.slug, t.nom, unite_role.titre, unite_type.nom
FROM unite_titre AS t
JOIN unite_role ON unite_role.id = t.role
JOIN unite_type ON unite_type.id = unite_role.type;

CREATE TABLE `appartenance` (
       id            INTEGER PRIMARY KEY,
       individu      INTEGER REFERENCES individu(id) NOT NULL,
       unite         INTEGER REFERENCES unite(id) NOT NULL,
       role          INTEGER REFERENCES unite_role(id) NULL,
       titre         CHAR(64),
       debut         DATE NOT NULL,
       fin           DATE DEFAULT NULL
);

CREATE VIEW vappartenances AS
SELECT DISTINCT
  appartenance.id, individu.slug AS individu, appartenance.titre, role.titre, unite.nom
FROM appartenance
JOIN individu ON individu.id = appartenance.individu
JOIN unite_role AS role ON role.id = appartenance.role
JOIN unite ON unite.id = appartenance.unite
ORDER BY individu.naissance ASC;

-- Les documents : pièces jointes, circulaires, etc.

CREATE TABLE `document` (
       id		INTEGER		PRIMARY KEY,
       slug       	CHAR(128)	NOT NULL UNIQUE,
       titre    	CHAR(128)	NOT NULL,
       suffixe  	CHAR(8),
       date     	DATETIME
);

CREATE TABLE `unite_document` (
       id		INTEGER		PRIMARY KEY,
       unite    	INTEGER		NOT NULL REFERENCES unite(id),
       document		INTEGER		NOT NULL REFERENCES document(id),
       UNIQUE (unite, document)
);

CREATE VIEW vdocuments AS
SELECT document.id, unite.slug, document.slug, document.suffixe
FROM document
JOIN unite_document ON unite_document.document = document.id
JOIN unite ON unite.id = unite_document.unite
ORDER BY document.date;


-- Le calendrier

CREATE TABLE `activite` (
       id		INTEGER		PRIMARY KEY,
       slug     	CHAR(128)	UNIQUE,
       type		CHAR(16),
       intitule 	CHAR(128),
       accronym		CHAR(8),
       date		CHAR(16),
       lieu     	CHAR(128),
       debut    	DATETIME       	NOT NULL,
       fin      	DATETIME       	NOT NULL,
       description	TEXT
);

CREATE TABLE `participation` (
       id		INTEGER		PRIMARY KEY,
       activite		INTEGER		NOT NULL REFERENCES activite(id),
       unite		INTEGER		NOT NULL REFERENCES unite(id),
       UNIQUE(activite, unite)
);

CREATE VIEW vcalendrier AS
SELECT
	participation.id, strftime('%Y', debut, '-9 months') AS annee,
	activite.slug AS activite, unite.slug AS unite, debut, fin
FROM activite
JOIN participation ON participation.activite = activite.id
JOIN unite ON unite.id = participation.unite
ORDER BY debut;

CREATE TABLE `activite_document` (
       id		INTEGER		PRIMARY KEY,
       activite		INTEGER		NOT NULL REFERENCES activite(id),
       document		INTEGER		NOT NULL REFERENCES document(id),
       UNIQUE (activite, document)
);

CREATE VIEW vpiecesjointes AS
SELECT document.id, activite.slug, document.slug, document.suffixe
FROM document
JOIN activite_document ON activite_document.document = document.id
JOIN activite ON activite.id = activite_document.activite
ORDER BY activite.debut;

-- Les photos
CREATE TABLE `commentaire` (
       id		INTEGER		PRIMARY KEY,
       auteur		INTEGER		REFERENCES individu(id),
       parent		INTEGER		REFERENCES commentaire(id),
       `date`		DATETIME        DEFAULT CURRENT_TIMESTAMP,
       message	        TEXT,
       -- Interdire les réponses multiples. Le site ne sert pas à discuter.
       UNIQUE(auteur, parent)
);

CREATE TABLE `photo` (
       id		INTEGER		PRIMARY KEY,
       slug     	CHAR(512)	UNIQUE,
       activite		INTEGER		NOT NULL REFERENCES activite(id),
       promotion	INTEGER		DEFAULT 0,
       `date`		DATETIME,
       titre 		CHAR(512),
       commentaires     INTEGER         NOT NULL REFERENCES commentaire(id)
);

CREATE VIEW vphotos AS
SELECT
	photo.id, photo.slug,
	activite.slug AS activite,
	photo.titre
FROM photo
JOIN activite ON activite.id = photo.activite
ORDER BY activite.debut, photo.date;

CREATE VIEW vcommentaires AS
SELECT
	commentaire.id,
	activite.slug AS activite, photo.slug AS photo, individu.slug,
	commentaire.date, message
FROM commentaire
JOIN photo ON photo.commentaires = commentaire.parent
JOIN activite ON activite.id = photo.activite
LEFT JOIN individu ON individu.id = commentaire.auteur
ORDER BY photo.id, commentaire.date;

CREATE TABLE `journal` (
       id		INTEGER		PRIMARY KEY,
       slug		CHAR(128)	UNIQUE,
       -- Un seul blog par unité autorisé
       unite		INTEGER		UNIQUE REFERENCES unite(id),
       nom		CHAR(128)	UNIQUE
);

-- Les gazettes d'unité

CREATE VIEW vjournaux AS
SELECT journal.id, journal.slug, unite.slug, journal.nom
FROM journal
JOIN unite ON unite.id = journal.unite;

CREATE TABLE `article` (
       id		INTEGER		PRIMARY KEY,
       slug		CHAR(256)	UNIQUE,
       journal	 	INTEGER 	REFERENCES journal(id),
       titre    	CHAR(256),
       boulet   	TEXT,
       article  	TEXT,
       public   	INT(1)     	DEFAULT 0,
       commentaires	INTEGER		UNIQUE NOT NULL REFERENCES commentaire(id)
);

CREATE VIEW varticles AS
SELECT article.id,
       journal.slug AS journal,
       article.slug, auteur.slug AS auteur,
       article.titre, commentaire.date
FROM article
JOIN journal ON journal.id = article.journal
JOIN commentaire ON commentaire.id = article.commentaires
JOIN individu AS auteur ON auteur.id = commentaire.auteur
ORDER BY journal.id, commentaire.date;

CREATE TABLE `article_etiquette` (
       id		INTEGER		PRIMARY KEY,
       article		INTEGER		NOT NULL REFERENCES article(id),
       etiquette	CHAR(128)	NOT NULL,
       UNIQUE(article, etiquette)
);

-- Citation, livre d'or, liens
CREATE TABLE `citation` (
       id INTEGER PRIMARY KEY,
       texte TEXT,
       auteur CHAR(128),
       date CHAR(16),
       posteur CHAR(128)
);

CREATE TABLE `livredor` (
       id INTEGER PRIMARY KEY,
       auteur           CHAR(128),
       adelec           CHAR(128),
       date             CHAR(16),
       public           CHAR(1) DEFAULT NULL,
       message          TEXT    NOT NULL
);

CREATE TABLE `lien` (
       id		INTEGER		PRIMARY KEY,
       url              CHAR(256)	UNIQUE,
       nom              CHAR(128),
       description      CHAR(512)
);

-- Journal système
CREATE TABLE `log` (
       id INTEGER PRIMARY KEY AUTOINCREMENT,
       username CHAR(64),
       url CHAR(255),
       titre CHAR(255),
       date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       detail CHAR(512)
);

CREATE TABLE `log_attr` (
       id INTEGER PRIMARY KEY AUTOINCREMENT,
       log INTEGER,
       detail CHAR(64),
       descr CHAR(128),
       classe CHAR(32),
       clef CHAR(128)
);
