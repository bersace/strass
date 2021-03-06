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
        case 'png':
            return 'image/png';
        case 'ttf':
            return 'application/x-font-ttf';
        default:
            return 'application/octet-stream';
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

        $utc = new DateTimeZone('UTC');
        $dans_un_an = new DateTime('next year', $utc);
        header('Expires: ' . $dans_un_an->format(DATE_RFC1123));

        readfile($path);
        exit(0);
    }

    static function serve()
    {
        $search_path = array('.', Strass::getPrefix());
        $request_path = parse_url($_SERVER['REQUEST_URI'],  PHP_URL_PATH);
        foreach ($search_path as $path) {
            self::try_file($path . $request_path);
        }

        header('HTTP/1.0 404 Not Found');
        echo 'Not Found';
        exit(0);
    }

    static function debug()
    {
        header('HTTP/1.0 200 OK');
        header('Content-Type: text/plain');
        echo "**DEBUG**\n\n";
        $argv = func_get_args();
        foreach ($argv as $arg) {
            var_export($arg);
            echo "\n";
        }
        exit(0);
    }
}
