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
-- copie-collé de l'installeur

INSERT INTO unite_type
(slug, nom, parent, virtuelle, sexe, age_min, age_max)
VALUES
('groupe',		'Groupe',		NULL,	0,  'm', 30, 130),
('clan',		'Clan',			NULL,	0,  'h', 17, 50),
('eqclan',		'Équipe',		2,	0,  'h', 16, 30),
('feu',			'Feu',			NULL,	0,  'f', 17, 30),
('eqfeu',		'Équipe',		4,	0,  'f', 16, 30),
('troupe',		'Troupe',		1,	0,  'h', 18, 30),
('hp',			'Haute-Patrouille',	6,	1,  NULL, NULL, NULL),
('patrouille',		'Patrouille',		6,	0,  'h', 11, 17),
('compagnie',		'Compagnie',		1,	0,  'f', 18, 30),
('hpc',			'Haute-Patrouille',	9,	1,  NULL, NULL, NULL),
('patguide',		'Patrouille',		9,	0,  'f', 11, 17),
('meute',		'Meute',		1,	0,  'm', 17, 30),
('sizloup',		'Sizaine',		12,	0,  'h', 7, 12),
('clairiere',		'Clairière',		1,	0,  'f', 17, 30),
('sizlouvette',		'Sizaine',		14,	0,  'f', 7, 12);

