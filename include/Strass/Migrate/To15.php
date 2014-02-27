<?php

class Strass_Migrate_To15 extends Strass_MigrateHandler {
  function online($db)
  {
    $old = new Strass_Config_Php('strass');

    $new = new Strass_Config_Php('strass', array());
    $new->metas = $old->site->metas;

    $system = $old->site->toArray();
    if ($system['id'] == 'morel')
      $mvt = 'fse';
    else
      $mvt = 'suf';
    $system['mouvement'] = $mvt;

    unset($system['id']);
    unset($system['association']);
    unset($system['metas']);
    unset($system['rubrique']);
    unset($system['sauvegarder']);

    $new->system = $system;
    $new->write();

    Zend_Registry::set('config', $new);
  }
}
