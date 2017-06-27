<?php
namespace HeliosAPI\Tests;

require_once __DIR__.'/../vendor/autoload.php';

use Silex\WebTestCase;
use HeliosAPI;

class AuthServiceTests extends WebTestCase
{
    private $_signKey = null;
    private $_issuer = null;
    private $_audience = null;
    private $_id = null;
    private $_expiration = 0;
    private $_data = null;

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
        $this->_data = json_decode('{"mockdata":{"param1":"value1","param2":"value2","param3":"value3"}}');
    }

    public function testGetNewToken_success()
    {
        $authService = new \HeliosAPI\AuthService($this->_signKey);
        $token = $authService->GetNewToken($this->_issuer, $this->_audience, $this->_id, $this->_expiration, $this->_data);
        
        $this->assertInstanceOf('Lcobucci\JWT\Token', $token, 'Token is type of Lcobucci\JWT\Token');
        $this->assertTrue($authService->IsTokenValid($token, $this->_issuer, $this->_audience, $this->_id), 'Token is valid');
        $this->assertTrue($authService->IsTokenVerified($token), 'Token is verified');
    }
    
    public function testGetNewToken_failure()
    {
        $authService = new \HeliosAPI\AuthService($this->_signKey);
        $token = $authService->GetNewToken('', '', '', 0, new \stdClass());

        $this->assertInstanceOf('Lcobucci\JWT\Token', $token, 'Token is type of Lcobucci\JWT\Token');
        $this->assertFalse($authService->IsTokenValid($token, $this->_issuer, $this->_audience, $this->_id), 'Token is valid');
        $this->assertTrue($authService->IsTokenVerified($token), 'Token is verified');
    }
    
    public function testIsTokenValid_success()
    {
        $authService = new \HeliosAPI\AuthService($this->_signKey);
        $token = $authService->GetNewToken($this->_issuer, $this->_audience, $this->_id, $this->_expiration, $this->_data);
        
        $this->assertInternalType('bool', $authService->IsTokenValid($token, $this->_issuer, $this->_audience, $this->_id), 'IsValid returns bool');
        $this->assertTrue($authService->IsTokenValid($token, $this->_issuer, $this->_audience, $this->_id), 'IsValid returns true');
    }

    public function testIsTokenValid_failure()
    {
        $authService = new \HeliosAPI\AuthService($this->_signKey);
        $token = $authService->GetNewToken('', '', '', 0, new \stdClass());
        
        $this->assertInternalType('bool', $authService->IsTokenValid($token, $this->_issuer, $this->_audience, $this->_id), 'IsValid returns bool');
        $this->assertFalse($authService->IsTokenValid($token, $this->_issuer, $this->_audience, $this->_id), 'IsValid returns false');
    }

    public function testIsTokenVerified_success()
    {
        $authService = new \HeliosAPI\AuthService($this->_signKey);
        $token = $authService->GetNewToken($this->_issuer, $this->_audience, $this->_id, $this->_expiration, $this->_data);

        $this->assertInternalType('bool', $authService->IsTokenVerified($token), 'IsVerified returns bool');
        $this->assertTrue($authService->IsTokenVerified($token), 'IsVerified returns true');
    }

    public function testIsTokenVerified_failure()
    {
        $signKeyDifferent = $this->_signKey.'differ by this from right key';
        $authService = new \HeliosAPI\AuthService($this->_signKey);
        $token = $authService->GetNewToken($this->_issuer, $this->_audience, $this->_id, $this->_expiration, $this->_data);
        $authServiceTest = new \HeliosAPI\AuthService($signKeyDifferent);

        $this->assertInternalType('bool', $authServiceTest->IsTokenVerified($token), 'IsVerified returns bool');
        $this->assertFalse($authServiceTest->IsTokenVerified($token), 'IsVerified returns false');
    }

    public function testGetDataFromToken_success()
    {
        $authService = new \HeliosAPI\AuthService($this->_signKey);
        $token = $authService->GetNewToken($this->_issuer, $this->_audience, $this->_id, $this->_expiration, $this->_data);

        $this->assertInstanceOf('stdClass', $authService->GetDataFromToken($token), 'GetDataFromToken returns stdClass');
    }
}
?>