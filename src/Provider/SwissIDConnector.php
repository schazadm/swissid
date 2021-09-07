<?php

/**
 * File containing the SwissIDConnector class
 *
 * @package  SwissIDConnector
 * @author   Sean Natoewal <sean@natoewal.nl>
 * @link     https://github.com/natoewal/SwissIDConnector
 * @filesource
 */

use \Firebase\JWT\JWT;
use \Firebase\JWT\JWK;

/**
 * SwissIDConnector class
 *
 * Class to interact with the SwissID IdP
 *
 * @package  SwissIDConnector
 * @author   Sean Natoewal <sean@natoewal.nl>
 * @link     https://github.com/natoewal/SwissIDConnector
 */
class SwissIDConnector
{

    /**
     * The RP's client_id
     *
     * @var string
     */
    private $clientID;

    /**
     * The RP's client_secret
     *
     * @var string
     */
    private $clientSecret;

    /**
     * The RP's redirect URI registered with SwissID
     *
     * @var string
     */
    private $redirectURI;

    /**
     * The environment for which this object was initialized
     *
     * @var string
     */
    private $environment;

    /**
     * SwissID's OpenID configuration endpoints
     *
     * @var array
     */
    private $openidConfigurationEndpoints;

    /**
     * SwissID's OpenID configuration
     *
     * @var array
     */
    private $openidConfiguration;

    /**
     * SwissID's keys
     *
     * @var array
     */
    private $keys;

    /**
     * The scope, requested at the time of authentication
     *
     * @var string
     */
    private $scope;

    /**
     * The Quality of Authentication, requested at the time of authentication
     *
     * @var string
     */
    private $qoa;

    /**
     * The Quality of Registration, requested at the time of authentication
     *
     * @var string
     */
    private $qor;

    /**
     * The language of the end-user interface, requested at the time of authentication
     *
     * @var string
     */
    private $locale;

    /**
     * The login hint, requested at the time of authentication
     *
     * @var string
     */
    private $loginHint;

    /**
     * Whether and for what the IdP should prompt the end-user, requested at the time of authentication
     *
     * @var string
     */
    private $prompt;

    /**
     * The allowable elapsed time in seconds since the last time the end-user was actively authenticated, requested at the time of authentication
     *
     * @var int
     */
    private $maxAge;

    /**
     * Indicator on whether this object was initialized
     *
     * @var bool
     */
    private $connectorInitialized;

    /**
     * Indicator on whether the authentication was initialized
     *
     * @var bool
     */
    private $authenticationInitialized;

    /**
     * Indicator on whether the authentication was completed
     *
     * @var bool
     */
    private $authenticated;

    /**
     * Indicator on whether the step-up was initialized
     *
     * @var array
     */
    private $stepUpInitialized;

    /**
     * Indicator on whether the step-up was completed
     *
     * @var array
     */
    private $steppedUp;

    /**
     * Authorization code
     *
     * @var string
     */
    private $authorizationCode;

    /**
     * Access token
     *
     * @var string
     */
    private $accessToken;

    /**
     * Expire timestamp of access token
     *
     * @var int
     */
    private $accessTokenExpirationTimestamp;

    /**
     * Indicator on whether an automated re-authentication has
     * been attempted in an attempt to re-obtain an access token
     *
     * @var bool
     */
    private $automatedReauthenticationAttempted;

    /**
     * Refresh token
     *
     * @var string
     */
    private $refreshToken;

    /**
     * End user info
     *
     * @var stdClass
     */
    private $endUserInfo;

    /**
     * State used for the authentication or step-up request
     *
     * @var string
     */
    private $state;

    /**
     * Nonce used for the authentication or step-up request
     *
     * @var string
     */
    private $nonce;

    /**
     * Error
     *
     * @var array
     */
    private $error;

