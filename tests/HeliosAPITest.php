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

    public function testGETClients_success ()
    {
        $data = [
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

    public function testGETClientsWithFilter_success ()
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

    public function testGETClientsId_success ()
    {
        $data = [];
        $clientId = 4;

        $authService = new \HeliosAPI\AuthService($this->_signKey);
        $token = $authService->GetNewToken($this->_issuer, $this->_audience, $this->_id, $this->_expiration, new \stdClass());

        $client = $this->createClient();
        $crawler = $client->request('GET', 'heliosapi/clients/'.$clientId, $data, array(), $this->GetHeaders($token));

        // Assert that the response status code is 2xx
        $this->assertTrue($client->getResponse()->isSuccessful(), 'response status is 2xx');
        // Assert that the "Content-Type" header is "application/json"
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'),'the "Content-Type" header is "application/json"');
        $this->assertTrue($client->getResponse()->isOk());
        // Assert data
        $responseData = json_decode($client->getResponse()->getContent());
		$this->assertTrue(isset($responseData->id) && is_numeric($responseData->id) && isset($responseData->orgnum) && is_numeric($responseData->orgnum), 'Result part sent.');
    }

    public function testPOSTClients_success ()
    {
        $data = [
                    'orgnum' => 999999016,
                    'name' => 'Jmeno klienta',
                    'name2' => 'Druhe jmeno klienta',
                    'street' => 'Ulice',
                    'streetorinumber' => '10',
                    'streetdesnumber' => '20',
                    'city' => 'Mesto',
                    'status' => 0,
                    'parentid' => null,
                    'zip' => '12345',
                    'contact' => 'Kontakt',
                    'ic' => null,
                    'dic' => null
                ];

        $authService = new \HeliosAPI\AuthService($this->_signKey);
        $token = $authService->GetNewToken($this->_issuer, $this->_audience, $this->_id, $this->_expiration, new \stdClass());

        $client = $this->createClient();
        $crawler = $client->request('POST', 'heliosapi/clients', $data, array(), $this->GetHeaders($token));

        // Assert that the response status code is 201
        $this->assertSame(201, $client->getInternalResponse()->getStatus(), 'response status is 201');
        // Assert that the "Location" header contains URL for created clients detail
        $this->assertRegExp("/clients\/[1-9]+[0-9]*/", $client->getInternalResponse()->getHeader('Location', true),'the "Location" header contains URL for created client detail');
    }


    public function testGETProducts_success ()
    {
        $data = [
            'name' => 'a',
            'centernumber' => null,
            'regnumber' => 1,
            'listfrom' => 10,
            'listto' => 20,
            'sort' => 'namedesc'
        ];

        $authService = new \HeliosAPI\AuthService($this->_signKey);
        $token = $authService->GetNewToken($this->_issuer, $this->_audience, $this->_id, $this->_expiration, new \stdClass());

        $client = $this->createClient();
        $crawler = $client->request('GET', 'heliosapi/products', $data, array(), $this->GetHeaders($token));

        // Assert that the response status code is 2xx
        $this->assertTrue($client->getResponse()->isSuccessful(), 'response status is 2xx');
        // Assert that the "Content-Type" header is "application/json"
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'),'the "Content-Type" header is "application/json"');
        $this->assertTrue($client->getResponse()->isOk());
        // Assert data
        $responseData = json_decode($client->getResponse()->getContent());
		$this->assertTrue(isset($responseData->totalrows) && is_numeric($responseData->totalrows) && isset($responseData->rows) && is_array($responseData->rows), 'Result part sent.');  
    }
    
    public function testGETProductsId_success ()
    {
        $data = [];
        $productId = 4;

        $authService = new \HeliosAPI\AuthService($this->_signKey);
        $token = $authService->GetNewToken($this->_issuer, $this->_audience, $this->_id, $this->_expiration, new \stdClass());

        $client = $this->createClient();
        $crawler = $client->request('GET', 'heliosapi/products/'.$productId, $data, array(), $this->GetHeaders($token));

        // Assert that the response status code is 2xx
        $this->assertTrue($client->getResponse()->isSuccessful(), 'response status is 2xx');
        // Assert that the "Content-Type" header is "application/json"
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'),'the "Content-Type" header is "application/json"');
        $this->assertTrue($client->getResponse()->isOk());
        // Assert data
        $responseData = json_decode($client->getResponse()->getContent());
		$this->assertTrue(isset($responseData->id) && is_numeric($responseData->id) && isset($responseData->regnum) && strlen($responseData->regnum) <= 30, 'Result part sent.');
    }
    
}
?>