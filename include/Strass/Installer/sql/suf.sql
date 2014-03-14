INSERT INTO etape
(slug, participe_passe, titre, ordre, sexe, age_min)
VALUES
('promesse', 'a prononcé sa', 'promesse', 0, 'm', 11),
('seconde-classe', 'a obtenue sa', 'seconde classe', 2, 'm', 11),
('premiere-classe', 'a obtenue sa', 'première classe', 3, 'm', 11),
('cr', 'a pris son engagement', 'compagnon-routier', 10, 'h', 16),
('rs', 'a pris son départ', 'routier-scout', 11, 'h', 18),
('flot-jaune', 'a pris son', 'flot jaune', 10, 'f', 16),
('flot-vert', 'a pris son', 'flot vert', 11, 'f', 16),
('ga', 'a pris son', 'flot rouge', 12, 'f', 16);

INSERT INTO unite_type
(slug, nom, parent, virtuelle, sexe, age_min, age_max)
VALUES
('groupe',		'Groupe',		NULL,	0,  'm', 30, 130),
('aines',		'Communauté des aînés',	1,	1,  NULL, NULL, NULL),
('clan',		'Clan',			1,	0,  'h', 17, 30),
('eqclan',		'Équipe',		3,	0,  'h', 16, 30),
('feu',			'Feu',			1,	0,  'f', 17, 30),
('eqfeu',		'Équipe',		5,	0,  'f', 16, 30),
('troupe',		'Troupe',		1,	0,  'h', 18, 30),
('hp',			'Haute-Patrouille',	7,	1,  NULL, NULL, NULL),
('patrouille',		'Patrouille',		7,	0,  'h', 11, 17),
('compagnie',		'Compagnie',		1,	0,  'f', 18, 30),
('he',			'Haute-Équipe',		10,	1,  NULL, NULL, NULL),
('equipe',		'Équipe',		10,	0,  'f', 11, 17),
('meute',		'Meute',		1,	0,  'f', 17, 30),
('sizloup',		'Sizaine',		13,	0,  'h', 7, 12),
('ronde',		'Ronde',		1,	0,  'f', 17, 30),
('sizjeannette',	'Sizaine',		15,	0,  'f', 7, 12);

UPDATE unite_type SET accr_we = 'WEG', nom_we = 'Weekend de groupe' WHERE slug = 'groupe';
UPDATE unite_type SET accr_we = 'WEA', nom_we = 'Weekend aînés' WHERE slug = 'aines';
UPDATE unite_type SET accr_we = 'WEC', nom_we = 'Weekend de clan', accr_camp = 'Route', nom_camp = 'Route'
WHERE slug = 'clan';
UPDATE unite_type SET accr_we = 'WEF', nom_we = 'Weekend de feu' WHERE slug = 'feu';
UPDATE unite_type SET accr_we = 'WEE', nom_we = 'Weekend d''équipe' WHERE slug IN ('eqclan', 'eqfeu', 'equipe');
UPDATE unite_type SET accr_we = 'WET', nom_we = 'Weekend de troupe' WHERE slug = 'troupe';
UPDATE unite_type SET accr_we = 'WEHP', nom_we = 'Weekend HP', nom_camp = 'Camp HP' WHERE slug = 'hp';
UPDATE unite_type SET accr_we = 'WEP', nom_we = 'Weekend de patrouille' WHERE slug = 'patrouille';
UPDATE unite_type SET accr_we = 'WECie', nom_we = 'Weekend de compagnie' WHERE slug = 'compagnie';
UPDATE unite_type SET accr_we = 'WEHE', nom_we = 'Weekend HE', nom_camp = 'Camp HE' WHERE slug = 'he';
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

