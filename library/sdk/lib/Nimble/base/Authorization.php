<?php
/**  
 * Nimble-API-PHP : API v1.0
 *
 * PHP version 5.4.3
 *
 * @namespace Base
 * @link http://github.com/...
 * @filesource
 */
require_once 'ConfigSDK.php';

/**
 * Implements the Authorization header of the request to perform the identification correctly according to the type of
 * request
 */
class Authorization
{

    /**
     *
     * @var string $ clientId
     */
    private $clientId;

    /**
     *
     * @var string $ clientSecret
     */
    private $clientSecret;

    /**
     *
     * @var string $ token_type
     */
    private $token_type;

    /**
     *
     * @var string $ access_token
     */
    private $access_token;

    /**
     *
     * @var string $ is_authorized_request
     */
    public $is_preauthorized_request;

    /**
     *
     * @var int. $ expires_in. Time in seconds for the token ceases to be valid
     */
    private $expires_in;

    /**
     *
     * @var string
     */
    private $scope;

    /**
     * Function addheader, add a type param with a context in the header
     *
     * @param string $param
     * @param string $content
     */
    public function addHeader ($param, $content)
    {
        $this->header[$param] = $param . ': ' . $content;
    }

    /**
     * Method deleteheader, add a type param with a context in the header
     *
     * @param string $param
     */
    public function deleteHeader ($param)
    {
        unset($this->header[$param]);
    }

    /**
     * Method buildAuthorizationHeader, add Authorization header.
     */
    public function buildAuthorizationHeader ()
    {
        $this->addHeader('Authorization', 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret));
        $this->is_preauthorized_request = 1;
    }

    /**
     * Method buildAccessHeader, add Access Authorization header.
     *
     * @throws Exception
     */
    public function buildAccessHeader ()
    {
        $this->is_preauthorized_request = 0;
         if ($this->IsAccessParams())
             $this->addHeader('Authorization', $this->token_type . ' ' . $this->access_token);

    }

    /**
     * Method setAccessParams, set token_type and access_token
     *
     * @param string $response
     */
    public function setAccessParams ($response)
    {
        if ((isset($response['token_type'])) && (isset($response['access_token']))) {
            $this->token_type = $response['token_type'];
            $this->access_token = $response['access_token'];
        } else {
            throw new Exception('The identification was incorrect, check clientId and clientSecret');
        }
        
        if ((isset($response['expires_in'])) && (isset($response['scope']))) {
            $this->expires_in = $response['expires_in'];
            $this->scope = $response['scope'];
        }
    }

    /**
     * Method IsAccessParams, return TRUE if exist the params.
     *
     * @return boolean
     */
    public function IsAccessParams ()
    {
        if (($this->token_type != null) && ($this->access_token != null)) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Method buildHeader. Returns an array of header parameters.
     *
     * @return multitype:
     */
    public function buildHeader ()
    {
        $header = array();
        foreach ($this->header as $value) {
            if ($value != "") {
                array_push($header, $value);
            }
        }
        return $header;
    }

    /**
     * Method getClientId
     *
     * @return string
     */
    public function getClientId ()
    {
        return $this->clientId;
    }

    /**
     * Method setClientId
     *
     * @param string $clientId
     */
    public function setClientId ($clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * Method getClientSecret
     *
     * @return string
     */
    public function getClientSecret ()
    {
        return $this->clientSecret;
    }

    /**
     * Method setClientSecret
     *
     * @param string $clientSecret
     */
    public function setClientSecret ($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * Method getAccess_Token
     *
     * @return string
     */
    public function getAccess_Token ()
    {
        return $this->access_token;
    }

    /**
     * Method setAccess_Token
     *
     * @param string $access_token
     */
    public function setAccess_Token ($access_token)
    {
        $this->access_token = $access_token;
    }

    /**
     * Method getToken_Type
     *
     * @return string
     */
    public function getToken_Type ()
    {
        return $this->access_token;
    }

    /**
     * Method setToken_Type
     *
     * @param string $token_type
     */
    public function setToken_Type ($token_type)
    {
        $this->token_type = $token_type;
    }

    public function getAuthorization ($NimbleApi)
    {
        if (empty($NimbleApi)) {
            throw new Exception('$NimbleApi parameter is empty');
        }
        try {
            $NimbleApi->uri_oauth = ConfigSDK::OAUTH_URL;
            $NimbleApi->setGetfields('?grant_type=client_credentials&scope=PAYMENT');
            $NimbleApi->method = 'POST';
            $NimbleApi->authorization->buildAuthorizationHeader();
            $response = $NimbleApi->rest_api_call();

            $NimbleApi->setGetfields(null);

            if(isset($response['result']) && $response['result']['code'] != "200"){
                throw new Exception($response['result']['code'].' '.$response['result']['info']);
            }
            else{
                $this->setAccessParams($response);
            }

            return true;
        }
        catch (Exception $e) {
            throw new Exception('Failed in getAuthorization: ' . $e);
        }
    }
}