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

use OSR\Swissid\Connector\SwissIDConnector;

require _PS_MODULE_DIR_ . 'swissid/vendor/autoload.php';

/**
 * Class SwissidRedirectModuleFrontController
 *
 * Handles the redirection of a SwissID customer process
 */
class SwissidRedirectModuleFrontController extends ModuleFrontController
{
    const COOKIE_ACTION_TYPE = 'redirect_action_type';
    const FILE_NAME = 'redirect';

    /** @var bool If set to true, will be redirected to authentication page */
    public $auth = false;
    /** @var string RP identifier */
    private $clientID;
    /** @var string RP secret */
    private $clientSecret;
    /** @var string Redirect-URL for calls */
    private $redirectURL;
    /** @var string PRE|PROD */
    private $environment;
    /** @var SwissIDConnector connector */
    private $swissIDConnector;

    /**
     * SwissidRedirectModuleFrontController constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();
        // before the object is instantiated check essential values and abort if not available
        if (!$this->checkConfigValues()) {
            $errorMessage = "SwissID: Parameters are insufficient. Check configuration values again.";
            if (_PS_MODE_DEV_) {
                $this->showError($errorMessage);
            } else {
                $this->responseError();
            }
        }
        // get the configuration
        $configValues = $this->getConfigValues();
        // set the configuration values
        $this->clientID = $configValues['SWISSID_CLIENT_ID'];
        $this->clientSecret = $configValues['SWISSID_CLIENT_SECRET'];
        // if encrypt and decrypt is needed
        // $this->clientSecret = (new PhpEncryption(_NEW_COOKIE_KEY_))->decrypt($configValues['SWISSID_CLIENT_SECRET']);
        $this->redirectURL = $configValues['SWISSID_REDIRECT_URL'];
        // TODO: Change when environment changes
        $this->environment = 'PRE';
        // set-up the connection
        $this->connectToSwissID();
    }

    /**
     * POST entry of the controller
     *
     * @throws Exception
     */
    public function postProcess()
    {
        // whenever errors are send from 'swissID'
        if (Tools::getIsset('error')) {
            $this->processErrorResponse();
        }
        // whenever responses are send from 'swissID'
        if (Tools::getIsset('code')) {
            $this->processCodeResponse();
        }
        // whenever actions are coming from 'authenticate'
        if (Tools::getIsset('action')) {
            switch (Tools::getValue('action')) {
                case 'login':
                    $this->context->cookie->__set(self::COOKIE_ACTION_TYPE, 'login');
                    $this->requestUserAuthentication();
                    break;
                case 'register':
                    $this->context->cookie->__set(self::COOKIE_ACTION_TYPE, 'register');
                    $this->requestRegistrationInformation();
                    break;
                case 'ageVerify':
                    $this->context->cookie->__set(self::COOKIE_ACTION_TYPE, 'ageVerify');
                    $this->requestUserAuthentication();
                    break;
                case 'connect':
                    $this->context->cookie->__set(self::COOKIE_ACTION_TYPE, 'connect');
                    $this->requestUserAuthentication();
                    break;
                default:
                    $this->context->cookie->__set(self::COOKIE_ACTION_TYPE, 'none');
                    break;
            }
        }
        // when no of the above keys are found, redirect to base
        $this->responseError();
    }

    /**
     * Tries to instantiate a {@link SwissIDConnector} object
     * with the RP-specific configuration for further operations
     */
    private function connectToSwissID()
    {
        try {
            $this->swissIDConnector = new SwissIDConnector(
                $this->clientID,
                $this->clientSecret,
                $this->redirectURL,
                $this->environment
            );
        } catch (Exception $e) {
            $this->showError($e->getMessage());
        }
    }

    private function processCodeResponse()
    {
        $actionType = $this->context->cookie->__get(self::COOKIE_ACTION_TYPE);
        // switch based on cookie action type
        switch ($actionType) {
            case 'login':
            case 'connect':
                $this->loginAction();
                break;
            case 'register':
                $this->requestRegistrationInformation();
                break;
            case 'ageVerify':
                $this->ageVerifyAction();
                break;
        }
    }

