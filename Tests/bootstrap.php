<?php

error_reporting(E_ALL | E_STRICT);


function loader($sClass)
{
    $sFile = '../' . $sClass . '.php';

    if (file_exists($sFile)) {
        require $sFile;
    } else {
        throw new Exception('Unable to include file: ' . $sFile);
    }

}


spl_autoload_register('loader');

?>