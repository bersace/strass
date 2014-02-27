<?php /*-*- sql -*-*/

class Strass_Migrate_To9 extends Strass_MigrateHandler {
  function online($db) {
    error_log("Unicité des identifiants de photos");
    do {
      $slugs = $db->query("SELECT count(*) AS count, id AS slug FROM photos GROUP BY id HAVING count > 1");
      $count = 0;
      foreach ($slugs as $i => $row) {
	$count += 1;
	extract($row);
	$activites = $db->query("SELECT activite FROM photos WHERE id = '${slug}' ORDER BY ROWID");
	foreach($activites as $i => $arow) {
	  if (!$i) continue;
	  extract($arow);
	  $newslug = "${slug}-${i}";
	  error_log("Renommage de ${activite}/${slug} en ${newslug}.");
	  rename("data/photos/${activite}/${slug}.jpeg",
		 "data/photos/${activite}/${newslug}.jpeg");
	  rename("data/photos/${activite}/${slug}-vignette.jpeg",
		 "data/photos/${activite}/${newslug}-vignette.jpeg");
	  $db->exec(<<<EOS
UPDATE photos SET id = '${newslug}'
       WHERE id = '${slug}' AND activite = '${activite}';
UPDATE commentaires SET photo = '${newslug}'
       WHERE photo = '${slug}' AND activite = '${activite}';
EOS
);
	}
      }
    } while($count);

    error_log("Migration des photos");
    $db->exec(<<<'EOS'
--

CREATE TABLE `commentaire` (
       id		INTEGER		PRIMARY KEY,
       auteur		INTEGER		REFERENCES individu(id),
       parent		INTEGER		REFERENCES commentaire(id),
       `date`		DATETIME        DEFAULT CURRENT_TIMESTAMP,
       message	        TEXT,
       -- Interdire les réponses multiples. Le site ne sert pas à discuter.
       UNIQUE(auteur, parent)
);

INSERT INTO commentaire
(`date`, message)
SELECT photos.`date`, photos.id
FROM photos
ORDER BY `date`;

CREATE TABLE `photo` (
       id		INTEGER		PRIMARY KEY,
       slug     	CHAR(512)	UNIQUE,
       activite		INTEGER		NOT NULL REFERENCES activite(id),
       promotion	INTEGER		DEFAULT 0,
       `date`		DATETIME,
       titre 		CHAR(512),
       commentaires     INTEGER         NOT NULL REFERENCES commentaire(id)
);


INSERT INTO photo
(slug, titre, activite, date, commentaires)
SELECT photos.id, titre, activite.id, photos.date, (
SELECT id FROM commentaire WHERE commentaire.message = photos.id)
FROM photos
JOIN activite ON activite.slug = photos.activite
ORDER BY activite.debut, photos.date;

UPDATE commentaire
SET message = (SELECT desc FROM photos WHERE photos.id = message);

DROP TABLE photos;

INSERT INTO commentaire
(auteur, parent, date, message)
SELECT individu.id, photo.commentaires, commentaires.date, commentaires.commentaire
FROM commentaires
JOIN individu ON individu.slug = commentaires.individu
JOIN activite ON activite.slug = commentaires.activite
JOIN photo ON photo.slug = commentaires.photo AND photo.activite = activite.id
ORDER BY commentaires.date;

DROP TABLE commentaires;


CREATE VIEW vphotos AS
SELECT
	photo.id, photo.slug,
	activite.slug AS activite,
	photo.titre
FROM photo
JOIN activite ON activite.id = photo.activite
ORDER BY activite.debut, photo.date;

CREATE VIEW vcommentaires AS
SELECT
	commentaire.id,
	activite.slug AS activite, photo.slug AS photo, individu.slug,
	commentaire.date, message
FROM commentaire
JOIN photo ON photo.commentaires = commentaire.parent
JOIN activite ON activite.id = photo.activite
LEFT JOIN individu ON individu.id = commentaire.auteur
ORDER BY photo.id, commentaire.date;

EOS
);

    error_log("Écriture de la configuration des photos.");
    $config = new Strass_Config_Php('strass');
    $config->photos = array('size' => 2048,
			    'quality' => 85,
			    'vignette' => 256);
    $config->write();


    error_log('Regénération des vignettes');
    $total = $db->query("SELECT count(*) FROM vphotos;")->fetchColumn();
    $stmt = $db->query("SELECT id, slug, activite FROM vphotos;");
    $top = microtime(true);
    foreach ($stmt as $i => $row) {
      $now = microtime(true);
      if (intval($now - $top) > 3) {
	$count = $i + 1;
	error_log("${count} vignettes sur ${total} regénérées.");
	$top = microtime(true);
      }
      extract($row);
      $base = 'data/photos/' . $activite . '/' . $slug;
      $photo = $base . '.jpeg';
      $vignette = $base . '-vignette.jpeg';

      if (!file_exists($photo)) {
	error_log("Photo perdue : ${activite}/${slug}");
	$db->exec("DELETE FROM photo WHERE id = ".$id. ";");
	continue;
      }

      $im = new Imagick($photo);
      $width = $im->getImageWidth();
      $height = $im->getImageHeight();
      $im->setImageCompressionQuality(85);

      $MAX = 256;
      if (min($width, $height) > $MAX)
	$im->cropThumbnailImage($MAX, $MAX);
      $im->writeImage($vignette);
    }

    $db->exec(<<<'EOS'
-- Supprimer les descriptions de photos supprimées
DELETE FROM commentaire
WHERE commentaire.parent IS NULL AND NOT EXISTS (
       SELECT * FROM photo WHERE commentaires = commentaire.id);

-- Supprimer les commentaires orphelins
DELETE FROM commentaire
WHERE commentaire.parent IS NOT NULL AND NOT EXISTS (
       SELECT * FROM commentaire AS parent WHERE parent.id = commentaire.id);

EOS
);
  }
}