    /**
     * Constructor
     *
     * After instantiating this object, make sure to check if any error have occurred.
     *
     * If a brand new instance needs to be created, all first four parameters must be specified and the last three are optional.
     * If an existing instances needs to be restored from the session, all first four parameters are optional and the last three are ignored.
     *
     * @param string $clientID The RP's client_id
     * @param string $clientSecret The RP's client secret
     * @param string $redirectURI The RP's redirect URI registered with SwissID
     * @param string $environment The environment for which to initialize this object. Valid values are 'INT', 'PROD'
     * @param string $accessToken A previously obtained and securely stored access token
     * @param int $accessTokenExpirationTimestamp The expiration timestamp of a previously obtained and securely stored access token
     * @param string $refreshToken A previously obtained and securely stored refresh token
     */
    public function __construct(string $clientID = null, string $clientSecret = null, string $redirectURI = null, string $environment = null, $accessToken = null, $accessTokenExpirationTimestamp = null, $refreshToken = null)
    {
        $requiredParametersSet = (!is_null($clientID) || !is_null($clientSecret) || !is_null($redirectURI) || !is_null($environment));

        /**
         * Start session if not already started
         */
        if (!isset($_SESSION)) {
            session_start();
        } elseif (!isset($_SESSION[get_class($this)]) && !$requiredParametersSet) {
            /**
             * If class members are not available from the session,
             * and too few parameters have been specified,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => '',
                'error_description' => 'Too few parameters have been specified',
            );
            return;
        }

        if (isset($_SESSION[get_class($this)])) {
            /**
             * Reconstruct class member variables from the session,
             * if not all parameters are set
             * and if data is available in the session
             */
            foreach ($_SESSION[get_class($this)] as $key => $val) {
                $this->$key = $val;
            }

            /**
             * Complete the authentication, if not already done
             */
            if (!$this->authenticated) {
                $this->completeAuthentication();
            }
        } elseif (!isset($_SESSION[get_class($this)])) {
            /**
             * If class members are not available from the session,
             * and all parameters have been specified,
             * initialize the class member variables
             */
            $this->openidConfigurationEndpoints = array(
                'INT' => 'https://login.int.swissid.ch/idp/oauth2/.well-known/openid-configuration',
                'PROD' => 'https://login.swissid.ch/idp/oauth2/.well-known/openid-configuration'
            );
            $this->connectorInitialized = false;
            $this->authenticationInitialized = false;
            $this->authenticated = false;
            $this->stepUpInitialized = array(
                'qor1' => false
            );
            $this->steppedUp = array(
                'qor1' => false
            );
            $this->automatedReauthenticationAttempted = false;

            /**
             * Verify environment parameter
             */
            if (is_null($rs = $this->verifyParameter('environment', $environment))) {
                return false;
            } elseif (!$rs['valid']) {
                /**
                 * If an invalid environment parameter has been specified,
                 * return an error
                 */
                $this->error = array(
                    'line' => __LINE__,
                    'type' => 'object',
                    'error' => '',
                    'error_description' => 'The environment parameter is invalid. Valid values are ' . $rs['allowedValues']
                );
                return;
            }

            if (!$openidConfigurationEncoded = file_get_contents($this->openidConfigurationEndpoints[$environment])) {
                /**
                 * If SwissID's OpenID configuration could not be read,
                 * return an error
                 */
                $this->error = array(
                    'line' => __LINE__,
                    'type' => 'object',
                    'error' => '',
                    'error_description' => 'An error has occurred while trying to read the openid configuration'
                );
                return;
            } else {
                if (!$openidConfigurationDecoded = json_decode($openidConfigurationEncoded, $associativeP = true)) {
                    /**
                     * If an error has occurred while trying to decode the JSON response,
                     * return an error
                     */
                    $this->error = array(
                        'line' => __LINE__,
                        'type' => 'object',
                        'error' => json_last_error(),
                        'error_description' => json_last_error_msg()
                    );
                    return;
                } else {
                    /**
                     * On success, update the class member variables
                     */
                    $this->clientID = $clientID;
                    $this->clientSecret = $clientSecret;
                    $this->redirectURI = $redirectURI;
                    $this->environment = $environment;
                    $this->openidConfiguration = $openidConfigurationDecoded;
                    $this->connectorInitialized = true;

                    if (!$keysEncoded = file_get_contents($this->openidConfiguration['jwks_uri'])) {
                        /**
                         * If SwissID's OpenID configuration could not be read,
                         * return an error
                         */
                        $this->error = array(
                            'line' => __LINE__,
                            'type' => 'object',
                            'error' => '',
                            'error_description' => 'An error has occurred while trying to read the keys'
                        );
                        return;
                    } else {
                        if (!$keysDecoded = json_decode($keysEncoded, $associativeP = true)) {
                            /**
                             * If an error has occurred while trying to decode the JSON response,
                             * return an error
                             */
                            $this->error = array(
                                'line' => __LINE__,
                                'type' => 'object',
                                'error' => json_last_error(),
                                'error_description' => json_last_error_msg()
                            );
                            return;
                        } else {
                            $this->keys = $keysDecoded;

                            if (!$rs2 = json_decode($rs, $associativeP = true)) {
                                /**
                                 * If an error has occurred while trying to decode the JSON response,
                                 * return an error
                                 */
                                $this->error = array(
                                    'line' => __LINE__,
                                    'type' => 'object',
                                    'error' => json_last_error(),
                                    'error_description' => json_last_error_msg()
                                );
                                return false;
                            }

                            /**
                             * Store the access and refresh token, if provided
                             */
                            if (!is_null($accessToken)) {
                                $this->accessToken = $accessToken;
                                $this->accessTokenExpirationTimestamp = time() + (int)$rs2['expires_in'];
                            }
                            if (!is_null($refreshToken)) {
                                $this->refreshToken = $refreshToken;
                            }
                            return;
                        }
                    }
                }
                /**
                 * If an unknown error has occurred,
                 * return an error
                 */
                $this->error = array(
                    'line' => __LINE__,
                    'type' => 'object',
                    'error' => '',
                    'error_description' => 'An unknown has occured whilte trying to initialize this object'
                );
                return;
            }
        }
    }

