<?php

spl_autoload_register(function ($class) {
    $filename = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
	include($filename);
});

?>