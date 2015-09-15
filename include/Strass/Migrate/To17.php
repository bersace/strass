<?php /*-*- sql -*-*/

class Strass_Migrate_To17 extends Strass_MigrateHandler {
  function online($db) {
    $db->exec(<<<'EOS'
--

ALTER TABLE user RENAME TO tmp;
CREATE TABLE `user` (
	id			INTEGER 	PRIMARY KEY,
	individu		INTEGER 	UNIQUE REFERENCES individu(id),
	-- Valeur utilisée pour générer le digest. À partir de maintenant,
	-- c'est l'adelec.
	username		CHAR(64)	UNIQUE NOT NULL,
	-- On stocke un digest.
	password		CHAR(32)	NOT NULL,
	admin			BOOLEAN		DEFAULT 0,
	recover_token		CHAR(36),
	recover_deadline	DATETIME,
	last_login		DATETIME,
	send_mail		BOOLEAN DEFAULT TRUE
);


INSERT INTO user
(individu, username, password, admin, last_login)
SELECT
	tmp.individu, tmp.username, tmp.password, tmp.admin, tmp.last_login
FROM tmp
ORDER BY tmp.id;

DROP TABLE tmp;

EOS
);
  }
}
