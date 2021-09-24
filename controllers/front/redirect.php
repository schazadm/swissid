<?php

use OSR\Swissid\Connector\SwissIDConnector;

require _PS_MODULE_DIR_ . 'swissid/vendor/autoload.php';

/**
 * Class SwissidRedirectModuleFrontController
 *
 * Handles the redirection of a SwissID customer process
 */
class SwissidRedirectModuleFrontController extends ModuleFrontController
{
    const SWISSID_DOMAIN = 'swissid.ch';

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
     * @throws PrestaShopException
     */
    public function __construct()
    {
        parent::__construct();

        if (!$this->checkConfigValues()) {
            $errorMessage = "SwissID: Parameters are insufficient. Check configuration values again.";
            if (_PS_MODE_DEV_) {
                $this->showError($errorMessage);
            } else {
                $this->redirectToReferer($errorMessage);
            }
        }
        // get the configuration
        $configValues = $this->getConfigValues();
        // define the configuration values
        $this->clientID = $configValues['SWISSID_CLIENT_ID'];
        $this->clientSecret = $configValues['SWISSID_CLIENT_SECRET'];
        $this->redirectURL = $configValues['SWISSID_REDIRECT_URL'];
        $this->environment = 'PRE';
    }

    /**
     * POST entry of the controller
     *
     * @throws PrestaShopException
     * @throws Exception
     */
    public function postProcess()
    {
        // TODO: handle error given by swissid
        if (Tools::getIsset('error')) {
            $this->processErrorResponse();
        }

        // TODO: retrieve received data
        if (Tools::getIsset('code')) {
            $this->processCodeResponse();
        }

        if (Tools::getIsset('action')) {
            switch (Tools::getValue('action')) {
                case 'login':
                    $this->authenticateUser();
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * @throws PrestaShopException
     */
    private function processCodeResponse()
    {
        $this->connectToSwissID();
        $this->swissIDConnector->completeAuthentication();
        $this->checkForConnectorError();

        $rs = $this->swissIDConnector->getClaim('email');
        $this->checkForConnectorError();

        if (!empty($rs)) {
            Tools::redirect($this->context->link->getModuleLink($this->module->name, 'authenticate', [
                'action' => 'login',
                'response' => $rs
            ], true));
        } else {
            $this->showError("Mail address is empty");
        }
    }

    /**
     * @throws Exception
     */
    private function processErrorResponse()
    {
        $error = Tools::getValue('error');
        switch ($error) {
            case 'authentication_cancelled':
                /**
                 * Handle the end-user who cancelled the authentication
                 */
                ;
                $this->setCookieErrorMessage(Tools::getValue('error_description'));
                break;
            case 'access_denied':
                /**
                 * Handle the end-user who didn't give consent
                 */
                ;
                $this->setCookieErrorMessage(Tools::getValue('error_description'));
                break;
            case 'interaction_required':
                /**
                 * Handle the end-user who didn't authenticate
                 */
                ;
                $this->setCookieErrorMessage(Tools::getValue('error_description'));
                break;
            case 'invalid_client_id':
                /**
                 * Handle the case in which the client_id was invalid
                 */
                $this->setCookieErrorMessage(Tools::getValue('error_description'));
                break;
            case 'redirect_uri_mismatch':
                /**
                 * Handle the case in which the redirect URI was invalid
                 */
                $this->setCookieErrorMessage(Tools::getValue('error_description'));
                break;
            case 'general_error':
                /**
                 * Handle the case of a general error
                 */
                $this->setCookieErrorMessage(Tools::getValue('error_description'));
                break;
            case 'manual_check_needed':
                /**
                 * Handle the end-user who is subject to a manual verification
                 */
                $this->setCookieErrorMessage(Tools::getValue('error_description'));
                break;
            case 'cancelled_by_user':
                /**
                 * Handle the end-user who cancelled the step-up
                 */
                $this->setCookieErrorMessage(Tools::getValue('error_description'));
                break;
        }
    }

    /**
     * Tries to instantiate a {@link SwissIDConnector} object
     * with the RP-specific configuration for further operations
     *
     * @throws PrestaShopException
     */
    private function connectToSwissID()
    {
        try {
            $this->swissIDConnector = new SwissIDConnector($this->clientID, $this->clientSecret, $this->redirectURL, $this->environment);
            $this->checkForConnectorError();
        } catch (Exception | PrestaShopException $e) {
            $this->showError($e->getMessage());
        }
    }

    /**
     * Tries to authenticate an end-user by the help of the {@link SwissIDConnector}
     *
     * @throws PrestaShopException
     * @throws Exception
     */
    private function authenticateUser()
    {
        try {
            $scope = 'openid profile email';
            $qoa = null;
            $qor = 'qor1';
            $locale = (isset($this->context->language->iso_code)) ? $this->context->language->iso_code : 'en';
            $state2pass = null;
            $nonce = bin2hex(random_bytes(8));
            $this->connectToSwissID();
            $this->swissIDConnector->authenticate($scope, $qoa, $qor, $locale, $state2pass, $nonce);
            $this->checkForConnectorError();
        } catch (Exception $e) {
            $this->showError($e->getMessage());
        }
    }

    /**
     * Determine if the end-user has the required Quality of Registration
     * and initiate a step-up if this is not the case
     *
     * @throws PrestaShopException
     */
    private function hasUserSufficientQOR()
    {
        try {
            $this->connectToSwissID();
            if (!is_null($rs = $this->swissIDConnector->getClaim('urn:swissid:qor')) && $rs['value'] == 'qor0') {
                // Guide the end-user with QoR0 into a step-up process to attain QoR1
                $nonce = bin2hex(random_bytes(8));
                $this->swissIDConnector->stepUpQoR('qor1', $nonce);
            }
            $this->checkForConnectorError();
        } catch (Exception $e) {
            $this->showError($e->getMessage());
        }
    }

    /**
     * Checks if the connector has error and tries to handle it
     *
     * @throws PrestaShopException
     */
    private function checkForConnectorError()
    {
        try {
            if ($this->swissIDConnector->hasError()) {
                $error = $this->swissIDConnector->getError();
                if ($error['type'] == 'swissid') {
                    switch ($error['error']) {
                        case 'authentication_cancelled':
                            /**
                             * Handle the end-user who cancelled the authentication
                             */
                            ;
                            break;
                        case 'interaction_required':
                            /**
                             * Handle the end-user who didn't authenticate
                             */
                            ;
                            break;
                        case 'invalid_client_id':
                            /**
                             * Handle the case in which the client_id was invalid
                             */
                            break;
                        case 'redirect_uri_mismatch':
                            /**
                             * Handle the case in which the redirect URI was invalid
                             */
                            break;
                        case 'general_error':
                            /**
                             * Handle the case of a general error
                             */
                            break;
                        case 'manual_check_needed':
                            /**
                             * Handle the end-user who is subject to a manual verification
                             */
                            break;
                        case 'cancelled_by_user':
                            /**
                             * Handle the end-user who cancelled the step-up
                             */
                            break;
                        case 'access_denied':
                            /**
                             * Handle the end-user who didn't give consent
                             */
                            break;
                    }
                } elseif ($error['type'] == 'object') {
                    /**
                     * Handle the object's error if authentication failed
                     */
                }

                // TODO: Change when debugging is done
                $this->showError(json_encode($error));
                // $this->setCookieErrorMessage($error);
                // or
                // $this->setCookieErrorMessage($this->module->l('An error occurred while trying to connect to SwissID.'));
            }
        } catch (Exception $e) {
            $this->showError($e->getMessage());
        }
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
        if (!isset($configValues['SWISSID_REDIRECT_URL']) || !$configValues['SWISSID_REDIRECT_URL'] || empty($configValues['SWISSID_REDIRECT_URL'])) {
            return false;
        } elseif (!isset($configValues['SWISSID_CLIENT_ID']) || !$configValues['SWISSID_CLIENT_ID'] || empty($configValues['SWISSID_CLIENT_ID'])) {
            return false;
        } elseif (!isset($configValues['SWISSID_CLIENT_SECRET']) || !$configValues['SWISSID_CLIENT_SECRET'] || empty($configValues['SWISSID_CLIENT_SECRET'])) {
            return false;
        }
        return true;
    }

    /**
     * Shows error if the PS Dev is on
     *
     * @param string $errors
     * @throws PrestaShopException
     */
    private function showError($errors)
    {
        if (_PS_MODE_DEV_) {
            if (is_array($errors)) {
                Tools::displayError(json_encode($errors));
            } else {
                Tools::displayError($errors);
            }
        }
    }

    /**
     * Sets a cookie error message
     *
     * @param $message
     * @throws Exception
     */
    private function setCookieErrorMessage($message)
    {
        if (is_array($message)) {
            $this->context->cookie->__set('redirect_error', json_encode($message));
        } else {
            $this->context->cookie->__set('redirect_error', $message);
        }
    }

    /**
     * Redirect to the page where request came from
     *
     * @param string $errorMessage
     * @throws PrestaShopException
     */
    private function redirectToReferer($errorMessage = null)
    {
        Tools::redirect(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : die(Tools::displayError($errorMessage)));
    }

    /**
     * Checks given URL and returns a domain else false
     *
     * @param $url
     * @return bool|mixed
     */
    private function getDomain($url)
    {
        $pieces = parse_url($url);
        $domain = isset($pieces['host']) ? $pieces['host'] : '';
        if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z.]{2,6})$/i', $domain, $regs)) {
            return $regs['domain'];
        }
        return false;
    }
}