<?php
/**
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code.
 *
 * @author             Online Services Rieder GmbH
 * @copyright          Online Services Rieder GmbH
 * @license            Check at: https://www.os-rieder.ch/
 * @date:              22.10.2021
 * @version:           1.0.0
 * @name:              SwissID
 * @description        Provides the possibility for a customer to log in with his SwissID.
 * @website            https://www.os-rieder.ch/
 */

namespace OSR\Swissid\Connector;

use Exception;
use \Firebase\JWT\JWT;
use \Firebase\JWT\JWK;
use Tools;

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
     * The allowable elapsed time in seconds since the last time the end-user was actively authenticated,
     * requested at the time of authentication
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
     * ID Token
     *
     * @var string
     */
    private $idToken;

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
     * If a brand new instance needs to be created, all first four parameters must be
     * specified and the last three are optional.
     * If an existing instances needs to be restored from the session, all first four
     * parameters are optional and the last three are ignored.
     *
     * @param string $clientID The RP's client_id
     * @param string $clientSecret The RP's client secret
     * @param string $redirectURI The RP's redirect URI registered with SwissID
     * @param string $environment The environment for which to initialize this object. Valid values are 'PRE', 'PROD'
     * @throws Exception
     */
    public function __construct(
        $clientID = null,
        $clientSecret = null,
        $redirectURI = null,
        $environment = null
    ) {
        $requiredParametersSet = (!is_null($clientID)
            || !is_null($clientSecret)
            || !is_null($redirectURI)
            || !is_null($environment)
        );
        if (!isset($_SESSION)) {
            session_start();
        } elseif (!isset($_SESSION[get_class($this)]) && !$requiredParametersSet) {
            /**
             * If class members are not available from the session,
             * and too few parameters have been specified,
             * return an error
             */
            throw new Exception('Too few parameters have been specified');
        }
        if (isset($_SESSION[get_class($this)])) {
            $this->reconstructObjectBasedOnSession();
        } elseif (!isset($_SESSION[get_class($this)])) {
            /**
             * If class members are not available from the session,
             * and all parameters have been specified,
             * initialize the class member variables
             */
            $this->openidConfigurationEndpoints = [
                'PRE' => 'https://login.sandbox.pre.swissid.ch/idp/oauth2/.well-known/openid-configuration',
                'PROD' => 'https://login.swissid.ch/idp/oauth2/.well-known/openid-configuration'
            ];
            $this->connectorInitialized = false;
            $this->authenticationInitialized = false;
            $this->authenticated = false;
            $this->stepUpInitialized = [
                'qor1' => false
            ];
            $this->steppedUp = [
                'qor1' => false
            ];
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
                throw new Exception('The environment parameter is invalid. ' .
                    'Valid values are ' . $rs['allowedValues']);
            }
            if (!$openidConfigurationEncoded = Tools::file_get_contents(
                $this->openidConfigurationEndpoints[$environment]
            )) {
                /**
                 * If SwissID's OpenID configuration could not be read,
                 * return an error
                 */
                throw new Exception('An error has occurred while trying to read the openid configuration');
            } else {
                if (!$openidConfigurationDecoded = json_decode($openidConfigurationEncoded, true)) {
                    /**
                     * If an error has occurred while trying to decode the JSON response,
                     * return an error
                     */
                    throw new Exception(json_last_error_msg());
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
                    if (!$keysEncoded = Tools::file_get_contents($this->openidConfiguration['jwks_uri'])) {
                        /**
                         * If SwissID's OpenID configuration could not be read,
                         * return an error
                         */
                        throw new Exception('An error has occurred while trying to read the keys');
                    } else {
                        if (!$keysDecoded = json_decode($keysEncoded, true)) {
                            /**
                             * If an error has occurred while trying to decode the JSON response,
                             * return an error
                             */
                            throw new Exception(json_last_error_msg());
                        } else {
                            $this->keys = $keysDecoded;
                        }
                    }
                }
            }
        }
        return null;
    }

    /**
     * Method to obtain a new access token on the basis of an access token
     *
     * @return bool
     * @throws Exception
     */
    private function refreshAccessToken()
    {
        if (is_null($this->refreshToken)) {
            /**
             * If no refresh token is available,
             * return an error
             */
            throw new Exception('There is no refresh token available');
        }
        /**
         * Try to redeem the refresh token at the token endpoint
         */
        $params = [
            'scope' => $this->scope,
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientID,
            'refresh_token' => $this->refreshToken
        ];
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
            throw new Exception('Unexpected http status ' . $httpStatus . ' - ' . $rs);
        }
        if (!$rs2 = json_decode($rs, true)) {
            /**
             * If an error has occurred while trying to decode the JSON response,
             * return an error
             */
            throw new Exception(json_last_error_msg());
        }
        /**
         * Store the access and refresh token
         */
        $this->accessToken = $rs2['access_token'];
        $this->idToken = $rs2['id_token'];
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
     * - allowedValues, the allowed values for the parameter specified
     *
     * @param string $type The type of parameter to verify. Valid values are 'environment',
     * 'scope', 'qoa', 'qor', 'locale', 'login_hint', 'prompt', 'maxAge', 'tokenType'
     * @param string $value the value to verify for the parameter specified
     * @return array|null
     * @throws Exception
     */
    private function verifyParameter($type, $value)
    {
        if (!in_array($type, ['environment', 'scope', 'qoa', 'qor', 'locale',
            'login_hint', 'prompt', 'maxAge', 'tokenType'])) {
            /**
             * If an invalid type has been specified,
             * return an error
             */
            throw new Exception('The type parameter "' . $type . '" is invalid. ' .
                'Valid values are environment, scope, qoa, qor, locale, prompt, maxAge');
        }
        switch ($type) {
            case 'environment':
                $allowedValues = ['PRE', 'PROD'];
                return [
                    'valid' => in_array($value, $allowedValues),
                    'allowedValues' => implode(', ', $allowedValues)
                ];
            case 'scope':
                $allowedValues = ['openid', 'profile', 'email', 'phone', 'address'];
                $requestedScopes = explode(' ', $value);
                // $invalidParameter = false;
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
                        return [
                            'valid' => false,
                            'allowedValues' => implode(', ', $allowedValues)
                        ];
                    }
                }
                return [
                    'valid' => true,
                    'allowedValues' => implode(', ', $allowedValues)
                ];
            case 'qoa':
                $allowedValues = ['qoa1', 'qoa2'];
                return [
                    'valid' => in_array($value, $allowedValues),
                    'allowedValues' => implode(', ', $allowedValues)
                ];
            case 'qor':
                $allowedValues = ['qor0', 'qor1', 'qor2'];
                return [
                    'valid' => in_array($value, $allowedValues),
                    'allowedValues' => implode(', ', $allowedValues)
                ];
            case 'locale':
                $allowedValues = ['de', 'fr', 'it', 'en'];
                return [
                    'valid' => in_array($value, $allowedValues),
                    'allowedValues' => implode(', ', $allowedValues)
                ];
            case 'login_hint':
                return [
                    'valid' => filter_var($value, FILTER_VALIDATE_EMAIL),
                    'allowedValues' => 'a valid e-mail address'
                ];
            case 'prompt':
                $allowedValues = ['none', 'login', 'consent'];
                return [
                    'valid' => in_array($value, $allowedValues),
                    'allowedValues' => implode(', ', $allowedValues)
                ];
            case 'maxAge':
                return [
                    'valid' => (is_numeric($value) && $value >= 0),
                    'allowedValues' => 'a postive numeric value'
                ];
            case 'tokenType':
                $allowedValues = ['ACCESS', 'REFRESH'];
                return [
                    'valid' => in_array($value, $allowedValues),
                    'allowedValues' => implode(', ', $allowedValues)
                ];
            default:
                /**
                 * If an unknown error type of parameter was specified,
                 * return an error
                 */
                throw new Exception('An unknown type of parameter was specified. ' .
                    'Valid values are scope, qoa, qor, locale, prompt, maxAge');
        }
    }

    /**
     * Method to complete the authentication of the end-user
     *
     * If an error has occurred, this method returns false, otherwise true
     * @throws Exception
     */
    public function completeAuthentication()
    {
        if (!$this->connectorInitialized) {
            /**
             * If this object was not correctly initialized,
             * return an error
             */
            throw new Exception('This object was not correctly initialized');
        } elseif (!$this->authenticationInitialized) {
            /**
             * If this object was correctly initialized,
             * but the authentication was not initialized,
             */
            throw new Exception('The authentication was not initialized');
        } elseif (!$this->authenticated) {
            /**
             * If this object was correctly initialized,
             * and the authentication was initialized,
             * but the authentication was not completed,
             * try to complete the authentication
             */
            if (Tools::getIsset('error') && Tools::getIsset('error_description')) {
                /**
                 * If an error occurred while trying to complete the authentication,
                 * relay the error
                 */
                throw new Exception(Tools::getValue('error_description'));
            } elseif (Tools::getIsset('code')) {
                /**
                 * If an authorization code was obtained,
                 * try to redeem it at the token endpoint
                 */
                $this->authorizationCode = Tools::getValue('code');
                $params = [
                    'grant_type' => 'authorization_code',
                    'code' => $this->authorizationCode,
                    'redirect_uri' => $this->redirectURI
                ];
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
                    throw new Exception('Unexpected http status ' . $httpStatus);
                }
                if (!$rs2 = json_decode($rs, true)) {
                    /**
                     * If an error has occurred while trying to decode the JSON response,
                     * return an error
                     */
                    throw new Exception(json_last_error_msg());
                }
                /**
                 * Store the access and refresh token
                 */
                $this->accessToken = $rs2['access_token'];
                $this->idToken = $rs2['id_token'];
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
                $this->saveObjectParamsToSession();
                return true;
            } elseif (!Tools::getIsset('code')) {
                /**
                 * If no authorization code was obtained,
                 * return an error
                 */
                throw new Exception('No authorization code could be obtained');
            } else {
                /**
                 * If an unknown error has occurred,
                 * return an error
                 */
                $this->raiseUnknownError();
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
            $this->raiseUnknownError();
            return false;
        }
    }

    /**
     * Method to authenticate the end-user
     *
     * @param string $scope The scope requested. Valid values are any combination
     * of the following 'openid', 'profile', 'email', 'phone', 'address'
     * @param string $qoa The Quality of Authentication requested. Valid values are 'qoa1', 'qoa2'
     * @param string $qor The Quality of Registration requested. Valid values are 'qor0', 'qor1', 'qor2'
     * @param string $locale The language of the end-user interface. Valid values are 'de', 'fr', 'it', 'en'
     * @param string $state The state to pass
     * @param string $nonce The nonce to be used
     * @param string $loginHint The login hint
     * @param string $prompt Whether and for what the IdP should prompt the end-user.
     * Valid values are 'none', 'login', 'consent'
     * @param int $maxAge The allowable elapsed time in seconds since the last time the
     * end-user was actively authenticated. A valid value is an integer >= 0
     * @return void
     * @throws Exception
     */
    public function authenticate(
        $scope = 'openid',
        $qoa = null,
        $qor = null,
        $locale = null,
        $state = null,
        $nonce = null,
        $loginHint = null,
        $prompt = null,
        $maxAge = null
    ) {
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
            throw new Exception('The scope parameter "' . $scope . '" is invalid. Valid values are ' .
                $rs['allowedValues']);
        } elseif (!is_null($qoa) && is_null($rs = $this->verifyParameter('qoa', $qoa))) {
            return;
        } elseif (!is_null($qoa) && !$rs['valid']) {
            /**
             * If an invalid qoa parameter has been specified,
             * return an error
             */
            throw new Exception('The qoa parameter is invalid. Valid values are ' . $rs['allowedValues']);
        } elseif (!is_null($qor) && is_null($rs = $this->verifyParameter('qor', $qor))) {
            return;
        } elseif (!is_null($qor) && !$rs['valid']) {
            /**
             * If an invalid qor parameter has been specified,
             * return an error
             */
            throw new Exception('The qor parameter is invalid. Valid values are ' . $rs['allowedValues']);
        } elseif (!is_null($locale) && is_null($rs = $this->verifyParameter('locale', $locale))) {
            return;
        } elseif (!is_null($locale) && !$rs['valid']) {
            /**
             * If an invalid locale parameter has been specified,
             * return an error
             */
            throw new Exception('The locale parameter is invalid. Valid values are ' . $rs['allowedValues']);
        } elseif (!is_null($loginHint) && is_null($rs = $this->verifyParameter('loginHint', $loginHint))) {
            return;
        } elseif (!is_null($loginHint) && !$rs['valid']) {
            /**
             * If an invalid locale parameter has been specified,
             * return an error
             */
            throw new Exception('The login hint parameter is invalid. Valid values are ' .
                $rs['allowedValues']);
        } elseif (!is_null($prompt) && is_null($rs = $this->verifyParameter('prompt', $prompt))) {
            return;
        } elseif (!is_null($prompt) && !$rs['valid']) {
            /**
             * If an invalid prompt parameter has been specified,
             * return an error
             */
            throw new Exception('The prompt parameter is invalid. Valid values are ' . $rs['allowedValues']);
        } elseif (!is_null($maxAge) && is_null($rs = $this->verifyParameter('maxAge', $maxAge))) {
            return;
        } elseif (!is_null($maxAge) && !$rs['valid']) {
            /**
             * If an invalid prompt parameter has been specified,
             * return an error
             */
            throw new Exception('The maxAge parameter is invalid. Valid values are ' . $rs['allowedValues']);
        }
        if (!$this->connectorInitialized) {
            /**
             * If this object was not correctly initialized,
             * return an error
             */
            throw new Exception('This object was not correctly initialized');
        }
        /**
         * If this object was correctly initialized,
         * but the authentication was not initialized,
         * try to initialize the authentication
         */
        $claims = (is_null($qor)) ? null : '{"userinfo":{"urn:swissid:qor":{"value":"' . $qor . '"}}}';
        $params = [
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
        ];
        $params2 = [];
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
        $this->saveObjectParamsToSession();
        /**
         * Redirect
         */
        $redirectLocation = $this->openidConfiguration['authorization_endpoint'] . '?' . http_build_query($params2);
        Tools::redirect($redirectLocation);
    }

    /**
     * Method to get the end-user info from the end-user info endpoint
     *
     * If an error has occurred, this method returns false, otherwise true
     *
     * @return bool
     * @throws Exception
     */
    private function getEndUserInfo()
    {
        if (!$this->connectorInitialized) {
            /**
             * If this object was not correctly initialized,
             * return an error
             */
            throw new Exception('This object was not correctly initialized');
        } elseif (!isset($this->accessToken)) {
            /**
             * If there is no access token,
             * return an error
             */
            throw new Exception('There is no access token available');
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
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' .
            $this->accessToken]);
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
            $this->stepUpInitialized = [
                'qor1' => false
            ];
            $this->steppedUp = [
                'qor1' => false
            ];
            $this->authenticate(
                $this->scope,
                null,
                null,
                null,
                null,
                null,
                null,
                'login'
            );
        } elseif ($httpStatus != 200) {
            /**
             * If the http status was different from 200,
             * return an error
             */
            throw new Exception('Unexpected http status ' . $httpStatus .
                '. Maybe the access token has expired?');
        }
        $rs2 = explode('.', $rs);
        if (count($rs2) != 3) {
            /**
             * If the http status was different from 200,
             * return an error
             */
            throw new Exception('Unexpected output from request to userinfo endpoint');
        } elseif (!$rs3 = json_decode(base64_decode($rs2[0]), true)) {
            /**
             * If an error has occurred while trying to decode the JSON response,
             * return an error
             */
            throw new Exception(json_last_error_msg());
        } else {
            /**
             * Try to decode the token, based on the applicable algorithm
             */
            try {
                $alg = $rs3['alg'];
                if ($alg == 'HS256') {
                    $decodedIDToken = JWT::decode($rs, $this->clientSecret, ['HS256']);
                } elseif ($alg == 'RS256') {
                    $decodedIDToken = JWT::decode($rs, JWK::parseKeySet($this->keys), ['RS256']);
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
                throw new Exception($e->getMessage());
            }
        }
    }

    /**
     * Method to get a token
     *
     * If an error has occurred, this method returns null, otherwise a string:
     *
     * @param string $tokenType The type of token to get. Valid values are 'ACCESS', 'REFRESH'
     * @return string
     * @throws Exception
     */
    public function getToken($tokenType)
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
            throw new Exception('The scope parameter "' . $this->scope . '" is invalid. Valid values are ' .
                $rs['allowedValues']);
        }
        if ($tokenType == 'ACCESS') {
            return $this->accessToken;
        } elseif ($tokenType == 'REFRESH') {
            return $this->refreshToken;
        }
        return null;
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
     * @return array|null
     * @throws Exception
     */
    public function getClaim($claim)
    {
        if (!$this->connectorInitialized) {
            /**
             * If this object was not correctly initialized,
             * return an error
             */
            throw new Exception('This object was not correctly initialized');
        } elseif (!isset($this->endUserInfo)) {
            /**
             * If there is no end-user info
             * return an error
             */
            throw new Exception('There is no end-user info available');
        } elseif (!property_exists($this->endUserInfo, $claim)) {
            /**
             * If non-existing, return null value
             */
            return [
                'claim' => $claim,
                'value' => null
            ];
        } elseif (property_exists($this->endUserInfo, $claim)) {
            /**
             * On success, return the claim
             */
            return [
                'claim' => $claim,
                'value' => $this->endUserInfo->$claim
            ];
        }
        /**
         * If an unknown error has occurred,
         * return an error
         */
        throw new Exception('An unknown has occurred while trying to initialize this object');
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
     * @param string $scope The scope requested, if different from at the time of authentication.
     * Valid values are any combination of the following 'openid', 'profile', 'email', 'phone', 'address'
     * @param string $qoa The Quality of Authentication requested, if different from at the time of authentication.
     * Valid values are 'qoa1', 'qoa2'
     * @param string $qor The Quality of Registration requested, if different from at the time of authentication.
     * Valid values are 'qor0', 'qor1', 'qor2'
     * @param string $locale The language of the end-user interface, if different from at the time of authentication.
     * Valid values are 'de', 'fr', 'it', 'en'
     * @param string $loginHint The login hint, if different from at the time of authentication.
     * @param string $prompt Whether and for what the IdP should prompt the end-user,
     * if different from at the time of authentication. Valid values are 'none', 'login', 'consent'
     * @param int $maxAge The allowable elapsed time in seconds since the last time
     * the end-user was actively authenticated, if different from at the time of authentication.
     * A valid value is an integer >= 0
     * @throws Exception
     */
    public function stepUpQoR(
        $targetQoR,
        $nonce = null,
        $state = null,
        $scope = null,
        $qoa = null,
        $qor = null,
        $locale = null,
        $loginHint = null,
        $prompt = null,
        $maxAge = null
    ) {
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
            throw new Exception('The target qor parameter is invalid. Valid values are ' .
                $rs['allowedValues']);
        } elseif (!is_null($scope) && is_null($rs = $this->verifyParameter('scope', $scope))) {
            return;
        } elseif (!is_null($scope) && !$rs['valid']) {
            /**
             * If an invalid scope parameter has been specified,
             * return an error
             */
            throw new Exception('The scope parameter "' . $scope . '" is invalid. Valid values are ' .
                $rs['allowedValues']);
        } elseif (!is_null($qoa) && is_null($rs = $this->verifyParameter('qoa', $qoa))) {
            return;
        } elseif (!is_null($qoa) && !$rs['valid']) {
            /**
             * If an invalid qoa parameter has been specified,
             * return an error
             */
            throw new Exception('The qoa parameter is invalid. Valid values are ' . $rs['allowedValues']);
        } elseif (!is_null($qor) && is_null($rs = $this->verifyParameter('qor', $qor))) {
            return;
        } elseif (!is_null($qor) && !$rs['valid']) {
            /**
             * If an invalid qor parameter has been specified,
             * return an error
             */
            throw new Exception('The qor parameter is invalid. Valid values are ' . $rs['allowedValues']);
        } elseif (!is_null($locale) && is_null($rs = $this->verifyParameter('locale', $locale))) {
            return;
        } elseif (!is_null($locale) && !$rs['valid']) {
            /**
             * If an invalid locale parameter has been specified,
             * return an error
             */
            throw new Exception('The locale parameter is invalid. Valid values are ' . $rs['allowedValues']);
        } elseif (!is_null($loginHint) && is_null($rs = $this->verifyParameter('loginHint', $loginHint))) {
            return;
        } elseif (!is_null($loginHint) && !$rs['valid']) {
            /**
             * If an invalid loginHint parameter has been specified,
             * return an error
             */
            throw new Exception('The login hint parameter is invalid. Valid values are ' . $rs['allowedValues']);
        } elseif (!is_null($prompt) && is_null($rs = $this->verifyParameter('prompt', $prompt))) {
            return;
        } elseif (!is_null($prompt) && !$rs['valid']) {
            /**
             * If an invalid prompt parameter has been specified,
             * return an error
             */
            throw new Exception('The prompt parameter is invalid. Valid values are ' . $rs['allowedValues']);
        } elseif (!is_null($maxAge) && is_null($rs = $this->verifyParameter('maxAge', $maxAge))) {
            return;
        } elseif (!is_null($maxAge) && !$rs['valid']) {
            /**
             * If an invalid prompt parameter has been specified,
             * return an error
             */
            throw new Exception('The maxAge parameter is invalid. Valid values are ' . $rs['allowedValues']);
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
            throw new Exception('This end-user was not authenticated');
        } elseif (!$this->stepUpInitialized[$targetQoR]) {
            /**
             * If the end-user was authenticated,
             * but the step-up was not initialized,
             * try to initialize the QoR1 step-up
             */
            $claims = (is_null($qor)) ? null : '{"userinfo":{"urn:swissid:qor":{"value":"' . $qor . '"}}}';
            $params = [
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
            ];
            $params2 = [];
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
            $this->saveObjectParamsToSession();
            /**
             * Redirect
             */
            $stepUpURI = ($this->environment == 'PROD') ?
                'https://account.swissid.ch/idcheck/rp/stepup/lot1' :
                'https://account.sandbox.pre.swissid.ch/idcheck/rp/stepup/lot1';
            $redirectLocation = $stepUpURI . '?' . http_build_query($params2);
            Tools::redirect($redirectLocation);
        } elseif (!$this->steppedUp[$qor]) {
            /**
             * If the end-user was authenticated,
             * and the step-up was initialized,
             * but the step-up was not completed,
             * try to complete the step-up
             */
            if (Tools::getIsset('error') && Tools::getIsset('error_description')) {
                /**
                 * If an error occurred while trying to complete the authentication,
                 * relay the error
                 */
                throw new Exception(Tools::getValue('error_description'));
            } elseif (Tools::getIsset('code')) {
                /**
                 * If an authorization code was obtained,
                 * try to redeem it at the token endpoint
                 */
                $this->authorizationCode = Tools::getValue('code');
                $params = [
                    'grant_type' => 'authorization_code',
                    'code' => $this->authorizationCode,
                    'redirect_uri' => $this->redirectURI
                ];
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
                    throw new Exception('Unexpected http status ' . $httpStatus);
                }
                if (!$rs2 = json_decode($rs, true)) {
                    /**
                     * If an error has occurred while trying to decode the JSON response,
                     * return an error
                     */
                    throw new Exception(json_last_error_msg());
                }
                /**
                 * Store the access and refresh token
                 */
                $this->accessToken = $rs2['access_token'];
                $this->idToken = $rs2['id_token'];
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
                $this->saveObjectParamsToSession();
                return;
            } else {
                /**
                 * If no authorization code was obtained,
                 * return an error
                 */
                throw new Exception('No authorization code could be obtained');
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
    }

    /**
     * Requests a end session
     *
     * @return bool
     * @throws Exception
     */
    public function endSession()
    {
        if (!$this->connectorInitialized) {
            return true;
        }
        if (time() > $this->accessTokenExpirationTimestamp) {
            return true;
        }
        $params = [
            'id_token_hint' => $this->idToken,
            'redirect_uri' => $this->redirectURI
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERPWD, $this->clientID . ':' . $this->clientSecret);
        curl_setopt(
            $ch,
            CURLOPT_URL,
            $this->openidConfiguration['end_session_endpoint'] . '?' . http_build_query($params)
        );
        curl_exec($ch);
        curl_close($ch);
        // unset session variable
        unset($_SESSION[get_class($this)]);
        return true;
    }

    /**
     * Method to determine whether this object has an error
     *
     * @return bool
     */
    public function hasError()
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
    public function getError()
    {
        return $this->error;
    }

    /**
     * Method to raise an unknown error
     *
     * @return void
     * @throws Exception
     */
    private function raiseUnknownError()
    {
        throw new Exception('An unknown has occured');
    }

    /**
     * Store state of this object in the session as cookies
     *
     * @throws Exception
     */
    private function saveObjectParamsToSession()
    {
        try {
            $_SESSION[get_class($this)] = get_object_vars($this);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Reconstruct class member variables from the session, if not all parameters are set
     * and if data is available in the session
     *
     * @throws Exception
     */
    private function reconstructObjectBasedOnSession()
    {
        try {
            foreach ($_SESSION[get_class($this)] as $key => $val) {
                $this->$key = $val;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
