<?php /*-*- sql -*-*/

class Strass_Migrate_To6 extends Strass_MigrateHandler {
  function online($db) {
    $db->exec(<<<'EOS'
--

CREATE TABLE `unite_type`
(
  id            INTEGER PRIMARY KEY,
  slug          CHAR(32)        NOT NULL UNIQUE,
  parent        INTEGER REFERENCES unite_type(id),
  virtuelle     BOOLEAN DEFAULT 0,
  nom           CHAR(32)        NOT NULL,
  ordre         INT(2),
  sexe          CHAR(1)         NOT NULL,
  age_min       INT(4)          NOT NULL,
  age_max       INT(4)          NOT NULL
);

INSERT INTO unite_type
(slug, virtuelle, nom, ordre, sexe, age_min, age_max)
SELECT id AS slug,
CAST((id IN ('hp', 'aines')) AS BOOLEAN) AS virtuelle,
t.nom, t.ordre, t.sexe, t.age_min, t.age_max
FROM types_unite AS t;

UPDATE unite_type
SET parent = (
    SELECT p.id
    FROM types_unite AS t
    JOIN unite_type AS p ON p.slug = t.parent
    WHERE t.id = unite_type.slug);

INSERT INTO unite_type
(slug, parent, nom, ordre, sexe, age_min, age_max)
VALUES
('eqclan', (SELECT id FROM unite_type WHERE slug = 'clan'), 'équipe', 1, 'h', 16, 30),
('eqfeu', (SELECT id FROM unite_type WHERE slug = 'feu'), 'équipe', 1, 'f', 16, 30);

DROP TABLE types_unite;

CREATE VIEW vtypes AS
SELECT t.id, t.slug, t.nom, t.virtuelle, t.sexe, t.age_min AS min, t.age_max AS max, p.nom AS parent
FROM unite_type AS t
LEFT JOIN unite_type AS p on p.id = t.parent
ORDER BY t.ordre;

EOS
);

    $db->exec(<<<'EOS'
CREATE TABLE `unite` (
  id            INTEGER PRIMARY KEY,
  slug          CHAR(128)       UNIQUE NOT NULL,
  parent        INTEGER REFERENCES unite(id),
  nom           CHAR(128)       NOT NULL,
  `type`        INTEGER REFERENCES unite_type(id),
  extra         CHAR(128)       NULL   -- cri de pat/équipe/nom de troupe/compagnie/guide
);

INSERT INTO unite
(slug, nom, type, extra)
SELECT id, nom, (SELECT id FROM unite_type WHERE slug = type), extra FROM unites;

UPDATE unite
SET parent = (
    SELECT parent.id FROM unites
    JOIN unite AS parent ON parent.slug = unites.parent
    WHERE unites.id = unite.slug);

DROP TABLE unites;

CREATE VIEW vunites AS
SELECT u.id, u.slug, t.nom AS type, u.nom, u.extra
FROM unite AS u
JOIN unite_type AS t ON t.id = u.type;
EOS
);

    $db->exec(<<<'EOS'
-- chef, assistant, 3e, etc.
CREATE TABLE `unite_role` (
  id       INTEGER PRIMARY KEY,
  slug     CHAR(16) UNIQUE,
  type     INTEGER REFERENCES unites_type(id),
  acl_role CHAR(16),
  titre    CHAR(64),
  accr     CHAR(6),
  ordre    INT(2),
  UNIQUE (slug, type)
);

-- préparation des données pour migration

UPDATE roles SET accr = 'CF' WHERE id = 'chef' AND type = 'feu';
UPDATE roles SET accr = replace(accr, '.', '');
UPDATE roles SET titre = 'assistant chef de clan', accr = 'ACC' WHERE id = 'assistant' AND titre = 'routier';
UPDATE roles SET titre = 'cheftaine de compagnie' WHERE accr = 'CCie';
UPDATE roles SET titre = 'assistante cheftaine de compagnie' WHERE accr = 'ACCie';
UPDATE roles SET accr = 'ACR' WHERE id = 'assistant' AND type = 'ronde';
UPDATE roles SET accr = 'GA' WHERE titre = 'guide-aînée';

-- Migration des roles, même s'il y a des titres '
INSERT INTO unite_role
(slug, type, acl_role, titre, accr, ordre)
SELECT
	(CASE WHEN length(roles.accr) > 0 THEN lower(replace(roles.accr, '.', '')) ELSE lower(roles.titre) || '-' || roles.type END),
        (SELECT id FROM unite_type WHERE unite_type.slug = roles.type), id, titre, accr, ordre
FROM roles;

-- Complément SUF
INSERT INTO unite_role
(slug, acl_role, titre, accr, ordre, type)
VALUES
('acm', 'assistant', 'Assistante d''Akéla', 'ACM', 2, (SELECT id FROM unite_type WHERE unite_type.slug = 'meute')),
('acf', 'assistant', 'Assistante cheftaine de feu', 'ACF', 2, (SELECT id FROM unite_type WHERE unite_type.slug = 'feu')),
('cer', 'chef', 'Chef d''équipe', 'CE', 1, (SELECT id FROM unite_type WHERE slug = 'eqclan')),
('equipier', 'assistant', 'routier', NULL, 2, (SELECT id FROM unite_type WHERE slug = 'eqclan')),
('cef', 'chef', 'Cheftaine d''équipe', 'CE', 1, (SELECT id FROM unite_type WHERE slug = 'eqfeu')),
('equipiere', 'assistant', 'guide-aînée', NULL, 2, (SELECT id FROM unite_type WHERE slug = 'eqfeu'));

UPDATE unite_role SET slug = 'akela' WHERE titre = 'Akéla';
UPDATE unite_role SET slug = 'guillemette' WHERE titre = 'Guillemette';
UPDATE unite_role SET slug = replace(slug, 'sizloup', 'louveteau');
UPDATE unite_role SET slug = replace(slug, 'sizjeannette', 'jeannette');
UPDATE unite_role SET slug = replace(slug, 'sizainière', 'sizainiere');

DROP TABLE roles;

CREATE VIEW vroles AS
SELECT r.id, r.slug, r.titre, t.nom, accr, acl_role AS acl
FROM unite_role AS r
JOIN unite_type AS t ON t.id = r.type
ORDER BY t.id, r.id;
EOS
);

    $db->exec(<<<'EOS'
-- titre comme Bagheera, Hauviette, aumônier, etc.
CREATE TABLE `unite_titre` (
  id            INTEGER PRIMARY KEY,
  slug		CHAR(128)  NOT NULL,
  role          INTEGER REFERENCES unite_role(id),
  nom           CHAR(128)  NOT NULL,
  UNIQUE (role, nom),
  UNIQUE (role, slug)
);

INSERT INTO unite_titre
(slug, nom, role)
VALUES
('aumonier', 'aumônier',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'groupe')),
('tresorier', 'trésorier',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'groupe')),
('materialiste', 'matérialiste',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'groupe')),
('secretaire', 'secrétaire',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'groupe')),

