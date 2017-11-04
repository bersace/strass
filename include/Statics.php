<?php

class Statics {
    static function contentType($extension)
    {
        switch($extension) {
        case 'css':
            return 'text/css';
        case 'htm':
        case 'html':
            return 'text/html';
        case 'ico':
            return 'image/ico';
        case 'jpeg';
        case 'jpg':
            return 'image/jpeg';
        case 'js':
            return 'application/javascript';
        case 'mp3':
            return 'audio/mp3';
        case 'ogg':
            return 'audio/ogg';
        case 'pdf':
            return 'application/pdf';
        case 'png':
            return 'image/png';
        case 'ttf':
            return 'application/x-font-ttf';
        case 'wav':
            return 'audio/wav';
        case 'wma':
            return 'audio/x-ms-wma';
        default:
            return 'text/plain';
        }
    }

    function try_file($path)
    {
        if (!file_exists($path))
            return;

        header('X-Strass: statics');
        $info = pathinfo($path);
        $ext = @$info['extension'] ?: null;
        header('Content-Type: ' . self::contentType($ext));
        readfile($path);
        exit(0);
    }

    static function serve()
    {
        $search_path = array('.', Strass::getPrefix());
        foreach ($search_path as $path) {
            $static_path = $path . $_SERVER['REQUEST_URI'];
            self::try_file($static_path);
        }
        header('HTTP/1.0 404 Not Found');
        echo 'Not Found';
        exit(0);
    }
}
