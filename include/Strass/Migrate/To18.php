<?php /*-*- sql -*-*/

class Strass_Migrate_To18 extends Strass_MigrateHandler {
    function online($db) {
        $db->exec(<<<'EOS'
--

-- Vue pour réutiliser la concaténation des champs dans les différents
-- triggers.

CREATE VIEW individu_content AS
SELECT DISTINCT
        individu.id AS id,
        coalesce(individu.titre, '') || ' ' || prenom || ' ' || individu.nom
        || ' ' || individu.adelec
        || ' ' || group_concat(coalesce(e.titre, ''), ' ')
        || ' ' || group_concat(coalesce(u.nom, ''), ' ')
        || ' ' || group_concat(coalesce(u.extra, ''), ' ')
        || ' ' || group_concat(coalesce(t.nom, ''), ' ') AS content
FROM individu
LEFT OUTER JOIN etape AS e ON e.id = individu.etape
LEFT OUTER JOIN appartenance AS a ON a.individu = individu.id
LEFT OUTER JOIN unite AS u ON u.id = a.unite
LEFT OUTER JOIN unite_type AS t ON t.id = u.`type`
GROUP BY individu.id
ORDER BY individu.id;

CREATE VIRTUAL TABLE individu_fts USING fts4 (tokenize=unicode61);

INSERT INTO individu_fts (docid, content)
SELECT * FROM individu_content;

-- Triggers pour maintenir l'index à jour de la table individu

CREATE TRIGGER individu_before_update_fts BEFORE UPDATE ON individu BEGIN
  DELETE FROM individu_fts WHERE docid=old.id;
END;

CREATE TRIGGER individu_before_delete_fts BEFORE DELETE ON individu BEGIN
  DELETE FROM individu_fts WHERE docid=old.id;
END;

CREATE TRIGGER individu_after_update_fts AFTER UPDATE ON individu BEGIN
  INSERT INTO individu_fts (docid, content)
  SELECT * FROM individu_content AS individu
  WHERE individu.id = NEW.id;
END;

CREATE TRIGGER individu_after_insert_fts AFTER INSERT ON individu BEGIN
  INSERT INTO individu_fts (docid, content)
  SELECT * FROM individu_content AS individu
  WHERE individu.id = NEW.id;
END;

-- Triggers pour maintenir l'index à jour de la table appartenance

CREATE TRIGGER appartenance_before_delete_fts BEFORE DELETE ON appartenance BEGIN
  DELETE FROM individu_fts WHERE docid=old.individu;
END;

-- Pas de trigger sur update, car on ne peut pas éditer l'unité.

CREATE TRIGGER appartenance_after_insert_fts AFTER INSERT ON appartenance BEGIN
  DELETE FROM individu_fts WHERE docid=new.individu;
  INSERT INTO individu_fts (docid, content)
  SELECT * FROM individu_content AS individu
  WHERE individu.id = NEW.individu;
END;

-- Triggers pour maintenir l'index à jour de la table appartenance

-- Pas besoin de trigger sur la création ni la suppression d'unité, car les
-- appartenances le font déjà.

CREATE TRIGGER unite_before_update_fts BEFORE UPDATE ON unite BEGIN
  DELETE FROM individu_fts WHERE docid IN (
    SELECT individu.id FROM individu
    JOIN appartenance AS a ON a.individu = individu.id
    WHERE a.unite = old.id);
END;

CREATE TRIGGER unite_after_update_fts AFTER UPDATE ON unite BEGIN
  INSERT INTO individu_fts (docid, content)
  SELECT * FROM individu_content AS individu
  -- Mettre à jour uniquement les individus de cette unité
  JOIN appartenance AS ca ON ca.individu = individu.id AND ca.unite = NEW.id;

END;

EOS
);
    }
}