INSERT INTO unite_role
(slug, titre, accr, type, acl_role)
VALUES
('cg',		'chef de groupe',		'CG',	1,	'chef'),	-- 1
('acg',		'assistant chef de groupe',	'ACG',	1,	'assistant'),
('cc',		'chef de clan',			'CC',	3,	'chef'),
('acc',		'assistant chef de clan',	'ACC',	3,	'assistant'),
('routier',	'routier',			'SR',	3,	'assistant'),	-- 5
('cer',		'Chef d''équipe',		'CE',	4,	'chef'),
('equipier',	'routier',			'SR',	4,	'assistant'),
('cf',		'cheftaine de feu',		'CF',	5,	'chef'),
('acf',		'Assistante cheftaine de feu',	'ACF',	5,	'chef'),
('ga',		'guide-aînée',			'GA',	5,	'assistant'),	-- 10
('cef',		'Cheftaine d''équipe',		'CE',	6,	'chef'),
('equipiere',	'guide-aînée',			'GA',	6,	'assistant'),
('ct',		'chef de troupe',		'CT',	7,	'chef'),
('act',		'assistant chef de troupe',	'ACT',	7,	'assistant'),
('cp',		'chef de patrouille',		'CP',	9,	'chef'),	-- 15
('sp',		'second de patrouille',		'SP',	9,	'assistant'),
('3e-patrouille','3e',				NULL,	9,	'membre'),
('4e-patrouille','4e',				NULL,	9,	'membre'),
('5e-patrouille','5e',				NULL,	9,	'membre'),
('6e-patrouille','6e',				NULL,	9,	'membre'),	-- 20
('7e-patrouille','7e',				NULL,	9,	'membre'),
('8e-patrouille','8e',				NULL,	9,	'membre'),
('ccie',	'cheftaine de compagnie',	'CCie',	10,	'chef'),
('accie',	'assistante cheftaine de compagnie','ACCie',10,	'chef'),
('ce',		'cheftaine d''équipe',		'CE',	12,	'chef'),	-- 25
('se',		'seconde d''équipe',		'SE',	12,	'assistant'),
('3e-equipe',	'3e',				NULL,	12,	'membre'),
('4e-equipe',	'4e',				NULL,	12,	'membre'),
('5e-equipe',	'5e',				NULL,	12,	'membre'),
('6e-equipe',	'6e',				NULL,	12,	'membre'),	-- 30
('7e-equipe',	'7e',				NULL,	12,	'membre'),
('8e-equipe',	'8e',				NULL,	12,	'membre'),
('akela',	'Akéla',			NULL,	13, 	'chef'),
('acm',		'Assistante d''Akéla',		'ACM',	13,	'chef'),
('sizainier',	'sizainier',		NULL,	14,	'membre'),	-- 35
('second',	'second',			NULL,	14,	'membre'),
('3e-louveteau','3e',				NULL,	14,	'membre'),
('4e-louveteau','4e',				NULL,	14,	'membre'),
('5e-louveteau','5e',				NULL,	14,	'membre'),
('6e-louveteau','6e',				NULL,	14,	'membre'),	-- 40
('guillemette',	'Guillemette',			NULL,	15,	'chef'),
('acr',		'assistante de Guillemette',	NULL,	15,	'chef'),
('sizainiere',	'sizainière',		NULL,	16,	'membre'),
('seconde',	'seconde',			NULL,	16,	'membre'),
('3e-jeannette','3e',				NULL,	16,	'membre'),	-- 45
('4e-jeannette','4e',				NULL,	16,	'membre'),
('5e-jeannette','5e',				NULL,	16,	'membre'),
('6e-jeannette','6e',				NULL,	16,	'membre');

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

INSERT INTO unite_titre
(slug, nom, role)
VALUES
('aumonier-g',	'Aumônier',	2),
('tresorier',	'Trésorier',	2),
('materialiste','Matérialiste',	2),
('secretaire',	'Secrétaire',	2),
('aumonier-c',	'Aumônier',	4),	--  clan
('aumonier-f',	'Aumônier',	8),	--  feu
('aumonier-t',	'Aumônier',	14),	--  troupe
('aumonier-ccie','Aumônier',	24),	--  compagnie
('aumonier-m',	'Aumônier',	34),	--  meute
('ahdeek',	'Ahdeek',	34),
('baloo',	'Baloo',	34),
('bagheera',	'Bagheera',	34),
('chikai',	'Chikaï',	34),
('chil',	'Chil',		34),
('chunchundra',	'Chunchundra',	34),
('dahinda',	'Dahinda',	34),
('darzee',	'Darzee',	34),
('ferao',	'Ferao',	34),
('gris',	'Frère-Gris',	34),
('hathi',	'Hathi',	34),
('jacala',	'jacala',	34),
('kaa',		'Kaa',		34),
('keego',	'Keego',	34),
('keneu',	'Keneu',	34),
('ko',		'Ko',		34),
('kotick',	'Kotick',	34),
('lardaki',	'Lardaki',	34),
('louie',	'Roi-Louie',	34),
('mang',	'Mang',		34),
('mor',		'Mor',		34),
('mysa',	'Mysa',		34),
('nag',		'Nag',		34),
('oo',		'Oo',		34),
('oonai',	'Oonaï',	34),
('phao',	'Phao',		34),
('phaona',	'Phaona',	34),
('pukeena',	'Pukeena',	34),
('raksha',	'Raksha',	34),
('rama',	'Rama',		34),
('rikki',	'Rikki Tiki Tavi',34),
('sahi',	'Sahi',		34),
('shada',	'Shada',	34),
('shawshaw',	'Shaw Shaw',	34),
('singum',	'Singum',	34),
('sona',	'Sona',		34),
('tegumai',	'Tegumaï',	34),
('tha',		'Thâ',		34),
('thuu',	'Thuu',		34),
('wontolla',	'Won-Tolla',	34),
('aumonier-r',	'aumônier',	41),	--  ronde
('zabilet',	'Zabilet',	41),
('mengette',	'Mengette',	41),
('hauviette',	'Hauviette',	41),
('isabelette',	'Isabellette',	41),
('colette',	'Nicolette',	41);