    /**
     * Method to obtain a new access token on the basis of an access token
     *
     * @return bool
     */
    private function refreshAccessToken(): bool
    {
        if (is_null($this->refreshToken)) {
            /**
             * If no refresh token is available,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => '',
                'error_description' => 'There is no refresh token available'
            );
            return false;
        }

        /**
         * Try to redeem the refresh token at the token endpoint
         */
        $params = array(
            'scope' => $this->scope,
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientID,
            'refresh_token' => $this->refreshToken
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERPWD, $this->clientID . ':' . $this->clientSecret);
        curl_setopt($ch, CURLOPT_URL, $this->openidConfiguration['token_endpoint']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $rs = curl_exec($ch);
        $httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpStatus != 200) {
            /**
             * If the http status was different from 200,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => null,
                'error_description' => 'Unexpected http status ' . $httpStatus
            );
            return false;
        }

        if (!$rs2 = json_decode($rs, $associativeP = true)) {
            /**
             * If an error has occurred while trying to decode the JSON response,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => json_last_error(),
                'error_description' => json_last_error_msg()
            );
            return false;
        }

        /**
         * Store the access and refresh token
         */
        $this->accessToken = $rs2['access_token'];
        $this->accessTokenExpirationTimestamp = time() + (int)$rs2['expires_in'];
        $this->refreshToken = $rs2['refresh_token'];

        return true;
    }

    /**
     * Method to verify a parameter
     *
     * If no error has occurred, this method returns null, otherwise an array:
     *
     * - valid, whether the parameter was valid
     * - alowedValues, the allowed values for the parameter specified
     *
     * @param string $type The type of parameter to verify. Valid values are 'environment', 'scope', 'qoa', 'qor', 'locale', 'login_hint', 'prompt', 'maxAge', 'tokenType'
     * @param type $value the value to verify for the parameter specified
     * @return array|null
     */
    private function verifyParameter(string $type, $value): ?array
    {
        if (!in_array($type, array('environment', 'scope', 'qoa', 'qor', 'locale', 'login_hint', 'prompt', 'maxAge', 'tokenType'))) {
            /**
             * If an invalid type has been specified,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => '',
                'error_description' => 'The type parameter "' . $type . '" is invalid. Valid values are environment, scope, qoa, qor, locale, prompt, maxAge'
            );
            return null;
        }

        switch ($type) {
            case 'environment':
                $allowedValues = array('INT', 'PROD');
                return array(
                    'valid' => in_array($value, $allowedValues),
                    'allowedValues' => implode(', ', $allowedValues)
                );
                break;
            case 'scope':
                $allowedValues = array('openid', 'profile', 'email', 'phone', 'address');
                $requestedScopes = explode(' ', $value);
                $invalidParameter = false;
                foreach ($requestedScopes as $requestedScope) {
                    $requestedScopeFound = false;
                    $requestedScopeFoundMultipleTimes = false;
                    foreach ($allowedValues as $allowedValue) {
                        if ($requestedScope == $allowedValue && !$requestedScopeFound) {
                            $requestedScopeFound = true;
                        } elseif ($requestedScope == $allowedValue && $requestedScopeFound) {
                            $requestedScopeFoundMultipleTimes = true;
                        }
                    }
                    if (!$requestedScopeFound || $requestedScopeFoundMultipleTimes) {
                        return array(
                            'valid' => false,
                            'allowedValues' => implode(', ', $allowedValues)
                        );
                    }
                }
                return array(
                    'valid' => true,
                    'allowedValues' => implode(', ', $allowedValues)
                );
                break;
            case 'qoa':
                $allowedValues = array('qoa1', 'qoa2');
                return array(
                    'valid' => in_array($value, $allowedValues),
                    'allowedValues' => implode(', ', $allowedValues)
                );
                break;
            case 'qor':
                $allowedValues = array('qor0', 'qor1', 'qor2');
                return array(
                    'valid' => in_array($value, $allowedValues),
                    'allowedValues' => implode(', ', $allowedValues)
                );
                break;
            case 'locale':
                $allowedValues = array('de', 'fr', 'it', 'en');
                return array(
                    'valid' => in_array($value, $allowedValues),
                    'allowedValues' => implode(', ', $allowedValues)
                );
                break;
            case 'login_hint':
                return array(
                    'valid' => filter_var($value, FILTER_VALIDATE_EMAIL),
                    'allowedValues' => 'a valid e-mail address'
                );
                break;
            case 'prompt':
                $allowedValues = array('none', 'login', 'consent');
                return array(
                    'valid' => in_array($value, $allowedValues),
                    'allowedValues' => implode(', ', $allowedValues)
                );
                break;
            case 'maxAge':
                return array(
                    'valid' => (is_numeric($value) && $value >= 0),
                    'allowedValues' => 'a postive numeric value'
                );
                break;
            case 'tokenType':
                $allowedValues = array('ACCESS', 'REFRESH');
                return array(
                    'valid' => in_array($value, $allowedValues),
                    'allowedValues' => implode(', ', $allowedValues)
                );
                break;
            default:
                /**
                 * If an unknown error type of parameter was specified,
                 * return an error
                 */
                $this->error = array(
                    'line' => __LINE__,
                    'type' => 'object',
                    'error' => '',
                    'error_description' => 'An unknown type of parameter was specified. Valid values are scope, qoa, qor, locale, prompt, maxAge'
                );
                return null;
                break;
        }
        /**
         * If an unknown error has occurred,
         * return an error
         */
        $this->error = array(
            'line' => __LINE__,
            'type' => 'object',
            'error' => '',
            'error_description' => 'An unknown has occured while trying to verify the parameter'
        );
        return null;
    }

    /**
     * Method to complete the authentication of the end-user
     *
     * If an error has occurred, this method returns false, otherwise true
     */
    private function completeAuthentication(): bool
    {

        if (!$this->connectorInitialized) {
            /**
             * If this object was not correctly initialized,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => '',
                'error_description' => 'This object was not correctly initialized'
            );
            return false;
        } elseif (!$this->authenticationInitialized) {
            /**
             * If this object was correctly initialized,
             * but the authentication was not initialized,
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => '',
                'error_description' => 'The authentication was not initialized'
            );
            return false;
        } elseif (!$this->authenticated) {
            /**
             * If this object was correctly initialized,
             * and the authentication was initialized,
             * but the authentication was not completed,
             * try to complete the authentication
             */
            if (isset($_GET['error']) && isset($_GET['error_description'])) {
                /**
                 * If an error occurred while trying to complete the authentication,
                 * relay the error
                 */
                $this->error = array(
                    'line' => __LINE__,
                    'type' => 'swissid',
                    'error' => $_GET['error'],
                    'error_description' => $_GET['error_description']
                );
                return false;
            } elseif (isset($_GET['code'])) {
                /**
                 * If an authorization code was obtained,
                 * try to redeem it at the token endpoint
                 */
                $this->authorizationCode = $_GET['code'];

                $params = array(
                    'grant_type' => 'authorization_code',
                    'code' => $this->authorizationCode,
                    'redirect_uri' => $this->redirectURI
                );
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_USERPWD, $this->clientID . ':' . $this->clientSecret);
                curl_setopt($ch, CURLOPT_URL, $this->openidConfiguration['token_endpoint']);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $rs = curl_exec($ch);
                $httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpStatus != 200) {
                    /**
                     * If the http status was different from 200,
                     * return an error
                     */
                    $this->error = array(
                        'line' => __LINE__,
                        'type' => 'object',
                        'error' => null,
                        'error_description' => 'Unexpected http status ' . $httpStatus
                    );
                    return false;
                }

                if (!$rs2 = json_decode($rs, $associativeP = true)) {
                    /**
                     * If an error has occurred while trying to decode the JSON response,
                     * return an error
                     */
                    $this->error = array(
                        'line' => __LINE__,
                        'type' => 'object',
                        'error' => json_last_error(),
                        'error_description' => json_last_error_msg()
                    );
                    return false;
                }

                /**
                 * Store the access and refresh token
                 */
                $this->accessToken = $rs2['access_token'];
                $this->accessTokenExpirationTimestamp = time() + (int)$rs2['expires_in'];
                $this->refreshToken = $rs2['refresh_token'];

                /**
                 * Try to get the info from the end-user
                 */
                if (!$this->getEndUserInfo()) {
                    return false;
                }

                /**
                 * Mark the authentication as being completed
                 */
                $this->authenticated = true;

                /**
                 * Store state of this object in the session
                 */
                $_SESSION[get_class($this)] = get_object_vars($this);

                return true;
            } elseif (!isset($_GET['code'])) {
                /**
                 * If no authorization code was obtained,
                 * return an error
                 */
                $this->error = array(
                    'line' => __LINE__,
                    'type' => 'object',
                    'error' => '',
                    'error_description' => 'No authorization code could be obtained'
                );
                return false;
            } else {
                /**
                 * If an unknown error has occurred,
                 * return an error
                 */
                $this->raiseUnknownError(__LINE__);
                return false;
            }
        } elseif ($this->authenticated) {
            /**
             * If the authentication was already completed,
             * try to get the info from the end-user
             */
            if (!$this->getEndUserInfo()) {
                return false;
            }

            return true;
        } else {
            /**
             * If an unknown error has occurred,
             * return an error
             */
            $this->raiseUnknownError(__LINE__);
            return false;
        }
    }

