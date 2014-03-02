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
('ga', 'a pris son', 'flot rouge', 11, 'f', 16);

INSERT INTO unite_type
(slug, nom, parent, virtuelle, ordre, sexe, age_min, age_max)
VALUES
('groupe',		'groupe',		NULL,	0, 0,  'm', 30, 130),
('aines',		'commauté des aînés',	1,	1, 1,  NULL, NULL, NULL),
('clan',		'clan',			1,	0, 1,  'h', 17, 30),
('eqclan',		'équipe',		3,	0, 1,  'h', 16, 30),
('feu',			'feu',			1,	0, 1,  'f', 17, 30),
('eqfeu',		'équipe',		5,	0, 1,  'f', 16, 30),
('troupe',		'troupe',		1,	0, 2,  'h', 18, 30),
('hp',			'haute-patrouille',	7,	1, 3,  NULL, NULL, NULL),
('patrouille',		'patrouille',		7,	0, 4,  'h', 11, 17),
('compagnie',		'compagnie',		1,	0, 2,  'f', 18, 30),
('he',			'haute-équipe',		10,	1, 3,  NULL, NULL, NULL),
('equipe',		'équipe',		10,	0, 4,  'f', 11, 17),
('meute',		'meute',		1,	0, 5,  'f', 17, 30),
('sizloup',		'sizaine',		13,	0, 6,  'h', 7, 12),
('ronde',		'ronde',		1,	0, 5,  'f', 17, 30),
('sizjeannette',	'sizaine',		15,	0, 6,  'f', 7, 12);

INSERT INTO unite_role
(slug, titre, accr, type, acl_role, ordre)
VALUES
('cg',		'chef de groupe',		'CG',	1,	'chef',		0),	-- 1
('acg',		'assistant chef de groupe',	'ACG',	1,	'assistant',	1),
('cc',		'chef de clan',			'CC',	3,	'chef',		10),
('acc',		'assistant chef de clan',	'ACC',	3,	'assistant',	11),
('routier',	'routier',			'SR',	3,	'assistant',	12),	-- 5
('cer',		'Chef d''équipe',		'CE',	4,	'chef',		11),
('equipier',	'routier',			'SR',	4,	'assistant',	12),
('cf',		'cheftaine de feu',		'CF',	5,	'chef',		10),
('acf',		'Assistante cheftaine de feu',	'ACF',	5,	'chef',		11),
('ga',		'guide-aînée',			'GA',	5,	'assistant',	12),	-- 10
('cef',		'Cheftaine d''équipe',		'CE',	6,	'chef',		11),
('equipiere',	'guide-aînée',			'GA',	6,	'assistant',	12),
('ct',		'chef de troupe',		'CT',	7,	'chef',		20),
('act',		'assistant chef de troupe',	'ACT',	7,	'assistant',	21),
('cp',		'chef de patrouille',		'CP',	9,	'chef',		40),	-- 15
('sp',		'second de patrouille',		'SP',	9,	'assistant',	41),
('3e-patrouille','3e',				NULL,	9,	'membre',	42),
('4e-patrouille','4e',				NULL,	9,	'membre',	43),
('5e-patrouille','5e',				NULL,	9,	'membre',	44),
('6e-patrouille','6e',				NULL,	9,	'membre',	45),	-- 20
('7e-patrouille','7e',				NULL,	9,	'membre',	46),
('8e-patrouille','8e',				NULL,	9,	'membre',	46),
('ccie',	'cheftaine de compagnie',	'CCie',	10,	'chef',		20),
('accie',	'assistante cheftaine de compagnie','ACCie',10,	'chef',		21),
('ce',		'cheftaine d''équipe',		'CE',	12,	'chef',		40),	-- 25
('se',		'seconde d''équipe',		'SE',	12,	'assistant',	41),
('3e-equipe',	'3e',				NULL,	12,	'membre',	42),
('4e-equipe',	'4e',				NULL,	12,	'membre',	43),
('5e-equipe',	'5e',				NULL,	12,	'membre',	44),
('6e-equipe',	'6e',				NULL,	12,	'membre',	45),	-- 30
('7e-equipe',	'7e',				NULL,	12,	'membre',	46),
('8e-equipe',	'8e',				NULL,	12,	'membre',	47),
('akela',	'Akéla',			NULL,	13, 	'chef',		30),
('acm',		'Assistante d''Akéla',		'ACM',	13,	'chef',		31),
('sizainier-louveteau','sizainier',		NULL,	14,	'membre',	50),	-- 35
('second-louveteau','second',			NULL,	14,	'membre',	51),
('3e-louveteau','3e',				NULL,	14,	'membre',	52),
('4e-louveteau','4e',				NULL,	14,	'membre',	53),
('5e-louveteau','5e',				NULL,	14,	'membre',	54),
('6e-louveteau','6e',				NULL,	14,	'membre',	55),	-- 40
('guillemette',	'Guillemette',			NULL,	15,	'chef',		30),
('acr',		'assistante de Guillemette',	NULL,	15,	'chef',		31),
('sizainiere-jeannette','sizainière',		NULL,	16,	'membre',	50),
('seconde-jeannette','seconde',			NULL,	16,	'membre',	51),
('3e-jeannette','3e',				NULL,	16,	'membre',	52),	-- 45
('4e-jeannette','4e',				NULL,	16,	'membre',	53),
('5e-jeannette','5e',				NULL,	16,	'membre',	54),
('6e-jeannette','6e',				NULL,	16,	'membre',	55);

INSERT INTO unite_titre
(slug, nom, role)
VALUES
('aumonier-g',	'aumônier',	2),
('tresorier',	'trésorier',	2),
('materialiste','matérialiste',	2),
('secretaire',	'secrétaire',	2),
('aumonier-c',	'aumônier',	4),	--  clan
('aumonier-f',	'aumônier',	8),	--  feu
('aumonier-t',	'aumônier',	14),	--  troupe
('aumonier-ccie','aumônier',	24),	--  compagnie
('aumonier-m',	'aumônier',	34),	--  meute
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
