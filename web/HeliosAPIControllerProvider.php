<?php
namespace HeliosAPI;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
// use HeliosAPI\Types;

class HeliosAPIControllerProvider implements ControllerProviderInterface
{

    protected function GetRequestData() 
    {
        $authService = new \HeliosAPI\AuthService($this->_signKey);
        $token = $authService->GetNewToken($this->_issuer, $this->_audience, $this->_id, $this->_expiration, $this->_data);

        $this->assertInstanceOf('stdClass', $authService->GetDataFromToken($token), 'GetDataFromToken returns stdClass');
    }

    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];

        $controllers->get('/zbozi', function (Application $app) 
        {
            $sql = 'SELECT * FROM TabKmenZbozi WHERE ID = ?';
            $sqlData = array(1);
            $data = $app['db']->fetchAssoc($sql, $sqlData);
            print_r($data);
            return $data;
        });

        return $controllers;
    }
}
?>