    /**
     * Method to authenticate the end-user
     *
     * @param string $scope The scope requested. Valid values are any combination of the following 'openid', 'profile', 'email', 'phone', 'address'
     * @param string $qoa The Quality of Authentication requested. Valid values are 'qoa1', 'qoa2'
     * @param string $qor The Quality of Registration requested. Valid values are 'qor0', 'qor1', 'qor2'
     * @param string $locale The language of the end-user interface. Valid values are 'de', 'fr', 'it', 'en'
     * @param string $state The state to pass
     * @param string $nonce The nonce to be used
     * @param string $loginHint The login hint
     * @param string $prompt Whether and for what the IdP should prompt the end-user. Valid values are 'none', 'login', 'consent'
     * @param int $maxAge The allowable elapsed time in seconds since the last time the end-user was actively authenticated. A valid value is an integer >= 0
     * @return void
     */
    public function authenticate(string $scope = 'openid', string $qoa = null, string $qor = null, string $locale = null, string $state = null, string $nonce = null, string $loginHint = null, string $prompt = null, int $maxAge = null): void
    {
        /**
         * Store parameters as class member variables
         */
        $this->scope = $scope;
        if (!is_null($qoa)) {
            $this->qoa = $qoa;
        } else {
            $qoa = (isset($this->qoa)) ? $this->qoa : null;
        }
        if (!is_null($qor)) {
            $this->qor = $qor;
        } else {
            $qor = (isset($this->qor)) ? $this->qor : null;
        }
        if (!is_null($locale)) {
            $this->locale = $locale;
        } else {
            $locale = (isset($this->locale)) ? $this->locale : null;
        }
        if (!is_null($state)) {
            $this->state = $state;
        } else {
            $state = (isset($this->state)) ? $this->state : null;
        }
        if (!is_null($nonce)) {
            $this->nonce = $nonce;
        } else {
            $nonce = (isset($this->nonce)) ? $this->nonce : null;
        }
        if (!is_null($loginHint)) {
            $this->loginHint = $loginHint;
        } else {
            $loginHint = (isset($this->loginHint)) ? $this->loginHint : null;
        }
        if (!is_null($prompt)) {
            $this->prompt = $prompt;
        } else {
            $prompt = (isset($this->prompt)) ? $this->prompt : null;
        }
        if (!is_null($maxAge)) {
            $this->maxAge = $maxAge;
        } else {
            $maxAge = (isset($this->maxAge)) ? $this->maxAge : null;
        }

        /**
         * Verify parameters
         */
        if (is_null($rs = $this->verifyParameter('scope', $scope))) {
            return;
        } elseif (!$rs['valid']) {
            /**
             * If an invalid scope parameter has been specified,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => '',
                'error_description' => 'The scope parameter "' . $scope . '" is invalid. Valid values are ' . $rs['allowedValues']
            );
            return;
        } elseif (!is_null($qoa) && is_null($rs = $this->verifyParameter('qoa', $qoa))) {
            return;
        } elseif (!is_null($qoa) && !$rs['valid']) {
            /**
             * If an invalid qoa parameter has been specified,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => '',
                'error_description' => 'The qoa parameter is invalid. Valid values are ' . $rs['allowedValues']
            );
            return;
        } elseif (!is_null($qor) && is_null($rs = $this->verifyParameter('qor', $qor))) {
            return;
        } elseif (!is_null($qor) && !$rs['valid']) {
            /**
             * If an invalid qor parameter has been specified,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => '',
                'error_description' => 'The qor parameter is invalid. Valid values are ' . $rs['allowedValues']
            );
            return;
        } elseif (!is_null($locale) && is_null($rs = $this->verifyParameter('locale', $locale))) {
            return;
        } elseif (!is_null($locale) && !$rs['valid']) {
            /**
             * If an invalid locale parameter has been specified,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => '',
                'error_description' => 'The locale parameter is invalid. Valid values are ' . $rs['allowedValues']
            );
            return;
        } elseif (!is_null($loginHint) && is_null($rs = $this->verifyParameter('loginHint', $loginHint))) {
            return;
        } elseif (!is_null($loginHint) && !$rs['valid']) {
            /**
             * If an invalid locale parameter has been specified,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => '',
                'error_description' => 'The login hint parameter is invalid. Valid values are ' . $rs['allowedValues']
            );
            return;
        } elseif (!is_null($prompt) && is_null($rs = $this->verifyParameter('prompt', $prompt))) {
            return;
        } elseif (!is_null($prompt) && !$rs['valid']) {
            /**
             * If an invalid prompt parameter has been specified,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => '',
                'error_description' => 'The prompt parameter is invalid. Valid values are ' . $rs['allowedValues']
            );
            return;
        } elseif (!is_null($maxAge) && is_null($rs = $this->verifyParameter('maxAge', $maxAge))) {
            return;
        } elseif (!is_null($maxAge) && !$rs['valid']) {
            /**
             * If an invalid prompt parameter has been specified,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => '',
                'error_description' => 'The maxAge parameter is invalid. Valid values are ' . $rs['allowedValues']
            );
            return;
        }

        if (!$this->connectorInitialized) {
            /**
             * If this object was not correctly initialized,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => '',
                'error_description' => 'This object was not correctly initialized'
            );
            return;
        } elseif (!$this->authenticationInitialized) {
            /**
             * If this object was correctly initialized,
             * but the authentication was not initialized,
             * try to initialize the authentication
             */
            $claims = (is_null($qor)) ? null : '{"userinfo":{"urn:swissid:qor":{"value":"' . $qor . '"}}}';
            $params = array(
                'response_type' => 'code',
                'client_id' => $this->clientID,
                'redirect_uri' => $this->redirectURI,
                'nonce' => $nonce,
                'state' => $state,
                'ui_locales' => $locale,
                'scope' => $scope,
                'login_hint' => $loginHint,
                'prompt' => $prompt,
                'max_age' => $maxAge,
                'claims' => $claims,
                'acr_values' => $qoa
            );
            $params2 = array();
            foreach ($params as $key => $val) {
                if (!is_null($val)) {
                    $params2[$key] = $val;
                }
            }

            /**
             * Mark the authentication as being initialized
             */
            $this->authenticationInitialized = true;

            /**
             * Store state of this object in the session
             */
            $_SESSION[get_class($this)] = get_object_vars($this);

            /**
             * Redirect
             */
            $redirectLocation = $this->openidConfiguration['authorization_endpoint'] . '?' . http_build_query($params2);
            header('Location: ' . $redirectLocation);
            exit;
            return;
        } else {
            /**
             * Return if there is nothing to do
             */
            return;
        }
    }

