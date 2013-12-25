<?php

class Strass_Migrate_To4 extends Strass_MigrateHandler {
  function offline() {
    rename("private/statiques/strass/unites", "private/unites");
    rename("data/images/strass/unites", "data/unites");
    rename("data/images/strass/photos/", "data/photos");
    rename("data/images/strass/individus/", "data/avatars/");
    rename("data/images/strass/journaux/", "data/journaux");
    $this::rrmdir('data/images/');
    rename('private/statiques/strass/inscription/cotisation.wiki', 'private/cotisation.wiki');
    $this::rrmdir('private/statiques/strass');
  }
}
