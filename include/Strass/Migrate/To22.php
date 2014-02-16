<?php

class Strass_Migrate_To22 extends Strass_MigrateHandler {
  function online($db)
  {
    $old = new Strass_Config_Php('strass');

    $new = new Strass_Config_Php('strass', array());
    $new->metas = $old->site->metas;

    $system = $old->site->toArray();
    $system['mouvement'] = $system['association'];

    unset($system['association']);
    unset($system['metas']);
    unset($system['rubrique']);

    $new->system = $system;
    $new->write();

    Zend_Registry::set('config', $new);
  }
}
