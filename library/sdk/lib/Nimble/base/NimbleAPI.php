<?php
/**  
 * Nimble-API-PHP : API v1.0
 *
 * PHP version 5.4.3
 *
 * @link http://github.com/...
 * @filesource
 */
require_once 'ConfigSDK.php';
require_once 'NimbleAPILibrary.php';

/**
 * NimbleAPI is the api that does all the connection mechanism to each of the requests. It is primarily responsible for
 * handling each.
 */
class NimbleAPI
{

    /**
     * @source
     *
     * @var string $ uri. (Url service api rest)
     */
    public $uri;

    /**
     * @source
     *
     * @var string $ uri. (Url service oauth)
     */
    public $uri_oauth;

    /**
     *
     * @var string $ method. (Could be 'GET, POST PATH, DELETE, PUT')
     */
    public $method;

    /**
     *
     * @var array $ postfields. (Attribute Manager contain the post parameters if necessary)
     */
    private $postfields;

    /**
     *
     * @var string $ getfields. (Attribute Manager contain the get parameters if necessary)
     */
    private $getfields;

    /**
     *
     * @var Object $ authorization. (An object that contains the definition of authorization)
     */
    public $authorization;

    /**
     *
     * @var string $ laststatuscode (contain the last code for the last request: 401, 200, 500)
     */
    private $laststatuscode;

    /**
     *
     * @var int $ attemps (integer contain attempts numbers to a service)
     */
    private $attemps = 0;

    /**
     *
     * @var int $ max_attemps (maximum number of attempts at connections)
     */
    private $max_attemps = ConfigSDK::MAX_ATTEMPS;

    /**
     * Construct method. Start the object NimbleApi. Start the Object Authorization too.
     *
     * @param array $settings. (must contain at least clientId and clientSecret vars)
     * @throws Exception. (Return exception if not exist clientId or clientSecret)
     */
    public function __construct (array $settings)
    {
        if (! in_array('curl', get_loaded_extensions())) {
            throw new Exception('You need to install cURL, see: http://curl.haxx.se/docs/install.html');
        }
        
        if ( empty($settings['clientId']) || empty($settings['clientSecret'])) {
            throw new Exception('secretKey or clientId cannot be null or empty!');
        }

        if ( empty($settings['mode']) ) {
            throw new Exception('mode cannot be null or empty!');
        }
        try {
            if( $settings['mode'] == 'real') {
                $this->uri = ConfigSDK::NIMBLE_API_BASE_URL;
            } else {
                $this->uri = ConfigSDK::NIMBLE_API_BASE_URL_DEMO;
            }

            $this->authorization = new authorization();
            $this->authorization->setClientId($settings['clientId']);
            $this->authorization->setClientSecret($settings['clientSecret']);
            if (! $this->authorization->IsAccessParams()) {
                $this->authorization->getAuthorization($this);
            }
        }
        catch (Exception $e) {
            throw new Exception('Failed to instantiate Authorization: ' . $e);
        }
    }

