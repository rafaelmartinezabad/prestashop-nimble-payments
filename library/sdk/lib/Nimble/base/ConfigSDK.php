<?php
/**  
 * Nimble-API-PHP : API v1.0
 *
 * PHP version 5.4.3
 *
 * @link http://github.com/...
 * @filesource
 */

/**
 * Class ConfigSDK
 * Placeholder for Nimble Config
 *
 * @package Base\Core
 */
class ConfigSDK
{

    const SDK_NAME = 'Nimble-PHP-SDK';
    const SDK_VERSION = '1.2';

    /**
	 *
	 * @var string OAUTH_URL constant var, with the base url to connect with Oauth
	 */
	const OAUTH_URL = "https://www.nimblepayments.com/auth/tsec/token";

	/**
	 *
	 * @var string NIMBLE_API_BASE_URLs constant var, with the base url of live enviroment to make requests
	 */
	const NIMBLE_API_BASE_URL = "https://www.nimblepayments.com/api/";

    /**
	 *
	 * @var string NIMBLE_API_BASE_URLs constant var, with the base url of demo enviroment to make requests
	 */
    const NIMBLE_API_BASE_URL_DEMO = "https://www.nimblepayments.com/sandbox-api/";

    /**
	 *
	 * @var int MAX_ATTEMPS constant var
	 */
    const MAX_ATTEMPS = 3;

    /**
	 *
	 * @var int TIMEOUT (seconds) constant var
	 */
    const TIMEOUT = 10;
}