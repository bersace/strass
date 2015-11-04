<?php /*-*- sql -*-*/
/*
 * Nouvelle fonctionnalité: envoie de PDF dans les blogs.
 */

class Strass_Migrate_To22 extends Strass_MigrateHandler {
    function online($db) {
        $db->exec(<<<'EOS'
--

CREATE TABLE article_document (
    id       INTEGER    PRIMARY KEY,
    article  INTEGER    NOT NULL REFERENCES article(id),
    document INTEGER    NOT NULL REFERENCES document(id),
    UNIQUE(article, document)
);

EOS
        );
    }

}