    /**
     * Method responsible for making Rest API calls @ Return $response from rest api.
     *
     * @return $response
     */
    public function rest_api_call ()
    {
        try {
            if (! isset($curl_connect))
                $curl_connect = curl_init();
            
            $this->authorization->addHeader('Content-Type', 'application/json');
            $this->authorization->addHeader('Accept', 'application/json');
            
            $getfields = $this->getGetfields();
            $postfields = $this->getPostfields();
            
            $this->authorization->buildAccessHeader();
            $header = $this->authorization->buildHeader();
            
            if(!empty($this->uri_oauth)){
                $url = $this->uri_oauth;
                $this->uri_oauth = "";
            } else
                $url = $this->uri;

            $options = array(
                    CURLOPT_HTTPHEADER => $header,
                    CURLOPT_URL => $url,
                    CURLOPT_CUSTOMREQUEST => $this->method, // GET POST PUT PATCH DELETE
                    CURLOPT_HEADER => false,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => ConfigSDK::TIMEOUT
            );

            if (! is_null($postfields)) {
                $options[CURLOPT_POSTFIELDS] = $postfields;
            } else {
                if ($getfields !== '') {
                    $options[CURLOPT_URL] .= $getfields;
                }
            }
            
            curl_setopt_array($curl_connect, ($options));

            //SSL PROBLEMS
            curl_setopt($curl_connect, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl_connect, CURLOPT_SSL_VERIFYHOST, false);
            
            $response = json_decode(curl_exec($curl_connect), true);

            $this->setLastStatusCode(curl_getinfo($curl_connect, CURLINFO_HTTP_CODE));
            $this->getLastStatusCode() == 200 ? $this->setAttemps(0) : $this->attemps ++;
            
            // getLastStatusCode() return 0 in timeout
            if (($this->getLastStatusCode() == 401 or $this->getLastStatusCode() == 403 or $this->getLastStatusCode() == 0)  and
                $this->getAttemps() <= $this->max_attemps) {
                $response = $this->rest_api_call();
            }

            $this->setAttemps(0);
            if ($this->authorization->is_preauthorized_request) {
                $this->authorization->setAccessParams($response);
            }
            
            curl_close($curl_connect);
            return $response;
        }
        catch (Exception $e) {
            throw new Exception('Failed to send Data in rest_api_call: ' . $e);
        }
    }

    /**
     * Method setGetfields example: '?grant_type=client_credentials&scope=read'
     *
     * @param string $getfields
     * @return NimbleAPI
     */
    public function setGetfields ($getfields)
    {
        $this->postfields = null;
        
        $search = array(
                '#',
                ',',
                '+',
                ':'
        );
        $replace = array(
                '%23',
                '%2C',
                '%2B',
                '%3A'
        );
        $getfields = str_replace($search, $replace, $getfields);
        
        $this->getfields = $getfields;
        
        return $this;
    }

    /**
     * Method setPostfields
     *
     * @param string $postfields
     * @return NimbleAPI object
     */
    public function setPostfields ($postfields)
    {
        $this->getfields = null;
        $this->postfields = $postfields;
        
        return $this;
    }

    /**
     * Method getGetfields
     *
     * @return string
     */
    public function getGetfields ()
    {
        return $this->getfields;
    }

    /**
     * Method getPostfields
     *
     * @return NimbleAPI object
     */
    public function getPostfields ()
    {
        return $this->postfields;
    }

    /**
     * Method getLastStatusCode.
     *
     *
     * @return string. Return the last status code 401 UnAuthorized, 200 Accept.
     */
    public function getLastStatusCode ()
    {
        return $this->laststatuscode;
    }

    /**
     * Method setLastStatusCode
     *
     * @param unknown $code
     * @return NimbleAPI object
     */
    public function setLastStatusCode ($code)
    {
        $this->laststatuscode = $code;
        return $this;
    }

    /**
     * Method getAttemps.
     *
     * @return int. Returns the number of attempts Connection
     */
    public function getAttemps ()
    {
        return $this->attemps;
    }

    /**
     * Method setAttemps
     *
     * @param int $attemps.
     * @return NimbleAPI object. Modifies the number of attempts Connection
     */
    public function setAttemps ($attemps)
    {
        $this->attemps = $attemps;
        return $this;
    }

    /**
     * Method clear. Clear all attributes of class NimbleApi except Object(for example: authorization)
     */
    public function clear ()
    {
        try {
            foreach ($this as $key => &$valor) {
                eval('$isArray=is_array($this->' . $key . ');');
                eval('$isObject=is_object($this->' . $key . ');');
                if (! $isObject) {
                    if ($isArray) {
                        eval('$this->' . $key . '=array();');
                    } else {
                        eval('$this->' . $key . '=null;');
                    }
                }
            }
        }
        catch (Exception $e) {
            throw new Exception('Failed to clear attrubutes: ' . $e);
        }
    }
}
