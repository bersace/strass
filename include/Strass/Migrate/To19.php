<?php /*-*- sql -*-*/

class Strass_Migrate_To19 extends Strass_MigrateHandler {
    function online($db) {
        $db->exec(<<<'EOS'
--

DROP TABLE livredor;
DELETE FROM log WHERE logger = 'livredor';

EOS
);
    }
}
