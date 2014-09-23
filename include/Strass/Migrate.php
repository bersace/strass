<?php

class Strass_Migrate
{
  function __construct($db)
  {
    $this->db = $db;
  }

  static function run() {
    Strass_Cache::setup();
    $db = Strass_Db::setup();
    Zend_Registry::set('acl', new Strass_Installer_FakeAcl);
    $migrator = new Strass_Migrate($db);
    $migrator->migrate();
  }

  function migrate()
  {
    $current = Strass_Version::dataCurrent();
    $strass = Strass_Version::DATA;

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
    if (class_exists($class)) {
      $handler = new $class;
      $handler->run($this->db);
    }
    else {
      error_log("Pas de migration vers la version $target.");
    }

    Strass_Version::save($target);

    /* chainage vers la version suivante */
    $this->migrate();
  }
}

class Strass_MigrateHandler
{
  function run($db)
  {
    if (!ini_get('html_errors')) {
      $this->offline();
    }

    $db->beginTransaction();
    try {
      $this->online($db);
      $db->commit();
    }
    catch (Exception $e) {
      $db->rollBack();
      error_log("Erreur. Restauration de la version précédentee.");
      throw $e;
    }

    $db->exec('VACUUM;');
  }

  /* À exécuter si on a un accès shell */
  function offline()
  {
  }

  /* exécutable en ligne, par l'assistant */
  function online($db)
  {
  }

  /* assistants */
  static function rrmdir($dir)
  {
    foreach(glob($dir . '/*') as $file) {
      if(is_dir($file))
	self::rrmdir($file);
      else
	unlink($file);
    }
    rmdir($dir);
  }
}
