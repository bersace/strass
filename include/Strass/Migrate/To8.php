<?php

require_once 'Strass/Statique.php';

class Strass_Migrate_To8 extends Strass_MigrateHandler {
  function offline() {
    $contacts = new Statique('contacts');
    $legal = new Statique('legal');
    $legal->write($legal->read() . "\n\n" . $contacts->read() . "\n");
    $contacts->delete();
  }
}
