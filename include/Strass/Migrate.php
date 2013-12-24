<?php

class Strass_Migrate {
  function migrate() {
    $current = Strass_Version::current();
    $strass = Strass_Version::VERSION;

    if ($strass == $current) {
      error_log("Installation à jour de la version ".$current." !");
      return;
    }
    else if ($current >= $strass) {
      error_log("Installation en avance sur Strass !!");
      return;
    }

    $target = $current + 1;
    error_log("Migration vers la version ".$target.".");
    $class = 'Strass_Migrate_To'.$target;
    $handler = new $class;
    if (!ini_get('html_errors')) {
      $handler->offline();
    }
    $handler->online();
    Strass_Version::save($target);

    /* chainage vers la version suivante */
    $this->migrate();
  }
}

class Strass_MigrateHandler {
  /* À exécuter si on a un accès shell */
  function offline() {
  }

  /* exécutable en ligne, par l'assistant */
  function online() {
  }
}