    /**
     * Method to get the end-user info from the end-user info endpoint
     *
     * If an error has occurred, this method returns false, otherwise true
     *
     * @return bool
     */
    private function getEndUserInfo(): bool
    {
        if (!$this->connectorInitialized) {
            /**
             * If this object was not correctly initialized,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => '',
                'error_description' => 'This object was not correctly initialized'
            );
            return false;
        } elseif (!isset($this->accessToken)) {
            /**
             * If there is no access token,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => '',
                'error_description' => 'There is no access token available'
            );
            return false;
        }

        /**
         * If the access token has expired,
         * try to obtain a new access token
         */
        if (time() > $this->accessTokenExpirationTimestamp) {
            if (!$this->refreshAccessToken()) {
                /**
                 * Return an error if the access token could not be refreshed
                 */
                return false;
            }
        }

        /**
         * Use the access token to get the end user info from the user info endpoint
         */
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bearer ' . $this->accessToken));
        curl_setopt($ch, CURLOPT_URL, $this->openidConfiguration['userinfo_endpoint']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $rs = curl_exec($ch);
        $httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpStatus == 401 && !$this->automatedReauthenticationAttempted) {
            /**
             * If the attempt failed,
             * reset the class member variables
             * and re-trigger the authentication as a last attempt
             */
            $this->automatedReauthenticationAttempted = true;
            $this->authenticationInitialized = false;
            $this->authenticated = false;
            $this->stepUpInitialized = array(
                'qor1' => false
            );
            $this->steppedUp = array(
                'qor1' => false
            );
            $this->authenticate($this->scope, null, null, null, null, null, null, 'login');
        } elseif ($httpStatus != 200) {
            /**
             * If the http status was different from 200,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => null,
                'error_description' => 'Unexpected http status ' . $httpStatus . '. Maybe the access token has expired?'
            );
            return false;
        }

        $rs2 = explode('.', $rs);
        if (count($rs2) != 3) {
            /**
             * If the http status was different from 200,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => null,
                'error_description' => 'Unexpected output from request to userinfo endpoint'
            );
            return false;
        } elseif (!$rs3 = json_decode(base64_decode($rs2[0]), $associativeP = true)) {
            /**
             * If an error has occurred while trying to decode the JSON response,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => json_last_error(),
                'error_description' => json_last_error_msg()
            );
            return false;
        } else {
            /**
             * Try to decode the token, based on the applicable algorithm
             */
            try {
                $alg = $rs3['alg'];
                if ($alg == 'HS256') {
                    $decodedIDToken = JWT::decode($rs, $this->clientSecret, array('HS256'));
                } elseif ($alg == 'RS256') {
                    $decodedIDToken = JWT::decode($rs, JWK::parseKeySet($this->keys), array('RS256'));
                }

                /**
                 * On success, update the class member variables
                 */
                $this->endUserInfo = $decodedIDToken;
                return true;
            } catch (Exception $e) {
                /**
                 * If an error has occurred,
                 * return an error
                 */
                $this->error = array(
                    'line' => __LINE__,
                    'type' => 'object',
                    'error' => '',
                    'error_description' => $e->getMessage()
                );
                return false;
            }
        }

        /**
         * If an unknown error has occurred,
         * return an error
         */
        $this->error = array(
            'line' => __LINE__,
            'type' => 'object',
            'error' => '',
            'error_description' => 'An unknown has occured whilte trying to initialize this object'
        );
        return false;
    }

    /**
     * Method to get a token
     *
     * If an error has occurred, this method returns null, otherwise a string:
     *
     * @param string $tokenType The type of token to get. Valid values are 'ACCESS', 'REFRESH'
     * @return string
     */
    public function getToken(string $tokenType): ?string
    {
        /**
         * Verify parameter
         */
        if (is_null($rs = $this->verifyParameter('tokenType', $tokenType))) {
            return null;
        } elseif (!$rs['valid']) {
            /**
             * If an invalid scope parameter has been specified,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => '',
                'error_description' => 'The scope parameter "' . $this->scope . '" is invalid. Valid values are ' . $rs['allowedValues']
            );
            return null;
        }

        if ($tokenType == 'ACCESS') {
            return $this->accessToken;
        } elseif ($tokenType == 'REFRESH') {
            return $this->refreshToken;
        }
    }

    /**
     * Method to retrieve a claim for the end-user
     *
     * If no error has occurred, this method returns null, otherwise an array:
     *
     * - claim, the requested claim
     * - value, the value for the claim, null if not available
     *
     * @param string $claim The claim to retrieve
     * @return string|null
     */
    public function getClaim(string $claim): ?array
    {
        if (!$this->connectorInitialized) {
            /**
             * If this object was not correctly initialized,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => '',
                'error_description' => 'This object was not correctly initialized'
            );
            return null;
        } elseif (!isset($this->endUserInfo)) {
            /**
             * If there is no end-user info
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => '',
                'error_description' => 'There is no end-user info available'
            );
            return null;
        } elseif (!property_exists($this->endUserInfo, $claim)) {
            /**
             * If non-existing, return null value
             */
            return array(
                'claim' => $claim,
                'value' => null
            );
        } elseif (property_exists($this->endUserInfo, $claim)) {
            /**
             * On success, return the claim
             */
            return array(
                'claim' => $claim,
                'value' => $this->endUserInfo->$claim
            );
        }

        /**
         * If an unknown error has occurred,
         * return an error
         */
        $this->error = array(
            'line' => __LINE__,
            'type' => 'object',
            'error' => '',
            'error_description' => 'An unknown has occured whilte trying to initialize this object'
        );
        return null;
    }

    /**
     * Method to initiate the step-up of the end-user's Quality of Registration
     *
     * By default, most parameters are assumed to be the same as at the time of authentication,
     * but if needed, they can be changed for calling this specific method.
     *
     * @param string $targetQoR The target Quality of Registration. Valid values are 'qor1'
     * @param string $nonce The nonce to be used
     * @param string $state The state to pass
     * @param string $scope The scope requested, if different from at the time of authentication. Valid values are any combination of the following 'openid', 'profile', 'email', 'phone', 'address'
     * @param string $qoa The Quality of Authentication requested, if different from at the time of authentication. Valid values are 'qoa1', 'qoa2'
     * @param string $qor The Quality of Registration requested, if different from at the time of authentication. Valid values are 'qor0', 'qor1', 'qor2'
     * @param string $locale The language of the end-user interface, if different from at the time of authentication. Valid values are 'de', 'fr', 'it', 'en'
     * @param string $loginHint The login hint, if different from at the time of authentication.
     * @param string $prompt Whether and for what the IdP should prompt the end-user, if different from at the time of authentication. Valid values are 'none', 'login', 'consent'
     * @param int $maxAge The allowable elapsed time in seconds since the last time the end-user was actively authenticated, if different from at the time of authentication. A valid value is an integer >= 0
     */
    public function stepUpQoR(string $targetQoR, string $nonce = null, string $state = null, string $scope = null, string $qoa = null, string $qor = null, string $locale = null, string $loginHint = null, string $prompt = null, int $maxAge = null): void
    {
        /**
         * Store nonce as class member variable
         */
        if (!is_null($nonce)) {
            $this->nonce = $nonce;
        }

        /**
         * Verify parameters
         */
        if (is_null($rs = $this->verifyParameter('qor', $targetQoR))) {
            return;
        } elseif (!$rs['valid']) {
            /**
             * If an invalid target qor parameter has been specified,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => '',
                'error_description' => 'The target qor parameter is invalid. Valid values are ' . $rs['allowedValues']
            );
            return;
        } elseif (!is_null($scope) && is_null($rs = $this->verifyParameter('scope', $scope))) {
            return;
        } elseif (!is_null($scope) && !$rs['valid']) {
            /**
             * If an invalid scope parameter has been specified,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => '',
                'error_description' => 'The scope parameter "' . $scope . '" is invalid. Valid values are ' . $rs['allowedValues']
            );
            return;
        } elseif (!is_null($qoa) && is_null($rs = $this->verifyParameter('qoa', $qoa))) {
            return;
        } elseif (!is_null($qoa) && !$rs['valid']) {
            /**
             * If an invalid qoa parameter has been specified,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => '',
                'error_description' => 'The qoa parameter is invalid. Valid values are ' . $rs['allowedValues']
            );
            return;
        } elseif (!is_null($qor) && is_null($rs = $this->verifyParameter('qor', $qor))) {
            return;
        } elseif (!is_null($qor) && !$rs['valid']) {
            /**
             * If an invalid qor parameter has been specified,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => '',
                'error_description' => 'The qor parameter is invalid. Valid values are ' . $rs['allowedValues']
            );
            return;
        } elseif (!is_null($locale) && is_null($rs = $this->verifyParameter('locale', $locale))) {
            return;
        } elseif (!is_null($locale) && !$rs['valid']) {
            /**
             * If an invalid locale parameter has been specified,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => '',
                'error_description' => 'The locale parameter is invalid. Valid values are ' . $rs['allowedValues']
            );
            return;
        } elseif (!is_null($loginHint) && is_null($rs = $this->verifyParameter('loginHint', $loginHint))) {
            return;
        } elseif (!is_null($loginHint) && !$rs['valid']) {
            /**
             * If an invalid loginHint parameter has been specified,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => '',
                'error_description' => 'The login hint parameter is invalid. Valid values are ' . $rs['allowedValues']
            );
            return;
        } elseif (!is_null($prompt) && is_null($rs = $this->verifyParameter('prompt', $prompt))) {
            return;
        } elseif (!is_null($prompt) && !$rs['valid']) {
            /**
             * If an invalid prompt parameter has been specified,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => '',
                'error_description' => 'The prompt parameter is invalid. Valid values are ' . $rs['allowedValues']
            );
            return;
        } elseif (!is_null($maxAge) && is_null($rs = $this->verifyParameter('maxAge', $maxAge))) {
            return;
        } elseif (!is_null($maxAge) && !$rs['valid']) {
            /**
             * If an invalid prompt parameter has been specified,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => '',
                'error_description' => 'The maxAge parameter is invalid. Valid values are ' . $rs['allowedValues']
            );
            return;
        }

        /**
         * Determine which parameters to use
         */
        $scope = (is_null($scope) && isset($this->scope)) ? $this->scope : $scope;
        $qoa = (is_null($qoa) && isset($this->qoa)) ? $this->qoa : $qoa;
        $qor = (is_null($qor) && isset($this->qor)) ? $this->qor : $qor;
        $locale = (is_null($locale) && isset($this->locale)) ? $this->locale : $locale;
        $loginHint = (is_null($loginHint) && isset($this->loginHint)) ? $this->loginHint : $loginHint;
        $prompt = (is_null($prompt) && isset($this->prompt)) ? $this->prompt : $prompt;
        $maxAge = (is_null($maxAge) && isset($this->maxAge)) ? $this->maxAge : $maxAge;

        if (!$this->authenticated) {
            /**
             * If the end-user was not authenticated yet,
             * return an error
             */
            $this->error = array(
                'line' => __LINE__,
                'type' => 'object',
                'error' => '',
                'error_description' => 'This end-user was not authenticated'
            );
            return;
        } elseif (!$this->stepUpInitialized[$targetQoR]) {
            /**
             * If the end-user was authenticated,
             * but the step-up was not initialized,
             * try to initialize the QoR1 step-up
             */
            $claims = (is_null($qor)) ? null : '{"userinfo":{"urn:swissid:qor":{"value":"' . $qor . '"}}}';
            $params = array(
                'response_type' => 'code',
                'client_id' => $this->clientID,
                'redirect_uri' => $this->redirectURI,
                'nonce' => $nonce,
                'state' => $state,
                'ui_locales' => $locale,
                'scope' => $scope,
                'login_hint' => $loginHint,
                'prompt' => $prompt,
                'max_age' => $maxAge,
                'claims' => $claims,
                'acr_values' => $qoa
            );
            $params2 = array();
            foreach ($params as $key => $val) {
                if (!is_null($val)) {
                    $params2[$key] = $val;
                }
            }

            /**
             * Mark the step-up as being initialized
             */
            $this->stepUpInitialized[$targetQoR] = true;

            /**
             * Store state of this object in the session
             */
            $_SESSION[get_class($this)] = get_object_vars($this);

            /**
             * Redirect
             */
            $stepUpURI = ($this->environment == 'PROD') ? 'https://account.swissid.ch/idcheck/rp/stepup/lot1' : 'https://account.int.swissid.ch/idcheck/rp/stepup/lot1';
            $redirectLocation = $stepUpURI . '?' . http_build_query($params2);
            header('Location: ' . $redirectLocation);
            exit;
            return;
        } elseif (!$this->steppedUp[$qor]) {
            /**
             * If the end-user was authenticated,
             * and the step-up was initialized,
             * but the step-up was not completed,
             * try to complete the step-up
             */
            if (isset($_GET['error']) && isset($_GET['error_description'])) {
                /**
                 * If an error occurred while trying to complete the authentication,
                 * relay the error
                 */
                $this->error = array(
                    'line' => __LINE__,
                    'type' => 'swissid',
                    'error' => $_GET['error'],
                    'error_description' => $_GET['error_description']
                );
                return;
            } elseif (isset($_GET['code'])) {
                /**
                 * If an authorization code was obtained,
                 * try to redeem it at the token endpoint
                 */
                $this->authorizationCode = $_GET['code'];

                $params = array(
                    'grant_type' => 'authorization_code',
                    'code' => $this->authorizationCode,
                    'redirect_uri' => $this->redirectURI
                );
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_USERPWD, $this->clientID . ':' . $this->clientSecret);
                curl_setopt($ch, CURLOPT_URL, $this->openidConfiguration['token_endpoint']);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $rs = curl_exec($ch);
                $httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpStatus != 200) {
                    /**
                     * If the http status was different from 200,
                     * return an error
                     */
                    $this->error = array(
                        'line' => __LINE__,
                        'type' => 'object',
                        'error' => null,
                        'error_description' => 'Unexpected http status ' . $httpStatus
                    );
                    return;
                }

                if (!$rs2 = json_decode($rs, $associativeP = true)) {
                    /**
                     * If an error has occurred while trying to decode the JSON response,
                     * return an error
                     */
                    $this->error = array(
                        'line' => __LINE__,
                        'type' => 'object',
                        'error' => json_last_error(),
                        'error_description' => json_last_error_msg()
                    );
                    return;
                }

                /**
                 * Store the access and refresh token
                 */
                $this->accessToken = $rs2['access_token'];
                $this->accessTokenExpirationTimestamp = time() + (int)$rs2['expires_in'];
                $this->refreshToken = $rs2['refresh_token'];

                /**
                 * Try to get the info from the end-user
                 */
                if (!$this->getEndUserInfo()) {
                    return;
                }

                /**
                 * Mark the step-up as being completed
                 */
                $this->steppedUp[$qor] = true;

                /**
                 * Store state of this object in the session
                 */
                $_SESSION[get_class($this)] = get_object_vars($this);

                return;
            } else {
                /**
                 * If no authorization code was obtained,
                 * return an error
                 */
                $this->error = array(
                    'line' => __LINE__,
                    'type' => 'object',
                    'error' => '',
                    'error_description' => 'No authorization code could be obtained'
                );
                return;
            }
        } elseif ($this->steppedUp) {
            /**
             * If the step-up was already completed,
             * try to get the info from the end-user
             */
            if (!$this->getEndUserInfo()) {
                return;
            }

            return;
        }

        return;
    }

    /**
     * Method to determine whether this object has an error
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return isset($this->error);
    }

    /**
     * Method to get the error for this object
     *
     * If no error has occurred, this method returns null, otherwise an array:
     *
     * - line, the line number at which the error occurred
     * - type, the type of error, valid values are 'object', 'swissid'
     * - error, the error
     * - error_description, the error description
     *
     * @return array|null
     */
    public function getError(): ?array
    {
        return $this->error;
    }

    /**
     * Method to raise an unknown error
     *
     * @param int $line The line from which the method is called
     * @return void
     */
    private function raiseUnknownError(int $line): void
    {
        $this->error = array(
            'line' => $line,
            'type' => 'object',
            'error' => '',
            'error_description' => 'An unknown has occured'
        );
        return;
    }
}

?>