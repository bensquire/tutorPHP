<?php

error_reporting(E_ALL | E_STRICT);

function loader($sClass)
{
    $sFile = __DIR__ . '/../' . $sClass . '.php';

    if (!file_exists($sFile)) {
        throw new Exception('Unable to include file: ' . $sFile);
    }

    require $sFile;
}

spl_autoload_register('loader');
