<?php /*-*- sql -*-*/

class Strass_Migrate_To15 extends Strass_MigrateHandler {
  function offline() {
    error_log("Renommage des dossiers d'article");

    foreach (glob('data/journaux/*/*/*') as $src) {
      $journal = dirname(dirname($src));
      $dest = $journal . '/' . basename($src);
      rename($src,  $dest);
    }
  }

  function online($db) {
    $db->exec(<<<'EOS'
--
CREATE TABLE `journal` (
       id		INTEGER		PRIMARY KEY,
       slug		CHAR(128)	UNIQUE,
       -- Un seul blog par unité autorisé
       unite		INTEGER		UNIQUE REFERENCES unite(id),
       nom		CHAR(128)	UNIQUE
);

INSERT INTO journal
(slug, unite, nom)
SELECT journaux.id, unite.id, journaux.nom
FROM journaux
JOIN unite ON unite.slug = journaux.unite;

DROP TABLE journaux;

CREATE VIEW vjournaux AS
SELECT journal.id, journal.slug, unite.slug, journal.nom
FROM journal
JOIN unite ON unite.id = journal.unite;


CREATE TABLE `article` (
       id		INTEGER		PRIMARY KEY,
       slug		CHAR(256)	UNIQUE,
       journal	 	INTEGER 	REFERENCES journal(id),
       titre    	CHAR(256),
       boulet   	TEXT,
       article  	TEXT,
       public   	INT(1)     	DEFAULT 0,
       commentaires	INTEGER		UNIQUE NOT NULL REFERENCES commentaire(id)
);

INSERT INTO commentaire
(auteur, message, date)
SELECT auteur.id, articles.id, articles.date || ' ' || substr(articles.heure, 0, 6)
FROM articles
JOIN individu AS auteur ON auteur.slug = articles.auteur;

INSERT INTO `article`
(slug, journal, titre, boulet, article, public, commentaires)
SELECT articles.id, journal.id,
       articles.titre, articles.boulet, articles.article, articles.public,
       (SELECT id FROM commentaire WHERE message = articles.id)
FROM articles
JOIN journal ON journal.slug = articles.journal;

CREATE VIEW varticles AS
SELECT article.id,
       journal.slug AS journal,
       article.slug, auteur.slug AS auteur,
       article.titre, commentaire.date
FROM article
JOIN journal ON journal.id = article.journal
JOIN commentaire ON commentaire.id = article.commentaires
JOIN individu AS auteur ON auteur.id = commentaire.auteur
ORDER BY journal.id, commentaire.date;


CREATE TABLE `article_etiquette` (
       id		INTEGER		PRIMARY KEY,
       article		INTEGER		NOT NULL REFERENCES article(id),
       etiquette	CHAR(128)	NOT NULL,
       UNIQUE(article, etiquette)
);

INSERT INTO article_etiquette
(article, etiquette)
SELECT DISTINCT
       article.id, rubriques.nom
FROM rubriques
JOIN articles ON articles.rubrique = rubriques.id AND articles.journal = rubriques.journal
JOIN article ON article.slug = articles.id;

DROP TABLE articles;
DROP TABLE rubriques;

EOS
);
  }
}
