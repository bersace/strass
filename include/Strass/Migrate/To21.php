<?php /*-*- sql -*-*/
/*
 * Ajout des branches pédagogiques.
 */

class Strass_Migrate_To21 extends Strass_MigrateHandler {
    function online($db) {
        $db->exec(<<<'EOS'
--

CREATE TABLE branche (
    id       INTEGER    PRIMARY KEY,
    slug     CHAR(64)   UNIQUE,
    nom      CHAR(64)   UNIQUE,
    couleur  CHAR(64),
    ordre    INTEGER,
    sexe     CHAR(1)
);

ALTER TABLE unite_type RENAME TO tmp;

CREATE TABLE `unite_type`
(
    id                  INTEGER         PRIMARY KEY,
    slug                CHAR(32)        NOT NULL UNIQUE,
    branche             INTEGER         REFERENCES branche(id),
    parent              INTEGER         REFERENCES unite_type(id),
    virtuelle           BOOLEAN         DEFAULT 0,
    nom                 CHAR(32)        NOT NULL,
    -- Comment s'appelle l'extra ? Cri de pat ? Saint Patron ?
    extra               CHAR(32)        DEFAULT NULL,
    ordre               INT(2),
    sexe                CHAR(1),
    age_min             INT(4),
    age_max             INT(4),
    nom_reunion         CHAR(16)        DEFAULT 'Réunion',
    nom_sortie          CHAR(16)        DEFAULT 'Sortie',
    nom_we              CHAR(16)        DEFAULT 'Weekend',
    nom_camp            CHAR(16)        DEFAULT 'Camp',
    accr_reunion        CHAR(8)         DEFAULT 'Réunion',
    accr_sortie         CHAR(8)         DEFAULT 'Sortie',
    accr_we             CHAR(8)         DEFAULT 'WE',
    accr_camp           CHAR(8)         DEFAULT 'Camp'
);

INSERT INTO unite_type
(slug, parent, virtuelle, nom, extra, ordre, sexe, age_min, age_max,
 nom_reunion, nom_sortie, nom_we, nom_camp,
 accr_reunion, accr_sortie, accr_we, accr_camp)
SELECT DISTINCT
    slug, parent, virtuelle, nom, extra, ordre, sexe, age_min, age_max,
    nom_reunion, nom_sortie, nom_we, nom_camp,
    accr_reunion, accr_sortie, accr_we, accr_camp
FROM tmp
ORDER BY id;
DROP TABLE tmp;

DROP VIEW vunites;
CREATE VIEW vunites AS
SELECT u.id, u.slug, t.nom AS type, b.slug as branche, u.nom, u.extra
FROM unite AS u
JOIN unite_type AS t ON t.id = u.type
LEFT JOIN branche AS b on b.id = t.branche;

EOS
);

        $config = new Strass_Config_Php('strass');
        $association = $config->system->association or $config->system->mouvement;
        if ($association == 'suf') {
            $db->exec(<<<'EOS'
--

INSERT INTO branche
(couleur, ordre, slug, nom, sexe)
VALUES
('jaune', 1, 'louveteau', 'Les louveteaux', 'h'),
('jaune', 1, 'jeannette', 'Les jeannettes', 'f'),
('verte', 2, 'eclaireur', 'Les éclaireurs', 'h'),
('verte', 2, 'guide', 'Les guides', 'f'),
('rouge', 3, 'routier', 'Les routiers', 'h'),
('rouge', 3, 'guide-ainee', 'Les guides-aînées', 'f');

UPDATE unite_type SET branche = 1 WHERE slug IN ('meute', 'sizloup');
UPDATE unite_type SET branche = 2 WHERE slug IN ('ronde', 'sizjeannette');
UPDATE unite_type SET branche = 3 WHERE slug IN ('troupe', 'hp', 'patrouille');
UPDATE unite_type SET branche = 4 WHERE slug IN ('compagnie', 'he', 'equipe');
UPDATE unite_type SET branche = 5 WHERE slug IN ('clan', 'eqclan');
UPDATE unite_type SET branche = 6 WHERE slug IN ('feu', 'eqfeu');

EOS
);
        } else if ($association == 'fse') {
            $db->exec(<<<'EOS'
--

INSERT INTO branche
(couleur, ordre, slug, nom, sexe)
VALUES
('jaune', 1, 'louveteau', 'Les louveteaux', 'h'),
('jaune', 1, 'louvette', 'Les louvettes', 'f'),
('verte', 2, 'eclaireur', 'Les éclaireurs', 'h'),
('verte', 2, 'guide', 'Les guides', 'f'),
('rouge', 3, 'routier', 'Les routiers', 'h'),
('rouge', 3, 'guide-ainee', 'Les guides-aînées', 'f');

UPDATE unite_type SET branche = 1 WHERE slug IN ('meute', 'sizloup');
UPDATE unite_type SET branche = 2 WHERE slug IN ('clairiere', 'sizlouvette');
UPDATE unite_type SET branche = 3 WHERE slug IN ('troupe', 'hp', 'patrouille');
UPDATE unite_type SET branche = 4 WHERE slug IN ('compagnie', 'hpc', 'patguide');
UPDATE unite_type SET branche = 5 WHERE slug IN ('clan', 'eqclan');
UPDATE unite_type SET branche = 6 WHERE slug IN ('feu', 'eqfeu');

EOS
);
        }
    }

}
