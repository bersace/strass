INSERT INTO etape
(slug, participe_passe, titre, ordre, sexe, age_min)
VALUES
('promesse', 'a prononcé sa', 'promesse', 0, 'm', 11),
('seconde-classe', 'a obtenue sa', 'seconde classe', 2, 'm', 11),
('premiere-classe', 'a obtenue sa', 'première classe', 3, 'm', 11),
('ep', 'a été admis', 'équipier-pilote', 10, 'h', 16),
('rp', 'a pris son engagement', 'routier-pilote', 11, 'h', 16),
('rs', 'a pris son départ', 'routier-scout', 12, 'h', 18),
('flot-jaune', 'a pris son', 'flot jaune', 10, 'f', 16),
('flot-vert', 'a pris son', 'flot vert', 11, 'f', 16),
('ga', 'a pris son', 'flot rouge', 12, 'f', 16);

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
WHERE slug = 'meute';

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

INSERT INTO unite_role
(slug, titre, accr, type, acl_role, ordre)
VALUES
('cg',		'Chef de groupe',		'CG',	1,	'chef',		0),	-- 1
('acg',		'Assistant chef de groupe',	'ACG',	1,	'assistant',	1),
('cc',		'Chef de clan',			'CC',	2,	'chef',		10),
('cca',		'Chef de clan adjoint',		'CCA',	2,	'assistant',	11),
('acc',		'Chef d''équipe',		'ACC',	3,	'chef',		11),
('equipier',	'Routier',			'SR',	3,	'assistant',	12),
('cf',		'Cheftaine de feu',		'CF',	3,	'chef',		10),
('cfa',		'Cheftaine de feu adjointe',	'CFA',	4,	'chef',		11),
('acf',		'Cheftaine d''équipe',		'ACF',	5,	'chef',		11),
('equipiere',	'Guide-aînée',			'GA',	5,	'assistant',	12),
('ct',		'Chef de troupe',		'CT',	6,	'chef',		20),
('act',		'Assistant chef de troupe',	'ACT',	6,	'assistant',	21),
('cp',		'Chef de patrouille',		'CP',	8,	'chef',		40),	-- 15
('sp',		'Second de patrouille',		'SP',	8,	'assistant',	41),
('3e-patrouille','3e',				NULL,	8,	'membre',	42),
('4e-patrouille','4e',				NULL,	8,	'membre',	43),
('5e-patrouille','5e',				NULL,	8,	'membre',	44),
('6e-patrouille','6e',				NULL,	8,	'membre',	45),	-- 20
('7e-patrouille','7e',				NULL,	8,	'membre',	46),
('8e-patrouille','8e',				NULL,	8,	'membre',	46),
('ccie',	'Cheftaine de compagnie',	'CCie',	9,	'chef',		20),
('accie',	'Assistante cheftaine de compagnie','ACCie',9,	'chef',		21),
('ce',		'Cheftaine d''équipe',		'CE',	11,	'chef',		40),	-- 25
('se',		'Seconde d''équipe',		'SE',	11,	'assistant',	41),
('3e-equipe',	'3e',				NULL,	11,	'membre',	42),
('4e-equipe',	'4e',				NULL,	11,	'membre',	43),
('5e-equipe',	'5e',				NULL,	11,	'membre',	44),
('6e-equipe',	'6e',				NULL,	11,	'membre',	45),	-- 30
('7e-equipe',	'7e',				NULL,	11,	'membre',	46),
('8e-equipe',	'8e',				NULL,	11,	'membre',	47),
('akela',	'Akéla',			NULL,	12, 	'chef',		30),
('acm',		'Assistant d''Akéla',		'ACM',	12,	'chef',		31),
('sizainier',	'Sizainier',		NULL,	13,	'membre',	50),	-- 35
('second',	'Second',			NULL,	13,	'membre',	51),
('3e-louveteau','3e',				NULL,	13,	'membre',	52),
('4e-louveteau','4e',				NULL,	13,	'membre',	53),
('5e-louveteau','5e',				NULL,	13,	'membre',	54),
('6e-louveteau','6e',				NULL,	13,	'membre',	55),	-- 40
('akelaf',	'Akéla',			NULL,	14,	'chef',		30),
('accl',	'Assistante d''Akéla',		NULL,	14,	'chef',		31),
('sizainiere',	'Sizainière',		NULL,	15,	'membre',	50),
('seconde',	'Seconde',			NULL,	15,	'membre',	51),
('3e-louvette','3e',				NULL,	15,	'membre',	52),	-- 45
('4e-louvette','4e',				NULL,	15,	'membre',	53),
('5e-louvette','5e',				NULL,	15,	'membre',	54),
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
('aumonier-m',	'aumônier',	42),	--  clairière
('ahdeek',	'Ahdeek',	42),
('baloo',	'Baloo',	42),
('bagheera',	'Bagheera',	42),
('chikai',	'Chikaï',	42),
('chil',	'Chil',		42),
('chunchundra',	'Chunchundra',	42),
('dahinda',	'Dahinda',	42),
('darzee',	'Darzee',	42),
('ferao',	'Ferao',	42),
('gris',	'Frère-Gris',	42),
('hathi',	'Hathi',	42),
('jacala',	'jacala',	42),
('kaa',		'Kaa',		42),
('keego',	'Keego',	42),
('keneu',	'Keneu',	42),
('ko',		'Ko',		42),
('kotick',	'Kotick',	42),
('lardaki',	'Lardaki',	42),
('louie',	'Roi-Louie',	42),
('mang',	'Mang',		42),
('mor',		'Mor',		42),
('mysa',	'Mysa',		42),
('nag',		'Nag',		42),
('oo',		'Oo',		42),
('oonai',	'Oonaï',	42),
('phao',	'Phao',		42),
('phaona',	'Phaona',	42),
('pukeena',	'Pukeena',	42),
('raksha',	'Raksha',	42),
('rama',	'Rama',		42),
('rikki',	'Rikki Tiki Tavi',42),
('sahi',	'Sahi',		42),
('shada',	'Shada',	42),
('shawshaw',	'Shaw Shaw',	42),
('singum',	'Singum',	42),
('sona',	'Sona',		42),
('tegumai',	'Tegumaï',	42),
('tha',		'Thâ',		42),
('thuu',	'Thuu',		42),
('wontolla',	'Won-Tolla',	42);
