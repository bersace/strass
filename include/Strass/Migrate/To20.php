<?php /*-*- sql -*-*/

class Strass_Migrate_To20 extends Strass_MigrateHandler {
  function online($db) {
    $db->exec(<<<'EOS'
--

ALTER TABLE log RENAME TO tmp;
CREATE TABLE `log` (
       id	INTEGER		PRIMARY KEY,
       user	INTEGER 	INTEGER REFERENCES user(id),
       logger	CHAR(255)	NOT NULL DEFAULT 'strass',
       level	CHAR(8)		NOT NULL DEFAULT 'info',
       date	TIMESTAMP	NOT NULL DEFAULT CURRENT_TIMESTAMP,
       message	CHAR(255)	NOT NULL,
       url	CHAR(255)	DEFAULT NULL,
       detail	TEXT		DEFAULT NULL
);

INSERT INTO log
(user, logger, date, message, url, detail)
SELECT
	user.id, 'strass', strftime('%s', date), detail, url, NULL
FROM tmp
LEFT JOIN user ON user.username = tmp.username
ORDER BY date;

DROP TABLE log_attr;
DROP TABLE tmp;

EOS
);
  }
}
