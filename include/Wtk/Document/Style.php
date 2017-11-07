<?php

class Wtk_Document_Style_NotFound extends Exception {}

class Wtk_Document_Style {
  static public $path;
  static public $basestyle;
    public $id;
    public $metas;
    protected $basedir;

    static function factory($id)
    {
      foreach (self::$path as $path) {
        if (file_exists($path . $id)) {
          return new self($id, $path);
        }
      }
      throw new Wtk_Document_Style_NotFound("Style ".$id." introuvable");
    }

    static function listAvailables()
    {
      $styles = array();
      foreach(self::$path as $basedir) {
        if (!file_exists($basedir))
          continue;

        foreach(glob($basedir . '*/metas.php') as $meta) {
          $name = basename(dirname($meta));
          array_push($styles, new self($name, $basedir));
        }
      }
      return $styles;
    }

    function __construct($id = 'default', $basedir = 'static/styles/') {
        $this->id = $id;
        $this->basedir = $basedir . $id . DIRECTORY_SEPARATOR;
        $this->metas = include $this->basedir . 'metas.php';

        if ($this->basedir[0] == DIRECTORY_SEPARATOR) {
            /* Pour les chemins absolu, on retire le STRASS_ROOT. L'URL est
             * soit static/styles, soit data/styles. */
          $parent = dirname(dirname($basedir));
          $this->baseurl = substr($this->basedir, strlen($parent)+1);
        }
        else
          $this->baseurl = $this->basedir;
    }

    function __get($name)
    {
        return $this->metas->$name;
    }

    function __toString()
    {
        return $this->id;
    }

    function getFavicon()
    {
        return str_replace('//', '/', $this->baseurl.'/favicon.png');
    }

    static function readManifest($basedir)
    {
        $manifest = array();
        $path = $basedir . '/html/manifest.json';
        if ($json = @file_get_contents($path)) {
            // Charger un manifeste généré par webassets.
            $json = json_decode($json, false, 4);
            foreach ($json as $path => $version) {
                $path = basename($path);
                $component = str_replace('.%(version)s.css', '', $path);
                $manifest[$component] = str_replace('%(version)s', $version, $path);
            }
        }
        else {
            // Générer un manifeste naïf.
            $files = glob($basedir . '/html/*.css');
            foreach ($files as $path) {
                $path = basename($path);
                $component = str_replace('.css', '', $path);
                $manifest[$component] = $path;
            }
        }

        return $manifest;
    }

    function findCss($components, $basedir, $baseurl) {
        $files = array();
        $manifest = self::readManifest($basedir);

        foreach($components as $comp) {
            if (!array_key_exists($comp, $manifest))
                continue;

            $path = 'html/' . $manifest[$comp];
            $files[] = array(
                'file' => $basedir . $path,
                'url' => $baseurl . $path,
            );
        }

        return $files;
    }

    /*
     * liste les fichiers externe à embarqué.
     */
    function getFiles(array $components, $format = 'Xhtml') {
        $files = array();

        switch ($format) {
        case 'Xhtml':
        case 'Html5':
            $files = array_merge(
                $this->findCss($components, self::$basestyle, 'static/styles/' . basename(self::$basestyle) . '/'),
                $this->findCss($components, $this->basedir, $this->baseurl)
            );
          break;
        }

        return $files;
    }
}

class Wtk_Document_Style_Empty extends Wtk_Document_Style {
    function __construct() {
        $this->id = '';
        $this->title = '';
    }

    function getFavicon()
    {
        return self::$basestyle.'/favicon.png';
    }

    function getFiles(array $components, $format = 'Xhtml') {
        $files = array();

        switch ($format) {
        case 'Xhtml':
        case 'Html5':
            $files = $this->findCss(
                $components,
                self::$basestyle,
                'static/styles/' . basename(self::$basestyle) . '/'
            );
          break;
        }

        return $files;
    }
}
