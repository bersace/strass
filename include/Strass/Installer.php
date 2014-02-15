<?php

require_once 'Strass/Individus.php';

class Strass_Installer
{
  const VERSION = 18;

  function __construct($data, $dbname = 'private/strass.sqlite')
  {
    $this->sql_dir = dirname(__FILE__) . '/Installer/sql/';
    $this->data = $data;
    $this->dbname = $dbname;
  }

  function generateRealm()
  {
    if ($_SERVER['SERVER_NAME'] != 'localhost') {
      return 'strass-'.$_SERVER['SERVER_NAME'];
    }
    else {
      return 'strass-'.mt_rand(100, 999);
    }
  }

  function writeConfig()
  {
    $config = array('site' => array ('metas' => array ('title' => null,
						       'author' => null,
						       'organization' => null,
						       'creation' => strftime('%Y'),
						       'subject' => 'scout,' . $this->data['site']['mouvement'],
						       ),
				     'association' => $this->data['site']['mouvement'],
				     'short_title' => null,
				     'id' => 'strass',
				     'realm' => $this->generateRealm(),
				     'realm_suffixe' => '',
				     'duree_connexion' => 2678400,
				     'admin' => $this->data['admin']['adelec'],
				     'mail' => array('enable' => true,
						     'smtp' => '',
						     ),
				     'style' => 'strass',
				     'rubrique' => null,
				     ),
		    'inscription' => array('scoutisme' => false, ),
		    );
    $config = new Strass_Config_Php('strass', $config);
    $config->write();
    Zend_Registry::set('config', $config);
  }

  function initDb()
  {
    /* optimisation car la création du schéma peut prendre pas mal de
       temps, et nous somme online */
    copy($this->sql_dir . '/strass.sqlite', $this->dbname);
    /* injection des données spécifique au mouvement */
    $sql = @file_get_contents($this->sql_dir . $this->data['site']['mouvement'] . '.sql');
    if ($sql === false)
      throw new Exception("Fichier ${name}.sql manquant");
    $db = Strass_Db::setup($this->dbname);
    $db->exec($sql);

    Strass_Version::save(self::VERSION);

    $migrator = new Strass_Migrate($db);
    $migrator->migrate();
  }

  function initAdmin()
  {
    $t = new Individus;
    $slug = wtk_strtoid($this->data['admin']['prenom'].' '.$this->data['admin']['nom']);
    $data = array('slug' => $slug,
		  'prenom' => $this->data['admin']['prenom'],
		  'nom' => $this->data['admin']['nom'],
		  'sexe' => $this->data['admin']['sexe'],
		  'naissance' => 0,
		  'adelec' => $this->data['admin']['adelec'],
		  );
    $t->insert($data);

    $i = $t->findBySlug($slug);

    $t = new Users;
    $data = array('individu' => $i->id,
		  'username' => $this->data['admin']['adelec'],
		  'password' => Users::hashPassword($this->data['admin']['adelec'],
						    $this->data['admin']['motdepasse']),
		  'admin' => TRUE,
		  );
    $k = $t->insert($data);
    Zend_Registry::set('user', $t->findOne($k));
  }

  function run()
  {
    $this->writeConfig();
    $this->initDb();
    $this->initAdmin();

    $logger = new Strass_Logger('installeur');
    $logger->info("Installation terminée");

    Strass_Version::setInstalled();
  }
}