UPDATE unite_type SET accr_we = 'WEG', nom_we = 'Weekend de groupe' WHERE slug = 'groupe';
UPDATE unite_type SET accr_we = 'WEC', nom_we = 'Weekend de clan', accr_camp = 'Route', nom_camp = 'Route'
WHERE slug = 'clan';
UPDATE unite_type SET accr_we = 'WEF', nom_we = 'Weekend de feu' WHERE slug = 'feu';
UPDATE unite_type SET accr_we = 'WEE', nom_we = 'Weekend d''équipe' WHERE slug IN ('eqclan', 'eqfeu');
UPDATE unite_type SET accr_we = 'WET', nom_we = 'Weekend de troupe' WHERE slug = 'troupe';
UPDATE unite_type SET accr_we = 'WECie', nom_we = 'Weekend de compagnie' WHERE slug = 'compagnie';
UPDATE unite_type SET accr_we = 'WEHP', nom_we = 'Weekend HP', accr_camp = 'Camp HP', nom_camp = 'Camp HP'
WHERE slug IN ('hp', 'hpc');
UPDATE unite_type SET accr_we = 'WEP', nom_we = 'Weekend de patrouille'
WHERE slug IN ('patrouille', 'patguide');
UPDATE unite_type SET nom_sortie = 'Chasse', nom_we = 'Grand chasse', nom_camp = 'Grande chasse',
       accr_sortie = 'Chasse', accr_we = 'Grande chasse', accr_camp = 'Grande chasse'
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
-- Ze bug :x
UPDATE appartient SET role = 'chef' WHERE unite = 'ronde' AND role = 'siz';
EOS
);
    else: // FSE
      $db->exec(<<<'EOS'
-- Complément FSE

INSERT INTO unite_role
(slug, titre, accr, type, acl_role, ordre)
VALUES
('cg',		'Chef de groupe',		'CG',	1,	'chef',		0),	-- 1
('acg',		'Assistant chef de groupe',	'ACG',	1,	'assistant',	1),
('cc',		'Chef de clan',			'CC',	2,	'chef',		10),
('cca',		'Chef de clan adjoint',		'CCA',	2,	'assistant',	11),
('acc',		'Chef d''équipe',		'ACC',	3,	'chef',		11),	-- 5
('equipier',	'Routier',			'SR',	3,	'assistant',	12),
('cf',		'Cheftaine de feu',		'CF',	4,	'chef',		10),
('cfa',		'Cheftaine de feu adjointe',	'CFA',	4,	'chef',		11),
('acf',		'Cheftaine d''équipe',		'ACF',	5,	'chef',		11),
('equipiere',	'Guide-aînée',			'GA',	5,	'assistant',	12),	-- 10
('ct',		'Chef de troupe',		'CT',	6,	'chef',		20),
('act',		'Assistant chef de troupe',	'ACT',	6,	'assistant',	21),
('cp',		'Chef de patrouille',		'CP',	8,	'chef',		40),
('sp',		'Second de patrouille',		'SP',	8,	'assistant',	41),
('3e-patrouille','3e',				NULL,	8,	'membre',	42),	-- 15
('4e-patrouille','4e',				NULL,	8,	'membre',	43),
('5e-patrouille','5e',				NULL,	8,	'membre',	44),
('6e-patrouille','6e',				NULL,	8,	'membre',	45),
('7e-patrouille','7e',				NULL,	8,	'membre',	46),
('8e-patrouille','8e',				NULL,	8,	'membre',	46),	-- 20
('ccie',	'Cheftaine de compagnie',	'CCie',	9,	'chef',		20),
('accie',	'Assistante cheftaine de compagnie','ACCie',9,	'chef',		21),
('ce',		'Cheftaine d''équipe',		'CE',	11,	'chef',		40),
('se',		'Seconde d''équipe',		'SE',	11,	'assistant',	41),
('3e-equipe',	'3e',				NULL,	11,	'membre',	42),	-- 25
('4e-equipe',	'4e',				NULL,	11,	'membre',	43),
('5e-equipe',	'5e',				NULL,	11,	'membre',	44),
('6e-equipe',	'6e',				NULL,	11,	'membre',	45),
('7e-equipe',	'7e',				NULL,	11,	'membre',	46),
('8e-equipe',	'8e',				NULL,	11,	'membre',	47),	-- 30
('akela',	'Akéla',			NULL,	12, 	'chef',		30),
('acm',		'Assistant d''Akéla',		'ACM',	12,	'chef',		31),
('sizainier',	'Sizainier',			NULL,	13,	'membre',	50),
('second',	'Second',			NULL,	13,	'membre',	51),
('3e-louveteau','3e',				NULL,	13,	'membre',	52),	-- 35
('4e-louveteau','4e',				NULL,	13,	'membre',	53),
('5e-louveteau','5e',				NULL,	13,	'membre',	54),
('6e-louveteau','6e',				NULL,	13,	'membre',	55),
('akelaf',	'Akéla',			NULL,	14,	'chef',		30),
('accl',	'Assistante d''Akéla',		NULL,	14,	'chef',		31),	-- 40
('sizainiere',	'Sizainière',			NULL,	15,	'membre',	50),
('seconde',	'Seconde',			NULL,	15,	'membre',	51),
('3e-louvette','3e',				NULL,	15,	'membre',	52),
('4e-louvette','4e',				NULL,	15,	'membre',	53),
('5e-louvette','5e',				NULL,	15,	'membre',	54),	-- 45
('6e-louvette','6e',				NULL,	15,	'membre',	55);

UPDATE unite_role SET ordre = 0 WHERE slug = 'cg';
UPDATE unite_role SET ordre = 1 WHERE slug = 'acg';
UPDATE unite_role SET ordre = 10 WHERE slug IN ('cc', 'cf');
UPDATE unite_role SET ordre = 11 WHERE slug IN ('cca', 'cfa', 'acc', 'acf');
UPDATE unite_role SET ordre = 12 WHERE slug IN ('routier', 'equipier', 'ga', 'equipiere');
UPDATE unite_role SET ordre = 20 WHERE slug IN ('ct', 'ccie');
UPDATE unite_role SET ordre = 21 WHERE slug IN ('act', 'accie');
UPDATE unite_role SET ordre = 30 WHERE slug IN ('akela', 'akelaf');
UPDATE unite_role SET ordre = 31 WHERE slug IN ('acm', 'accl');
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
UPDATE unite_role SET ordre = 52 WHERE slug IN ('3e-louveteau', '3e-louvette');
UPDATE unite_role SET ordre = 53 WHERE slug IN ('4e-louveteau', '4e-louvette');
UPDATE unite_role SET ordre = 54 WHERE slug IN ('5e-louveteau', '5e-louvette');
UPDATE unite_role SET ordre = 55 WHERE slug IN ('6e-louveteau', '6e-louvette');

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
    else: // FSE
    $db->exec(<<<'EOS'

INSERT INTO unite_titre
(slug, nom, role)
VALUES
('aumonier-g',	'CR',		2),
('tresorier',	'Trésorier',	2),
('materialiste','Matérialiste',	2),
('secretaire',	'Secrétaire',	2),
('aumonier-c',	'CR',		4),	--  clan
('aumonier-f',	'CR',		8),	--  feu
('aumonier-t',	'CR',		12),	--  troupe
('aumonier-ccie','CR',		22),	--  compagnie
('aumonier-m',	'CR',		32),	--  meute
('ahdeek',	'Ahdeek',	32),
('baloo',	'Baloo',	32),
('bagheera',	'Bagheera',	32),
('chikai',	'Chikaï',	32),
('chil',	'Chil',		32),
('chunchundra',	'Chunchundra',	32),
('dahinda',	'Dahinda',	32),
('darzee',	'Darzee',	32),
('ferao',	'Ferao',	32),
('gris',	'Frère-Gris',	32),
('hathi',	'Hathi',	32),
('jacala',	'jacala',	32),
('kaa',		'Kaa',		32),
('keego',	'Keego',	32),
('keneu',	'Keneu',	32),
('ko',		'Ko',		32),
('kotick',	'Kotick',	32),
('lardaki',	'Lardaki',	32),
('louie',	'Roi-Louie',	32),
('mang',	'Mang',		32),
('mor',		'Mor',		32),
('mysa',	'Mysa',		32),
('nag',		'Nag',		32),
('oo',		'Oo',		32),
('oonai',	'Oonaï',	32),
('phao',	'Phao',		32),
('phaona',	'Phaona',	32),
('pukeena',	'Pukeena',	32),
('raksha',	'Raksha',	32),
('rama',	'Rama',		32),
('rikki',	'Rikki Tiki Tavi',32),
('sahi',	'Sahi',		32),
('shada',	'Shada',	32),
('shawshaw',	'Shaw Shaw',	32),
('singum',	'Singum',	32),
('sona',	'Sona',		32),
('tegumai',	'Tegumaï',	32),
('tha',		'Thâ',		32),
('thuu',	'Thuu',		32),
('wontolla',	'Won-Tolla',	32),
('aumonier-m',	'CR',		40),	--  clairière
('ahdeek',	'Ahdeek',	40),
('baloo',	'Baloo',	40),
('bagheera',	'Bagheera',	40),
('chikai',	'Chikaï',	40),
('chil',	'Chil',		40),
('chunchundra',	'Chunchundra',	40),
('dahinda',	'Dahinda',	40),
('darzee',	'Darzee',	40),
('ferao',	'Ferao',	40),
('gris',	'Frère-Gris',	40),
('hathi',	'Hathi',	40),
('jacala',	'jacala',	40),
('kaa',		'Kaa',		40),
('keego',	'Keego',	40),
('keneu',	'Keneu',	40),
('ko',		'Ko',		40),
('kotick',	'Kotick',	40),
('lardaki',	'Lardaki',	40),
('louie',	'Roi-Louie',	40),
('mang',	'Mang',		40),
('mor',		'Mor',		40),
('mysa',	'Mysa',		40),
('nag',		'Nag',		40),
('oo',		'Oo',		40),
('oonai',	'Oonaï',	40),
('phao',	'Phao',		40),
('phaona',	'Phaona',	40),
('pukeena',	'Pukeena',	40),
('raksha',	'Raksha',	40),
('rama',	'Rama',		40),
('rikki',	'Rikki Tiki Tavi',40),
('sahi',	'Sahi',		40),
('shada',	'Shada',	40),
('shawshaw',	'Shaw Shaw',	40),
('singum',	'Singum',	40),
('sona',	'Sona',		40),
('tegumai',	'Tegumaï',	40),
('tha',		'Thâ',		40),
('thuu',	'Thuu',		40),
('wontolla',	'Won-Tolla',	40);

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
     WHEN roles.titre = 'routier' AND roles.type = 'clan' THEN unite_role.slug = roles.titre
     WHEN roles.titre = 'routier' AND roles.type = 'eqclan' THEN unite_role.slug = 'equipier'
     WHEN appartient.role = 'chef' THEN unite_role.acl_role = appartient.role
     WHEN appartient.role IN ('3e', '4e', '5e', '6e', '7e', '8e', 'siz', 'sec')
     	  THEN unite_role.slug LIKE appartient.role || '%'
     ELSE unite_role.acl_role = 'assistant' -- Pour bagheera, raksha, etc.
END)
JOIN unite_type ON unite_type.id = unite.type
LEFT JOIN unite_titre ON unite_titre.role = unite_role.id
     AND unite_titre.slug LIKE appartient.role || '%'
ORDER BY debut;

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
    $diff = $inscriptions_avant - $inscriptions_apres;
    if ($diff > 0)
      error_log($diff." inscriptions ont été perdues pendant la migration");
    elseif ($diff < 0)
      throw new Exception(-1 * $diff . " inscriptions générées !!");

    $rootslug = $db->query("SELECT slug FROM unite WHERE parent IS NULL LIMIT 1")->fetchColumn();
    rename('private/unites/intro.wiki', 'private/unites/'.$rootslug.'.wiki');
  }
}
