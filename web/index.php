<?php
namespace HeliosAPI;

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
$app['debug'] = true;
if(getenv('DEBUG'))
    $app['debug'] = true;

//Set logging
$logPath = __DIR__.'/log/';
ini_set("log_errors", 1);
ini_set("error_log", $logPath."php-error.log");
if($app['debug']) $app->register(new \Silex\Provider\MonologServiceProvider(), array('monolog.logfile' => $logPath.'development.log'));
$app->register(new \Silex\Provider\MonologServiceProvider(), array('monolog.logfile' => $logPath.'development.log'));

//Database
$dbConfig = array(
    'db.options' => array(
        'driver' => 'sqlsrv',
        'host' => 'brana.neudrinks.cz',
        'port' => '1433',
        'dbname' => 'Helios003',
        'user' => 'petr.mensik',
        'password' => 'P*csX18!ax'
    )
);

//Autorization
$app['signingkey'] = 'signingkey';

$app->register(new \Silex\Provider\DoctrineServiceProvider(), $dbConfig);


//JWT Autentification
$app->before(function (Request $request) use($app)
{
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json'))
    {
        $token = substr($request->server->get('Authorization'), 7);
        $authService = new \HeliosAPI\AuthService($app['signingkey']);

        if(!$authService->IsTokenVerified($token))
            $app->abort(404, "Invalid request token.");
    }
    else
        $app->abort(404, "Invalid request.");
});

//Controllers
$app->mount('/heliosapi', new \HeliosAPI\HeliosAPIControllerProvider($app['signingkey']));

if ('test' == $app['env'])
    return $app;
else
    $app->run();
?>

