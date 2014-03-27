<?php

class Strass_Migrate_To2 extends Strass_MigrateHandler {
  function offline() {
    if (file_exists('cache'))
      rename('cache', 'private/cache');

    // config
    Zend_Registry::set('config_basedir', 'config/');
    $config = array();;
    $tmp = new Strass_Config_Php('knema/db');
    $newpath = 'private/strass.sqlite';
    rename($tmp->config->dbname, $newpath);
    $tmp->config->dbname = $newpath;
    $config['db'] = $tmp->toArray();
    $tmp = new Strass_Config_Php('knema/site');
    $config['site'] = $tmp->toArray();
    $tmp = new Strass_Config_Php('strass/inscription');
    $config['inscription'] = $tmp->toArray();
    $tmp = new Strass_Config_Php('knema/menu');
    $config['menu'] = $tmp->toArray();

    Zend_Registry::set('config_basedir', 'private/config/');
    $config = new Strass_Config_Php('strass', $config);
    $config->write();

    // Renommages
    rename("resources/styles/".$config->site->style, "data/styles/".$config->site->style);
    shell_exec("rsync -av data/statiques/ private/statiques/");
    self::rrmdir('data/statiques');

    rename("private/statiques/strass/unites", "private/unites");
    rename("data/images/strass/unites/", "data/unites");
    rename("data/images/strass/photos/", "data/photos");
    rename("data/images/strass/individus/", "data/avatars/");
    rename("data/images/strass/journaux/", "data/journaux");
    rename("data/intro.wiki", "private/unites/intro.wiki");

    // Nettoyages
    @unlink('resources/templates/.htaccess');
    self::rrmdir('resources/');
    @unlink('config/.htaccess');
    self::rrmdir('config/');
    @unlink('data/db/.htaccess');
    self::rrmdir('data/db/');
    self::rrmdir('data/images/');
    self::rrmdir('private/statiques/strass');
    @self::rrmdir('private/statiques/scout');

    Strass::setInstalled();
  }
}
