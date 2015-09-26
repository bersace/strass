<?php /*-*- sql -*-*/

class Strass_Migrate_To18 extends Strass_MigrateHandler {
    function online($db) {
        $db->exec(<<<'EOS'
--

CREATE VIRTUAL TABLE individu_fts USING fts4 ();

INSERT INTO individu_fts (docid, content)
SELECT DISTINCT
	individu.id,
       	individu.titre || ' ' || prenom || ' ' || individu.nom
	|| ' ' || individu.adelec
	|| ' ' ||group_concat(u.nom, ' ')
	|| ' ' ||group_concat(u.extra, ' ')
	|| ' ' ||group_concat(t.nom, ' ')
FROM individu
LEFT OUTER JOIN appartenance AS a ON a.individu = individu.id
LEFT OUTER JOIN unite AS u ON u.id = a.unite
LEFT OUTER JOIN unite_type AS t ON t.id = u.`type`
GROUP BY individu.id
ORDER BY individu.id;

CREATE TRIGGER individu_before_update_fts BEFORE UPDATE ON individu BEGIN
  DELETE FROM individu_fts WHERE docid=old.id;
END;

CREATE TRIGGER individu_before_delete_fts BEFORE DELETE ON individu BEGIN
  DELETE FROM individu_fts WHERE docid=old.id;
END;

CREATE TRIGGER individu_after_update_fts AFTER UPDATE ON individu BEGIN
  INSERT INTO individu_fts (docid, content)
  SELECT DISTINCT
	  new.id,
	  new.titre || ' ' || new.prenom || ' ' || new.nom || ' ' || new.adelec
	  || ' ' ||group_concat(u.nom, ' ')
	  || ' ' ||group_concat(u.extra, ' ')
	  || ' ' ||group_concat(t.nom, ' ')
  FROM individu
  LEFT OUTER JOIN appartenance AS a ON a.individu = new.id
  LEFT OUTER JOIN unite AS u ON u.id = a.unite
  LEFT OUTER JOIN unite_type AS t ON t.id = u.`type`;
END;

CREATE TRIGGER individu_after_insert_fts AFTER INSERT ON individu BEGIN
  INSERT INTO individu_fts (docid, content)
  SELECT DISTINCT
	  new.id,
	  new.titre || ' ' || new.prenom || ' ' || new.nom || ' ' || new.adelec
	  || ' ' ||group_concat(u.nom, ' ')
	  || ' ' ||group_concat(u.extra, ' ')
	  || ' ' ||group_concat(t.nom, ' ')
  FROM individu
  LEFT OUTER JOIN appartenance AS a ON a.individu = new.id
  LEFT OUTER JOIN unite AS u ON u.id = a.unite
  LEFT OUTER JOIN unite_type AS t ON t.id = u.`type`;
END;

CREATE TRIGGER appartenance_before_delete_fts BEFORE DELETE ON appartenance BEGIN
  DELETE FROM individu_fts WHERE docid=old.individu;
END;

-- Pas de trigger sur update, car on ne peut pas éditer l'unité.

CREATE TRIGGER appartenance_after_insert_fts AFTER INSERT ON appartenance BEGIN
  DELETE FROM individu_fts WHERE docid=new.individu;
  INSERT INTO individu_fts (docid, content)
  SELECT DISTINCT
	  individu.id,
	  individu.titre || ' ' || prenom || ' ' || individu.nom
	  || ' ' || individu.adelec
	  || ' ' ||group_concat(u.nom, ' ')
	  || ' ' ||group_concat(u.extra, ' ')
	  || ' ' ||group_concat(t.nom, ' ')
  FROM individu
  LEFT OUTER JOIN unite AS u ON u.id = new.unite
  LEFT OUTER JOIN unite_type AS t ON t.id = u.`type`
  WHERE individu.id = new.individu;
END;

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
  SELECT DISTINCT
	  individu.id,
	  individu.titre || ' ' || individu.prenom || ' ' || individu.nom
	  || ' ' || individu.adelec
	  || ' ' ||group_concat(u.nom, ' ')
	  || ' ' ||group_concat(u.extra, ' ')
	  || ' ' ||group_concat(t.nom, ' ')
  FROM individu
  -- Mettre à jour uniquement les individus de cette unité
  JOIN appartenance AS ca ON ca.individu = individu.id AND ca.unite = new.id
  -- Joindre les autres unités pour l'indexation
  LEFT OUTER JOIN appartenance AS a ON a.individu = individu.id
  LEFT OUTER JOIN unite AS u ON u.id = a.unite
  LEFT OUTER JOIN unite_type AS t ON t.id = u.`type`
  GROUP BY individu.id;

END;

EOS
);
    }
}
