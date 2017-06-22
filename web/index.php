<?php
namespace HeliosAPI;

/*Helios API v0.0.1*/
require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\FileBag;

$app = new \Silex\Application();

//Detect environment
if (isset($app_env) && in_array($app_env, array('prod','dev','test')))
    $app['env'] = $app_env;
else
    $app['env'] = 'prod';

//Debug
$app['debug'] = false;

//Set logging
$logPath = __DIR__.'/log/';
ini_set("log_errors", 1);
ini_set("error_log", $logPath."php-error.log");
$app->register(new \Silex\Provider\MonologServiceProvider(), array('monolog.logfile' => $logPath.'development.log'));

//Database
$dbConfig = array(
                    'db.options' => array(
                                            'driver'   => 'pdo_sqlsrv',
                                            'host' => 'brana.neudrinks.cz',
                                            'port' => 1433,
                                            'dbname' => 'Helios001',
                                            'user' => 'petr.mensik',
                                            'password' => 'P*csX18!ax'
                                        )
);
$app->register(new \Silex\Provider\DoctrineServiceProvider(), $dbConfig);

//JWT


//Controllers
$app->mount('/heliosapi', new \HeliosAPI\HeliosAPIControllerProvider());

if ('test' == $app['env'])
    return $app;
else
    $app->run();
?>