    private function processErrorResponse()
    {
        $errorDesc = $this->module->l(
            'An error occurred while trying to handle your request.',
            self::FILE_NAME
        );
        switch (Tools::getValue('error')) {
            case 'authentication_cancelled':
                // Handle the end-user who cancelled the authentication
                $errorDesc = $this->module->l(
                    'The authentication was canceled.',
                    self::FILE_NAME
                );
                break;
            case 'access_denied':
                // Handle the end-user who didn't give consent
                $errorDesc = $this->module->l(
                    'To ensure optimal functionality, we need your consent.',
                    self::FILE_NAME
                );
                break;
            case 'interaction_required':
                // Handle the end-user who didn't authenticate
                $errorDesc = $this->translator->trans(
                    'Authentication failed.',
                    [],
                    'Shop.Notifications.Error',
                    self::FILE_NAME
                );
                break;
            case 'cancelled_by_user':
                // Handle the end-user who cancelled the step-up
                $errorDesc = $this->module->l(
                    'The step-up process was canceled.',
                    self::FILE_NAME
                );
                break;
            case 'invalid_client_id':
                // Handle the case in which the client_id was invalid
            case 'redirect_uri_mismatch':
                // Handle the case in which the redirect URI was invalid
            case 'general_error':
                // Handle the case of a general error
                $errorDesc = $this->module->l(
                    'An internal error occurred while trying to handle your request.',
                    self::FILE_NAME
                );
        }
        $this->responseError($errorDesc);
    }

    private function loginAction()
    {
        try {
            $this->swissIDConnector->completeAuthentication();
            // get the configuration
            $configValues = $this->getConfigValues();
            $rs = [
                'response' => [
                    'email' => '',
                    'age_over' => '',
                ]
            ];
            if ($configValues['SWISSID_AGE_VERIFICATION']) {
                $this->requestHasUserSufficientQOR();
                $rs['response']['age_over'] = $this->swissIDConnector->getClaim('urn:swissid:age_over')['value'];
            }
            $rs['response']['email'] = $this->swissIDConnector->getClaim('email')['value'];
            if (!empty($rs)) {
                $actionType = $this->context->cookie->__get(self::COOKIE_ACTION_TYPE);
                Tools::redirect(
                    $this->context->link->getModuleLink(
                        $this->module->name,
                        'authenticate',
                        [
                            'action' => $actionType,
                            'response' => $rs
                        ],
                        true
                    )
                );
            }
            $this->responseError();
        } catch (Exception $e) {
            $this->showError($e->getMessage());
        }
    }

    private function ageVerifyAction()
    {
        try {
            $this->swissIDConnector->completeAuthentication();
            $this->requestHasUserSufficientQOR();
            $rs = [
                'response' => [
                    'email' => '',
                    'birthday' => '',
                    'age_over' => '',
                ]
            ];
            $rs['response']['email'] = $this->swissIDConnector->getClaim('email')['value'];
            $rs['response']['birthday'] = $this->swissIDConnector->getClaim('urn:swissid:date_of_birth')['value'];
            $rs['response']['age_over'] = $this->swissIDConnector->getClaim('urn:swissid:age_over')['value'];
            if (!empty($rs)) {
                Tools::redirect(
                    $this->context->link->getModuleLink(
                        $this->module->name,
                        'authenticate',
                        [
                            'action' => 'ageVerify',
                            'response' => $rs
                        ],
                        true
                    )
                );
            }
            $this->responseError();
        } catch (Exception $e) {
            $this->showError($e->getMessage());
        }
    }

    /**
     * Tries to authenticate an end-user by the help of the {@link SwissIDConnector}
     *
     * @throws Exception
     */
    private function requestUserAuthentication()
    {
        try {
            $scope = 'openid profile email';
            $qoa = null;
            // get the configuration
            $configValues = $this->getConfigValues();
            $qor = ($configValues['SWISSID_AGE_VERIFICATION']) ? 'qor1' : 'qor0';
            $locale = (isset($this->context->language->iso_code)) ? $this->context->language->iso_code : 'en';
            $state2pass = null;
            $nonce = bin2hex(random_bytes(8));
            $this->swissIDConnector->authenticate($scope, $qoa, $qor, $locale, $state2pass, $nonce);
        } catch (Exception $e) {
            $this->showError($e->getMessage());
        }
    }

