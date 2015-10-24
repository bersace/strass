<?php /*-*- sql -*-*/
/*
 * Ajout de l'identification des unités par photo.
 */

class Strass_Migrate_To20 extends Strass_MigrateHandler {
    function online($db) {
        $db->exec(<<<'EOS'
--

CREATE TABLE photo_identification (
	id		INTEGER		PRIMARY KEY,
	photo	INTEGER		NOT NULL REFERENCES photo(id),
	unite	INTEGER		NOT NULL REFERENCES unite(id),
    UNIQUE(photo, unite)
);

-- Reprise d'historique: on identifie l'unité organisatrice la plus ancienne de
-- chaque activité dans chaque photo.
INSERT INTO photo_identification
(photo, unite)
SELECT DISTINCT
    photo.id, MIN(participation.unite)
FROM photo
JOIN activite ON activite.id = photo.activite
JOIN participation ON participation.activite = activite.id
GROUP BY photo.id
ORDER BY photo.id;

EOS
);
    }
}
