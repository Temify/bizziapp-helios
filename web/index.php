<?php
namespace HeliosAPI;

// Disable NOTICE from reporting
error_reporting( error_reporting() & ~E_NOTICE );

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
$app['debug'] = false; //TODO: for production set to false + set from environment variable
if(getenv('DEBUG'))
    $app['debug'] = true;

//Set logging
$logPath = __DIR__.'/log/';
ini_set("log_errors", 1);
ini_set("error_log", $logPath."php-error.log");
if($app['debug']) $app->register(new \Silex\Provider\MonologServiceProvider(), array('monolog.logfile' => $logPath.'development.log'));

//TODO: az budou na serveru nastaveny ENV VARS, tak odstranit natvrdo zadane parametry, nechat jen defaultni hodnoty pro driver a port
//Database
$dbConfig = array(
                    'db.options' => array(

                                            'driver' => 'pdo_sqlsrv',
                                            'host' => 'brana.neudrinks.cz',
                                            'port' => '1433',
                                            'dbname' => 'Helios003',
                                            'user' => 'petr.mensik',
                                            'password' => 'P*csX18!ax'

                                            // 'driver' => getenv('DB_DRIVER'),
                                            // 'host' => getenv('DB_HOST'),
                                            // 'port' => getenv('DB_PORT'),
                                            // 'dbname' => getenv('DB_NAME'),
                                            // 'user' => getenv('DB_USER'),
                                            // 'password' => getenv('DB_PASSWORD')
                                        )
);

//Autorization
$app['signingkey'] = 'signingkey';

$app->register(new \Silex\Provider\DoctrineServiceProvider(), $dbConfig);

//JWT Autentification
$app->before(function (Request $request) use($app)
{
//file_put_contents('log/request.txt', print_r($request, true), FILE_APPEND);

    $method = (!empty($request->getMethod()))?$request->getMethod():$_SERVER['REQUEST_METHOD'];
    $accept = (!empty($request->headers->get('Accept')))?$request->headers->get('Accept'):$_SERVER['CONTENT_TYPE'];
    $contentType = (!empty($request->headers->get('Content-Type')))?$request->headers->get('Content-Type'):$_SERVER['CONTENT_TYPE'];
    $authorization = (!empty($request->server->get('Authorization')))?$request->server->get('Authorization'):$_SERVER['HTTP_AUTHORIZATION'];


//file_put_contents('log/request.txt', $request->request->all(), FILE_APPEND);

    if (
        (($method == 'POST' || $method == 'PUT') && strpos($contentType, 'application/json') === 0)
        || ($method == 'GET' || $method == 'DELETE')
    )
    {
     	$token = substr($authorization, 7);
        $authService = new \HeliosAPI\AuthService($app['signingkey']);

        if(!$authService->IsTokenVerified($token))
            $app->abort(401, "Unauthorized.");
    }
    else
        $app->abort(415, "Unsupported Media Type.");
});


//Controllers
$app->mount('/heliosapi', new \HeliosAPI\HeliosAPIControllerProvider($app['signingkey']));

if ('test' == $app['env'])
    return $app;
else
    $app->run();
?>