    private function requestRegistrationInformation()
    {
        try {
            // get the configuration
            $configValues = $this->getConfigValues();
            $rs = [
                'response' => [
                    'birthday' => '',
                    'age_over' => '',
                    'gender' => '',
                    'firstname' => '',
                    'lastname' => '',
                    'language' => '',
                    'email' => '',
                ]
            ];
            if ($configValues['SWISSID_AGE_VERIFICATION']) {
                // lot1 request
                $this->requestHasUserSufficientQOR();
                // qor1 -> if available
                $rs['response']['birthday'] = $this->swissIDConnector->getClaim('urn:swissid:date_of_birth')['value'];
                $rs['response']['age_over'] = $this->swissIDConnector->getClaim('urn:swissid:age_over')['value'];
            }
            // qor0
            $rs['response']['gender'] = $this->swissIDConnector->getClaim('gender')['value'];
            $rs['response']['firstname'] = $this->swissIDConnector->getClaim('given_name')['value'];
            $rs['response']['lastname'] = $this->swissIDConnector->getClaim('family_name')['value'];
            $rs['response']['language'] = $this->swissIDConnector->getClaim('language')['value'];
            $rs['response']['email'] = $this->swissIDConnector->getClaim('email')['value'];
            if (!empty($rs)) {
                Tools::redirect(
                    $this->context->link->getModuleLink(
                        $this->module->name,
                        'authenticate',
                        [
                            'action' => 'register',
                            'response' => $rs
                        ],
                        true
                    )
                );
            }
            $this->responseError();
        } catch (Exception $e) {
            $this->showError($e->getMessage());
        }
    }

    /**
     * Determine if the end-user has the required Quality of Registration
     * and initiate a step-up if this is not the case
     */
    private function requestHasUserSufficientQOR()
    {
        try {
            $rs = $this->swissIDConnector->getClaim('urn:swissid:qor');
            if (is_null($rs)) {
                return false;
            }
            if ($rs['value'] == 'qor1') {
                return true;
            }
            if ($rs['value'] == 'qor0') {
                // Guide the end-user with QoR0 into a step-up process to attain QoR1
                $this->swissIDConnector->stepUpQoR('qor1');
            }
        } catch (Exception $e) {
            $this->showError($e->getMessage());
        }
        return false;
    }

    /**
     * Retrieves the configuration values of this module
     *
     * @return array
     */
    private function getConfigValues()
    {
        return [
            'SWISSID_REDIRECT_URL' => Configuration::get('SWISSID_REDIRECT_URL'),
            'SWISSID_CLIENT_ID' => Configuration::get('SWISSID_CLIENT_ID'),
            'SWISSID_CLIENT_SECRET' => Configuration::get('SWISSID_CLIENT_SECRET'),
            'SWISSID_AGE_VERIFICATION' => Configuration::get('SWISSID_AGE_VERIFICATION'),
            'SWISSID_AGE_VERIFICATION_OPTIONAL' => Configuration::get('SWISSID_AGE_VERIFICATION_OPTIONAL'),
        ];
    }

    /**
     * Checks whether the required config values are sufficient
     *
     * @return bool
     */
    private function checkConfigValues()
    {
        $configValues = $this->getConfigValues();
        if (!isset($configValues['SWISSID_REDIRECT_URL'])
            || !$configValues['SWISSID_REDIRECT_URL']
            || empty($configValues['SWISSID_REDIRECT_URL'])
        ) {
            return false;
        } elseif (!isset($configValues['SWISSID_CLIENT_ID'])
            || !$configValues['SWISSID_CLIENT_ID']
            || empty($configValues['SWISSID_CLIENT_ID'])
        ) {
            return false;
        } elseif (!isset($configValues['SWISSID_CLIENT_SECRET'])
            || !$configValues['SWISSID_CLIENT_SECRET']
            || empty($configValues['SWISSID_CLIENT_SECRET'])
        ) {
            return false;
        }
        return true;
    }

    /**
     * Shows error if the PS Dev is on
     *
     * @param string $errors
     */
    private function showError($errors)
    {
        try {
            if (_PS_MODE_DEV_) {
                if (is_array($errors)) {
                    Tools::displayError(json_encode($errors));
                } else {
                    Tools::displayError($errors);
                }
            }
        } catch (Exception | PrestaShopException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * @param $errorMessage
     */
    private function responseError($errorMessage = null)
    {
        $e = [
            'error' => true,
            'error_description' => ($errorMessage != null) ? $errorMessage : null
        ];
        Tools::redirect($this->context->link->getModuleLink($this->module->name, 'authenticate', $e));
    }
}
