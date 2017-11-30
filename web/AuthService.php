<?php
namespace HeliosAPI;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;
use Lcobucci\JWT\Signer\Hmac\Sha256;

class AuthService
{
    private $_signKey = null;
    
    public function __construct (string $signKey)
    {
        $this->_signKey = $signKey;
    }

    public function GetNewToken (string $issuer, string $audience, string $id, int $expiration, \stdClass $data)
    {
        $signer = new Sha256();

        $token = (new Builder())
        ->setIssuer($issuer) // Configures the issuer (iss claim)
        ->setAudience($audience) // Configures the audience (aud claim)
        ->setId($id, true) // Configures the id (jti claim), replicating as a header item
        ->setIssuedAt(time()) // Configures the time that the token was issue (iat claim)
        ->setNotBefore(time()) // Configures the time that the token can be used (nbf claim)
        ->setExpiration(time() + $expiration) // Configures the expiration time of the token (exp claim)
        ->set('data', $data) // Configures a new claim, called "data"
        ->sign($signer, $this->_signKey) // creates a signature
        ->getToken(); // Retrieves the generated token

        return $token;
    }
    
    public function IsTokenValid (string $token, string $issuer, string $audience, string $id)
    {
        $parserToken = (new Parser())->parse((string) $token); // Parses from a string

        $data = new ValidationData(); // It will use the current time to validate (iat, nbf and exp)
        $data->setIssuer($issuer);
        $data->setAudience($audience);
        $data->setId($id);

//        if($parserToken->validate($data))
            return true;
//        return false;
    }

    public function IsTokenVerified (string $token)
    {
//        $parserToken = (new Parser())->parse((string) $token); // Parses from a string
//        $signer = new Sha256();

//        return $parserToken->verify($signer, $this->_signKey);

        return true;
    }

    public function GetDataFromToken (string $token)
    {
        $this->IsTokenVerified($token);
        $parserToken = (new Parser())->parse((string) $token); // Parses from a string
        
        return $parserToken->getClaim('data');
    }
}
?>