<?php
return array (
  'db' => 
  array (
    'adapter' => 'Pdo_SQLite',
    'config' => 
    array (
      'dbname' => 'data/db/morel.sqlite',
    ),
  ),
  'site' => 
  array (
    'metas' => 
    array (
      'title' => 'Clan Lt Tom Morel',
      'author' => 'Clan Lt Tom Morel',
      'organization' => 'AGSE – District Sainte Geneviève Paris',
      'creation' => '2008',
      'subject' => 'scout,europe,agse,fse,clan,clans,paris,lieutenant,lt,theodose,tom,morel,route,routier,equipe,district,sainte,geneviève',
      'language' => 'fr',
    ),
    'short_title' => 'Clan Lt Tom Morel',
    'id' => 'morel',
    'realm' => 'morel',
    'duree_connexion' => 2678400,
    'admin' => 'clandeparis.morel@free.fr',
    'mail' => 
    array (
      'enable' => true,
      'smtp' => '',
    ),
    'style' => 'santiago',
    'sauvegarder' => true,
    'rubrique' => 'le-mot-du-chef',
  ),
  'inscription' => 
  array (
    'scoutisme' => false,
  ),
  'menu' => 
  array (
    'index' => 
    array (
      'metas' => 
      array (
        'label' => 'Accueil',
      ),
      'url' => 
      array (
        'controller' => '',
      ),
    ),
    'effectifs' => 
    array (
      'metas' => 
      array (
        'label' => 'Unités',
      ),
      'url' => 
      array (
        'controller' => 'unites',
      ),
    ),
    0 => 
    array (
      'metas' => 
      array (
        'label' => 'Photos',
      ),
      'url' => 
      array (
        'controller' => 'photos',
      ),
    ),
    1 => 
    array (
      'metas' => 
      array (
        'label' => 'Blog',
      ),
      'url' => 
      array (
        'controller' => 'journaux',
      ),
    ),
    2 => 
    array (
      'metas' => 
      array (
        'label' => 'Livre d\'or',
      ),
      'url' => 
      array (
        'controller' => 'livredor',
      ),
    ),
    3 => 
    array (
      'metas' => 
      array (
        'label' => 'Documents',
      ),
      'url' => 
      array (
        'controller' => 'documents',
      ),
    ),
    4 => 
    array (
      'metas' => 
      array (
        'label' => 'Liens',
      ),
      'url' => 
      array (
        'controller' => 'liens',
      ),
    ),
    5 => 
    array (
      'metas' => 
      array (
        'label' => 'Contacts',
      ),
      'url' => 
      array (
        'controller' => 'statiques',
        'page' => 'contacts',
      ),
    ),
  ),
);
