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
        $this->_signKey = $app['signingkey'];
        return $app;
    }

    public function setUp()
    {
        parent::setUp();

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
    
    public function testClients_success ()
    {
        $data = [
                    'name' => 'a',
                    'status' => 0,
                    'listfrom' => 10,
                    'listto' => 20,
                    'sort' => 'namedesc'
                ];

        $authService = new \HeliosAPI\AuthService($this->_signKey);
        $token = $authService->GetNewToken($this->_issuer, $this->_audience, $this->_id, $this->_expiration, new \stdClass());

        $client = $this->createClient();
        $crawler = $client->request('GET', 'heliosapi/clients', $data, array(), $this->GetHeaders($token));

        // Assert that the response status code is 2xx
        $this->assertTrue($client->getResponse()->isSuccessful(), 'response status is 2xx');
        // Assert that the "Content-Type" header is "application/json"
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'),'the "Content-Type" header is "application/json"');
        $this->assertTrue($client->getResponse()->isOk());
        // Assert data
        $responseData = json_decode($client->getResponse()->getContent());
		$this->assertTrue(isset($responseData->totalrows) && is_numeric($responseData->totalrows) && isset($responseData->rows) && is_array($responseData->rows), 'Result part sent.');
    }


    // public function testClientlist_success () {}

    // public function testClientlist_failure () {}

    // public function testClientdetail_success () {}

    // public function testClientdetail_failure () {}

    // public function testProductlist_success () {}
    
    // public function testProductlist_failure () {}

    // public function testProductdetail_success () {}
    
    // public function testProductdetail_failure () {}

}
?>