<?php /*-*- sql -*-*/

class Strass_Migrate_To18 extends Strass_MigrateHandler {
  function online($db) {
    $db->exec(<<<'EOS'
--

CREATE TABLE `inscription` (
       id		INTEGER		PRIMARY KEY,
       password         CHAR(32)    	NOT NULL,
       -- état civil
       nom              CHAR(64),
       prenom           CHAR(64),
       sexe             CHAR(1)     	NOT NULL,
       naissance        DATE	    	NOT NULL,
       -- contact
       adresse          CHAR(128)   	NOT NULL UNIQUE,
       fixe             CHAR(14)    	NULL,
       portable         CHAR(14)    	NULL,
       adelec           CHAR(64)    	NOT NULL,
       progression      TEXT        	NULL,
       -- modération
       message          TEXT        	NOT NULL,
       scoutisme        TEXT        	NOT NULL

);

-- Valider les inscriptions avant de migrer ;-)

DROP TABLE inscriptions;

EOS
);
  }
}
