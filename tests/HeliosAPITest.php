<?php
namespace HeliosAPI\Tests;

require_once __DIR__.'/../vendor/autoload.php';

use Silex\WebTestCase;
use HeliosAPI;

class HeliosAPITests extends WebTestCase
{
    private $_signKey = null;
    private $_issuer = null;
    private $_audience = null;
    private $_id = null;
    private $_expiration = 0;
    private $_requestHeader = null;

    public function createApplication()
    {
        $app_env = 'test';
        $app = require __DIR__ . "/../web/index.php";
        // $app['debug'] = true;
        unset($app['exception_handler']);
        return $app;
    }

    public function setUp()
    {
        parent::setUp();

        $this->_signKey = 'testsignkey';
        $this->_issuer = 'issuer';
        $this->_audience = 'audience';
        $this->_id = uniqid();
        $this->_expiration = 3600;
        $this->_requestHeader = array(
            'CONTENT_TYPE' => 'application/json',
            'Authorization' => 'Bearer'
        );
    }

    protected function GetHeaders ($token)
    {
        $headers = $this->_requestHeader;
        $keys = array_keys($headers);
        $headers[$keys[count($keys) - 1]] .= ' '.$token;
        
        return $headers;
    }
    
    public function testZbozi_success ()
    {
        $data = new \stdClass();
        $data->ID = 4;

        $authService = new \HeliosAPI\AuthService($this->_signKey);
        $token = $authService->GetNewToken($this->_issuer, $this->_audience, $this->_id, $this->_expiration, $data);

        $client = $this->createClient();
        $crawler = $client->request('GET', 'heliosapi/zbozi', array(), array(), $this->GetHeaders($token));

        $this->assertTrue($client->getResponse()->isOk());
        // $this->assertCount(1, $crawler->filter('h1:contains("Contact us")'));
        // $this->assertCount(1, $crawler->filter('form'));
    }
}
?>