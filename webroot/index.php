<?php

    

    require $_SERVER['APP_HOME'] . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'autoload.php';

	use src\App;

	$app = new App();
	$app->run();

    // var_dump(getallheaders());
    // var_dump($_SERVER);
