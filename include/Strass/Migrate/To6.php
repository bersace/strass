<?php /*-*- sql -*-*/

class Strass_Migrate_To6 extends Strass_MigrateHandler {
  function online($db) {
    $config = new Strass_Config_Php('strass');
    $SUF = $config->get('site/id') == 'suf1520';

    $db->exec(<<<'EOS'
CREATE TABLE `unite_type`
(
	id		INTEGER		PRIMARY KEY,
	slug		CHAR(32)	NOT NULL UNIQUE,
	parent		INTEGER		REFERENCES unite_type(id),
	virtuelle	BOOLEAN		DEFAULT 0,
	nom		CHAR(32)	NOT NULL,
	-- Comment s'appelle l'extra ? Cri de pat ? Saint patron ?
	extra		CHAR(32)	DEFAULT NULL,
	ordre		INT(2),
	sexe		CHAR(1),
	age_min	INT(4),
	age_max	INT(4),
	nom_reunion	CHAR(16)	DEFAULT 'Réunion',
	nom_sortie	CHAR(16)	DEFAULT 'Sortie',
	nom_we		CHAR(16)	DEFAULT 'Weekend',
	nom_camp	CHAR(16)	DEFAULT 'Camp',
	accr_reunion	CHAR(8)		DEFAULT 'Réunion',
	accr_sortie	CHAR(8)		DEFAULT 'Sortie',
	accr_we		CHAR(8)		DEFAULT 'WE',
	accr_camp	CHAR(8)		DEFAULT 'Camp'
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
EOS
);

    if ($SUF):
      $db->exec(<<<'EOS'
INSERT INTO unite_type
(slug, parent, nom, ordre, sexe, age_min, age_max)
VALUES
('eqclan', (SELECT id FROM unite_type WHERE slug = 'clan'), 'Équipe', 1, 'h', 16, 30),
('eqfeu', (SELECT id FROM unite_type WHERE slug = 'feu'), 'Équipe', 1, 'f', 16, 30),
('he', (SELECT id FROM unite_type WHERE slug = 'compagnie'), 'Haute-Équipe', 1, NULL, NULL, NULL);

UPDATE unite_type SET virtuelle = 1 WHERE slug = 'he';

-- Capitalisation
UPDATE unite_type SET nom = 'Groupe' WHERE slug = 'groupe';
UPDATE unite_type SET nom = 'Communauté des aînés' WHERE slug = 'aines';
UPDATE unite_type SET nom = 'Clan' WHERE slug = 'clan';
UPDATE unite_type SET nom = 'Équipe' WHERE slug IN ('eqclan', 'eqfeu', 'equipe');
UPDATE unite_type SET nom = 'Feu' WHERE slug = 'feu';
UPDATE unite_type SET nom = 'Troupe' WHERE slug = 'troupe';
UPDATE unite_type SET nom = 'Haute-Patrouille' WHERE slug = 'hp';
UPDATE unite_type SET nom = 'Patrouille' WHERE slug = 'patrouille';
UPDATE unite_type SET nom = 'Compagnie' WHERE slug = 'compagnie';
UPDATE unite_type SET nom = 'Meute' WHERE slug = 'meute';
UPDATE unite_type SET nom = 'Sizaine' WHERE slug LIKE 'siz%';
UPDATE unite_type SET nom = 'Ronde' WHERE slug = 'ronde';

UPDATE unite_type SET accr_we = 'WEG', nom_we = 'Weekend de groupe' WHERE slug = 'groupe';
UPDATE unite_type SET accr_we = 'WEA', nom_we = 'Weekend aînés' WHERE slug = 'aines';
UPDATE unite_type SET accr_we = 'WEC', nom_we = 'Weekend de clan', accr_camp = 'Route', nom_camp = 'Route'
WHERE slug = 'clan';
UPDATE unite_type SET accr_we = 'WEF', nom_we = 'Weekend de feu' WHERE slug = 'feu';
UPDATE unite_type SET accr_we = 'WEE', nom_we = 'Weekend d''équipe' WHERE slug IN ('eqclan', 'eqfeu', 'equipe');
UPDATE unite_type SET accr_we = 'WET', nom_we = 'Weekend de troupe' WHERE slug = 'troupe';
UPDATE unite_type SET accr_we = 'WEHP', nom_we = 'Weekend HP', accr_camp = 'Camp HP', nom_camp = 'Camp HP'
WHERE slug = 'hp';
UPDATE unite_type SET accr_we = 'WEP', nom_we = 'Weekend de patrouille' WHERE slug = 'patrouille';
UPDATE unite_type SET accr_we = 'WECie', nom_we = 'Weekend de compagnie' WHERE slug = 'compagnie';
UPDATE unite_type SET accr_we = 'WEHE', nom_we = 'Weekend HE', accr_camp = 'Camp HE', nom_camp = 'Camp HE'
WHERE slug = 'he';
UPDATE unite_type SET nom_sortie = 'Chasse', nom_we = 'Grand chasse', nom_camp = 'Grande chasse',
       accr_sortie = 'Chasse', accr_we = 'Grande chasse', accr_camp = 'Grande chasse'
WHERE slug = 'meute';

UPDATE unite_type SET ordre = 0 WHERE slug = 'groupe';
UPDATE unite_type SET ordre = 10 WHERE slug IN ('aines');
UPDATE unite_type SET ordre = 11 WHERE slug IN ('clan', 'feu');
UPDATE unite_type SET ordre = 12 WHERE slug IN ('eqclan', 'eqfeu');
UPDATE unite_type SET ordre = 20 WHERE slug IN ('troupe', 'compagnie');
UPDATE unite_type SET ordre = 21 WHERE slug IN ('hp', 'he');
UPDATE unite_type SET ordre = 22 WHERE slug IN ('patrouille', 'equipe');
UPDATE unite_type SET ordre = 30 WHERE slug IN ('meute', 'ronde');
UPDATE unite_type SET ordre = 31 WHERE slug IN ('sizloup', 'sizjeannette');

UPDATE unite_type SET extra = 'Cri de pat' WHERE slug IN ('hp', 'patrouille', 'he', 'equipe');
UPDATE unite_type SET extra = 'Saint patron'
WHERE slug IN ('groupe', 'aines', 'clan', 'eqclan', 'feu', 'eqfeu', 'troupe', 'compagnie');

EOS
);
    else: // FSE Tom Morel
      $db->exec(<<<'EOS'
-- LOL
DELETE FROM unite_type WHERE slug IN ('aines', 'ronde', 'sizjeanette');

-- Capitalisation
UPDATE unite_type SET nom = 'Groupe' WHERE slug = 'groupe';
UPDATE unite_type SET nom = 'Communauté des aînés' WHERE slug = 'aines';
UPDATE unite_type SET nom = 'Clan' WHERE slug = 'clan';
UPDATE unite_type SET nom = 'Équipe' WHERE slug IN ('eqclan', 'eqfeu', 'equipe');
UPDATE unite_type SET nom = 'Feu' WHERE slug = 'feu';
UPDATE unite_type SET nom = 'Troupe' WHERE slug = 'troupe';
UPDATE unite_type SET nom = 'Haute-Patrouille' WHERE slug = 'hp';
UPDATE unite_type SET nom = 'Patrouille' WHERE slug = 'patrouille';
UPDATE unite_type SET nom = 'Compagnie' WHERE slug = 'compagnie';
UPDATE unite_type SET nom = 'Meute' WHERE slug = 'meute';
UPDATE unite_type SET nom = 'Sizaine' WHERE slug LIKE 'siz%';

UPDATE unite_type SET accr_we = 'WEG', nom_we = 'Weekend de groupe' WHERE slug = 'groupe';
UPDATE unite_type SET accr_we = 'WEC', nom_we = 'Weekend de clan', nom_camp = 'Route' WHERE slug = 'clan';
UPDATE unite_type SET accr_we = 'WEF', nom_we = 'Weekend de feu' WHERE slug = 'feu';
UPDATE unite_type SET accr_we = 'WEE', nom_we = 'Weekend d''équipe' WHERE slug IN ('eqclan', 'eqfeu');
UPDATE unite_type SET accr_we = 'WET', nom_we = 'Weekend de troupe' WHERE slug = 'troupe';
UPDATE unite_type SET accr_we = 'WECie', nom_we = 'Weekend de compagnie' WHERE slug = 'compagnie';
UPDATE unite_type SET accr_we = 'WEHP', nom_we = 'Weekend HP', nom_camp = 'Camp HP' WHERE slug IN ('hp', 'hpc');
UPDATE unite_type SET accr_we = 'WEP', nom_we = 'Weekend de patrouille'
WHERE slug IN ('patrouille', 'patguide');
UPDATE unite_type SET nom_sortie = 'Chasse', nom_we = 'Grand chasse', nom_camp = 'Grande chasse'
WHERE slug IN ('meute', 'clairiere');

UPDATE unite_type SET ordre = 0 WHERE slug = 'groupe';
UPDATE unite_type SET ordre = 11 WHERE slug IN ('clan', 'feu');
UPDATE unite_type SET ordre = 12 WHERE slug IN ('eqclan', 'eqfeu');
UPDATE unite_type SET ordre = 20 WHERE slug IN ('troupe', 'compagnie');
UPDATE unite_type SET ordre = 21 WHERE slug IN ('hp', 'hpc');
UPDATE unite_type SET ordre = 22 WHERE slug IN ('patrouille', 'patguide');
UPDATE unite_type SET ordre = 30 WHERE slug IN ('meute', 'clairiere');
UPDATE unite_type SET ordre = 31 WHERE slug IN ('sizloup', 'sizlouvette');

UPDATE unite_type SET extra = 'Cri de pat' WHERE slug IN ('hp', 'patrouille', 'hpc', 'patguide');
UPDATE unite_type SET extra = 'Saint patron'
WHERE slug IN ('groupe', 'clan', 'eqclan', 'feu', 'eqfeu', 'troupe', 'compagnie');

EOS
);
    endif;

    $db->exec(<<<'EOS'
UPDATE unite_type SET age_min = NULL, age_max = NULL, sexe = NULL where virtuelle;

DROP TABLE types_unite;

CREATE VIEW vtypes AS
SELECT
	t.id, t.slug, t.nom, t.virtuelle, t.sexe,
	t.age_min AS min, t.age_max AS max, p.nom AS parent
FROM unite_type AS t
LEFT JOIN unite_type AS p on p.id = t.parent
ORDER BY t.ordre;

CREATE TABLE `unite` (
	id		INTEGER		PRIMARY KEY,
	slug		CHAR(128)	UNIQUE NOT NULL,
	parent		INTEGER		REFERENCES unite(id),
	nom		CHAR(128)	NOT NULL,
	`type`		INTEGER		REFERENCES unite_type(id),
	-- cri de pat/équipe/nom de troupe/compagnie/guide
	extra		CHAR(128)	NULL
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

-- chef, assistant, 3e, etc.
CREATE TABLE `unite_role` (
	id		INTEGER		PRIMARY KEY,
	slug		CHAR(16)	UNIQUE,
	type		INTEGER		REFERENCES unites_type(id),
	acl_role	CHAR(16),
	titre		CHAR(64),
	accr		CHAR(6),
	ordre		INT(2),
	nom_jungle	BOOLEAN		DEFAULT 0,
	UNIQUE (slug, type)
);
EOS
);

    if ($SUF):
      $db->exec(<<<'EOS'
-- préparation des données pour migration

UPDATE roles SET accr = 'CF' WHERE id = 'chef' AND type = 'feu';
UPDATE roles SET titre = 'Cheftaine de compagnie' WHERE accr = 'CCie';
UPDATE roles SET titre = 'Assistante cheftaine de compagnie' WHERE accr = 'ACCie';
UPDATE roles SET accr = 'ACR' WHERE id = 'assistant' AND type = 'ronde';
UPDATE roles SET accr = 'GA' WHERE titre = 'guide-aînée';

-- Ze bug :x
UPDATE appartient SET role = 'chef' WHERE unite = 'ronde' AND role = 'siz';
EOS
);
    else:
      $db->exec(<<<'EOS'
-- préparation des données pour migration

UPDATE roles SET titre = 'Chef de clan adjoint', accr = 'CCA' WHERE id = 'assistant' AND type = 'clan';
EOS
);
    endif;

    $db->exec(<<<'EOS'
UPDATE roles SET accr = replace(accr, '.', '');

-- Migration des roles, même s'il y a des titres '
INSERT INTO unite_role
(slug, type, acl_role, titre, accr, ordre)
SELECT
	(CASE WHEN length(roles.accr) > 0 THEN lower(roles.accr) ELSE lower(roles.titre) || '-' || roles.type END),
        (SELECT id FROM unite_type WHERE unite_type.slug = roles.type), id, titre, accr, ordre
FROM roles;

EOS
);

    if ($SUF):
      $db->exec(<<<'EOS'
-- Complément SUF
INSERT INTO unite_role
(slug, acl_role, titre, accr, type)
VALUES
('acc', 'assistant', 'Assistant chef de clan', 'ACC', (SELECT id FROM unite_type WHERE slug = 'clan')),
('acm', 'assistant', 'Assistante d''Akéla', 'ACM', (SELECT id FROM unite_type WHERE unite_type.slug = 'meute')),
('acf', 'assistant', 'Assistante cheftaine de feu', 'ACF', (SELECT id FROM unite_type WHERE unite_type.slug = 'feu')),
('cer', 'chef', 'Chef d''équipe', 'CE', (SELECT id FROM unite_type WHERE slug = 'eqclan')),
('equipier', 'assistant', 'routier', NULL, (SELECT id FROM unite_type WHERE slug = 'eqclan')),
('cef', 'chef', 'Cheftaine d''équipe', 'CE', (SELECT id FROM unite_type WHERE slug = 'eqfeu')),
('equipiere', 'assistant', 'guide-aînée', NULL, (SELECT id FROM unite_type WHERE slug = 'eqfeu'));

UPDATE unite_role SET slug = 'routier' WHERE slug LIKE 'routier%';
UPDATE unite_role SET slug = 'akela' WHERE titre = 'Akéla';
UPDATE unite_role SET slug = 'guillemette' WHERE titre = 'Guillemette';
UPDATE unite_role SET slug = 'sizainier' WHERE slug = 'sizainier-sizloup';
UPDATE unite_role SET slug = 'sizainiere' WHERE slug = 'sizainière-sizjeannette';
UPDATE unite_role SET slug = 'second' WHERE slug = 'second-sizloup';
UPDATE unite_role SET slug = 'seconde' WHERE slug = 'seconde-sizjeannette';
UPDATE unite_role SET slug = REPLACE(slug, 'sizloup', 'louveteau') WHERE slug LIKE '%sizloup';
UPDATE unite_role SET slug = REPLACE(slug, 'sizjeannette', 'jeannette') WHERE slug LIKE '%sizjeannette';

UPDATE unite_role SET nom_jungle = 1 WHERE slug IN ('akela', 'guillemette', 'acm', 'acr');

UPDATE unite_role SET ordre = 0 WHERE slug = 'cg';
UPDATE unite_role SET ordre = 1 WHERE slug = 'acg';
UPDATE unite_role SET ordre = 10 WHERE slug IN ('cc', 'cf');
UPDATE unite_role SET ordre = 11 WHERE slug IN ('acc', 'acf', 'cer', 'cef');
UPDATE unite_role SET ordre = 12 WHERE slug IN ('routier', 'equipier', 'ga', 'equipiere');
UPDATE unite_role SET ordre = 20 WHERE slug IN ('ct', 'ccie');
UPDATE unite_role SET ordre = 21 WHERE slug IN ('act', 'accie');
UPDATE unite_role SET ordre = 30 WHERE slug IN ('akela', 'guillemette');
UPDATE unite_role SET ordre = 31 WHERE slug IN ('acm', 'acr');
UPDATE unite_role SET ordre = 40 WHERE slug IN ('cp', 'ce');
UPDATE unite_role SET ordre = 41 WHERE slug IN ('sp', 'se');
UPDATE unite_role SET ordre = 42 WHERE slug IN ('3e-patrouille', '3e-equipe');
UPDATE unite_role SET ordre = 43 WHERE slug IN ('4e-patrouille', '4e-equipe');
UPDATE unite_role SET ordre = 44 WHERE slug IN ('5e-patrouille', '5e-equipe');
UPDATE unite_role SET ordre = 45 WHERE slug IN ('6e-patrouille', '6e-equipe');
UPDATE unite_role SET ordre = 46 WHERE slug IN ('7e-patrouille', '7e-equipe');
UPDATE unite_role SET ordre = 47 WHERE slug IN ('8e-patrouille', '8e-equipe');
UPDATE unite_role SET ordre = 50 WHERE slug IN ('sizainier', 'sizainiere');
UPDATE unite_role SET ordre = 51 WHERE slug IN ('second', 'seconde');
UPDATE unite_role SET ordre = 52 WHERE slug IN ('3e-louveteau', '3e-jeannette');
UPDATE unite_role SET ordre = 53 WHERE slug IN ('4e-louveteau', '4e-jeannette');
UPDATE unite_role SET ordre = 54 WHERE slug IN ('5e-louveteau', '5e-jeannette');
UPDATE unite_role SET ordre = 55 WHERE slug IN ('6e-louveteau', '6e-jeannette');

EOS
);
    endif;

    $db->exec(<<<'EOS'

CREATE VIEW vroles AS
SELECT r.id, r.slug, r.titre, t.nom, r.accr, acl_role AS acl
FROM unite_role AS r
JOIN unite_type AS t ON t.id = r.type
ORDER BY t.ordre, r.ordre;

-- titre comme Bagheera, Hauviette, aumônier, etc.
CREATE TABLE `unite_titre` (
	id		INTEGER		PRIMARY KEY,
	slug		CHAR(128)	NOT NULL,
	role		INTEGER		REFERENCES unite_role(id),
	nom		CHAR(128)	NOT NULL,
	UNIQUE (role, nom),
	UNIQUE (role, slug)
);
EOS
);

    if ($SUF):
      $db->exec(<<<'EOS'
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
EOS
);
    endif;

    $inscriptions_avant = $db->query('SELECT COUNT(*) FROM appartient;')->fetchColumn();
    $db->exec(<<<'EOS'
CREATE VIEW vtitres AS
SELECT t.id, t.slug, t.nom, unite_role.titre, unite_type.nom AS unite
FROM unite_titre AS t
JOIN unite_role ON unite_role.id = t.role
JOIN unite_type ON unite_type.id = unite_role.type;

CREATE TABLE `appartenance` (
	id		INTEGER		PRIMARY KEY,
	individu	INTEGER		REFERENCES individu(id) NOT NULL,
	unite		INTEGER		REFERENCES unite(id) NOT NULL,
	role		INTEGER		REFERENCES unite_role(id) NOT NULL,
	titre		CHAR(64),
	debut		DATE		NOT NULL,
	fin		DATE		DEFAULT NULL
);

INSERT INTO appartenance
(individu, unite, role, titre, debut, fin)
SELECT DISTINCT individu.id, unite.id, unite_role.id, unite_titre.nom, debut, fin
FROM appartient
JOIN individu ON individu.slug = appartient.individu
JOIN unite ON unite.slug = appartient.unite
JOIN roles ON roles.id = appartient.role AND roles.type = appartient.type
JOIN unite_role ON unite_role.type = unite.type
     AND (CASE
     WHEN roles.titre = 'routier' THEN unite_role.slug = roles.titre
     WHEN appartient.role = 'chef' THEN unite_role.acl_role = appartient.role
     WHEN appartient.role IN ('3e', '4e', '5e', '6e', '7e', '8e', 'siz', 'sec')
     	  THEN unite_role.slug LIKE appartient.role || '%'
     ELSE unite_role.acl_role = 'assistant' -- Pour bagheera, raksha, etc.
END)
JOIN unite_type ON unite_type.id = unite.type
LEFT JOIN unite_titre ON unite_titre.slug = appartient.role;

DROP TABLE roles;

-- suppression des titres (aumônier, bagheera, etc.)
UPDATE unite_role SET acl_role = 'membre' WHERE acl_role LIKE '_e' OR acl_role IN ('siz', 'sec');
DELETE FROM unite_role
WHERE acl_role NOT IN ('chef', 'assistant', 'membre');

CREATE VIEW vappartenances AS
SELECT DISTINCT
	appartenance.id, individu.slug AS individu,
	appartenance.titre, role.titre AS role, unite.nom AS unite
FROM appartenance
JOIN individu ON individu.id = appartenance.individu
JOIN unite_role AS role ON role.id = appartenance.role
JOIN unite ON unite.id = appartenance.unite
ORDER BY individu.naissance ASC;

DROP TABLE appartient;
EOS
);

    $inscriptions_apres = $db->query('SELECT COUNT(*) FROM appartenance;')->fetchColumn();
    if (($diff = $inscriptions_avant - $inscriptions_apres))
      error_log($diff." inscriptions ont été perdues pendant la migration");

    $rootslug = $db->query("SELECT slug FROM unite WHERE parent IS NULL LIMIT 1")->fetchColumn();
    rename('private/unites/intro.wiki', 'private/unites/'.$rootslug.'.wiki');
  }
}
