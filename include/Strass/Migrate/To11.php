<?php /*-*- sql -*-*/

class Strass_Migrate_To11 extends Strass_MigrateHandler {
  function online($db) {
    $db->exec(<<<'EOS'
--

CREATE TABLE `document` (
	id		INTEGER		PRIMARY KEY,
	slug		CHAR(128)	NOT NULL UNIQUE,
	titre		CHAR(128)	NOT NULL,
	suffixe		CHAR(8),
	date		DATETIME
);

INSERT INTO document
(slug, titre, suffixe, date)
SELECT id, titre, suffixe, date
FROM documents
ORDER BY ROWID;

DROP TABLE documents;

CREATE TABLE `unite_document` (
	id		INTEGER		PRIMARY KEY,
	unite		INTEGER		NOT NULL REFERENCES unite(id),
	document	INTEGER		NOT NULL REFERENCES document(id),
	UNIQUE (unite, document)
);

INSERT INTO `unite_document`
(document, unite)
SELECT document.id, unite.id
FROM doc_unite
JOIN document ON document.slug = doc_unite.document
JOIN unite ON unite.slug = doc_unite.unite;

DROP TABLE doc_unite;

CREATE VIEW vdocuments AS
SELECT document.id, unite.slug, document.slug, document.suffixe
FROM document
JOIN unite_document ON unite_document.document = document.id
JOIN unite ON unite.id = unite_document.unite
ORDER BY document.date;

ALTER TABLE activite_document RENAME TO tmp;

CREATE TABLE `activite_document` (
	id		INTEGER		PRIMARY KEY,
	activite	INTEGER		NOT NULL REFERENCES activite(id),
	document	INTEGER		NOT NULL REFERENCES document(id),
	UNIQUE (activite, document)
);

INSERT INTO activite_document
(activite, document)
SELECT tmp.activite, document.id
FROM tmp
JOIN document ON document.slug = document;

DROP TABLE tmp;

CREATE VIEW vpiecesjointes AS
SELECT document.id, activite.slug, document.slug, document.suffixe
FROM document
JOIN activite_document ON activite_document.document = document.id
JOIN activite ON activite.id = activite_document.activite
ORDER BY activite.debut;

EOS
);

    error_log("Génération des vignettes des documents");
    $stmt = $db->query("SELECT * FROM document");
    foreach($stmt as $row) {
      if ($row['suffixe'] != 'pdf') continue;

      $document = 'data/documents/' . $row['slug']. '.' . $row['suffixe'];
      $vignette = 'data/documents/' . $row['slug']. '-vignette.jpeg';

      $im = new Imagick($document . '[0]');
      $im->setImageAlphaChannel(Imagick::ALPHACHANNEL_RESET);
      $im->setImageFormat('jpg');
      $im->setBackgroundColor('white');
      $im->thumbnailImage(0, 256);
      $im->writeImage($vignette);
    }
  }
}
