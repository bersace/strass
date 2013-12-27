<?php

class Strass_Migrate_To8 extends Strass_MigrateHandler {
  function online($db) {
    $db->exec(<<<'EOS'
-- Longueur d'un UUID, même si on utiliser uniqid
ALTER TABLE user ADD COLUMN recover_token CHAR(36);
ALTER TABLE user ADD COLUMN recover_deadline INTEGER;
EOS
);
  }
}