('aumonier', 'aumônier',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'clan')),
('aumonier', 'aumônier',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'feu')),
('aumonier', 'aumônier',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'troupe')),
('aumonier', 'aumônier',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'compagnie')),

('aumonier', 'aumônier',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('ahdeek', 'Ahdeek',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('baloo', 'Baloo',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('bagheera', 'Bagheera',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('chikai', 'Chikaï',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('chil', 'Chil',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('chunchundra', 'Chunchundra',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('dahinda', 'Dahinda',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('darzee', 'Darzee',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('ferao', 'Ferao',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('gris', 'Frère-Gris',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('hathi', 'Hathi',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('jacala', 'jacala',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('kaa', 'Kaa',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('keego', 'Keego',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('keneu', 'Keneu',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('ko', 'Ko',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('kotick', 'Kotick',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('lardaki', 'Lardaki',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('louie', 'Roi-Louie',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('mang', 'Mang',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('mor', 'Mor',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('mysa', 'Mysa',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('nag', 'Nag',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('oo', 'Oo',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('oonai', 'Oonaï',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('phao', 'Phao',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('phaona', 'Phaona',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('pukeena', 'Pukeena',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('raksha', 'Raksha',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('rama', 'Rama',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('rikki', 'Rikki Tiki Tavi',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('sahi', 'Sahi',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('shada', 'Shada',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('shawshaw', 'Shaw Shaw',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('singum', 'Singum',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('sona', 'Sona',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('tegumai', 'Tegumaï',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('tha', 'Thâ',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('thuu', 'Thuu',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),
('wontolla', 'Won-Tolla',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'meute')),

('aumonier', 'aumônier',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'ronde')),
('zabilet', 'Zabilet',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'ronde')),
('mengette', 'Mengette',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'ronde')),
('hauviette', 'Hauviette',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'ronde')),
('isabelette', 'Isabellette',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'ronde')),
('colette', 'Nicolette',
 (SELECT r.id FROM unite_role r JOIN unite_type t ON t.id = r.type
  WHERE r.acl_role = 'assistant' AND t.slug = 'ronde'));

CREATE VIEW vtitres AS
SELECT t.id, t.slug, t.nom, unite_role.titre, unite_type.nom AS unite
FROM unite_titre AS t
JOIN unite_role ON unite_role.id = t.role
JOIN unite_type ON unite_type.id = unite_role.type;

EOS
);

    $db->exec(<<<'EOS'

CREATE TABLE `appartenance` (
  id            INTEGER PRIMARY KEY,
  individu      INTEGER REFERENCES individu(id) NOT NULL,
  unite         INTEGER REFERENCES unite(id) NOT NULL,
  role          INTEGER REFERENCES unite_role(id) NOT NULL,
  titre         CHAR(64),
  debut         DATE NOT NULL,
  fin           DATE DEFAULT NULL
);

INSERT INTO appartenance
(individu, unite, role, titre, debut, fin)
SELECT DISTINCT individu.id, unite.id, unite_role.id, unite_titre.nom, debut, fin
FROM appartient
JOIN individu ON individu.slug = appartient.individu
JOIN unite ON unite.slug = appartient.unite
JOIN unite_role ON unite_role.acl_role = (CASE
WHEN appartient.role IN ('chef', 'routier', '3e', '4e', '5e', '6e', '7e', '8e', 'siz', 'sec') THEN appartient.role
ELSE 'assistant'
END) AND unite_role.type = unite.type
JOIN unite_type ON unite_type.id = unite.type
LEFT JOIN unite_titre ON unite_titre.slug = appartient.role;

-- suppression des titres (aumônier, bagheera, etc.)
UPDATE unite_role SET acl_role = 'membre' WHERE acl_role LIKE '_e' OR acl_role IN ('siz', 'sec');
DELETE FROM unite_role
WHERE acl_role NOT IN ('chef', 'assistant', 'membre');

CREATE VIEW vappartenances AS
SELECT DISTINCT
  appartenance.id, individu.slug AS individu, appartenance.titre, role.titre AS role, unite.nom AS unite
FROM appartenance
JOIN individu ON individu.id = appartenance.individu
JOIN unite_role AS role ON role.id = appartenance.role
JOIN unite ON unite.id = appartenance.unite
ORDER BY individu.naissance ASC;

DROP TABLE appartient;
EOS
);

    $rootslug = $db->query("SELECT slug FROM unite WHERE parent IS NULL LIMIT 1")->fetchColumn();
    rename('private/unites/intro.wiki', 'private/unites/'.$rootslug.'.wiki');
  }
}
