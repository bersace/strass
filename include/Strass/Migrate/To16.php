<?php /*-*- sql -*-*/

class Strass_Migrate_To16 extends Strass_MigrateHandler {
  function online($db) {
    $db->exec(<<<'EOS'
--

ALTER TABLE log RENAME TO tmp;
CREATE TABLE `log` (
	id	INTEGER		PRIMARY KEY,
	user	INTEGER		INTEGER REFERENCES user(id),
	logger	CHAR(255)	NOT NULL DEFAULT 'strass',
	level	CHAR(8)		NOT NULL DEFAULT 'info',
	date	DATETIME	NOT NULL DEFAULT (datetime('now', 'localtime')),
	message	CHAR(255)	NOT NULL,
	url	CHAR(255)	DEFAULT NULL,
	detail	TEXT		DEFAULT NULL
);

INSERT INTO log
(user, logger, level, date, message, url, detail)
SELECT
	user, logger, level, datetime(date, 'localtime'), message, url, detail
FROM tmp
ORDER BY date;

DROP TABLE tmp;

EOS
);
  }
}
