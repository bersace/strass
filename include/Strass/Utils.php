<?php

function strass_append_mtime_querystring($path)
{
    if ($mtime = @filemtime($path)) {
        return $path . '?' . $mtime;
    }
}
