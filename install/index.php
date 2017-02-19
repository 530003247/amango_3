<?php
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');
define ( 'APP_DEBUG', true );
define ( 'ROOT_PATH', dirname(__FILE__));
define ( 'APP_PATH', './Application/' );
//开启session
if(false===session_start()){
    session_start();
}
if(!is_file( '../Application/Common/Conf/config.php')){
    define ( 'RUNTIME_PATH', './Runtime/' );
	require '../ThinkPHP/ThinkPHP.php';
} else {
	header('Location: ../index.php');
	exit;
}