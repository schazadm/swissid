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
     */
    public function __construct()
    {
        parent::__construct();
        if (!$this->checkConfigValues()) {
            $errorMessage = "SwissID: Parameters are insufficient. Check configuration values again.";
            if (_PS_MODE_DEV_) {
                Tools::displayError($errorMessage);
            } else {
                // redirect to the page where request came from
                Tools::redirect(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : die(Tools::displayError($errorMessage)));
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
     * GET entry of the controller
     */
    public function initContent()
    {
        //TODO: redirect to wherever
    }

    /**
     * POST entry of the controller
     */
    public function postProcess()
    {
        // TODO: distinguish from which location the request was made
        // TODO: e.g. _SELF then this set of function and if swissid.ch an other set
        if (Tools::getIsset('action')) {
            switch (Tools::getValue('action')) {
                case 'login':
                    $this->authenticateUser();
                    break;
                default:
                    break;
            }
        }

        if (Tools::getIsset('error')) {
            $error = Tools::getValue('error');
            switch ($error) {
                case 'authentication_cancelled':
                    /**
                     * Handle the end-user who cancelled the authentication
                     */
                    ;
                    $this->setErrorMessage(Tools::getValue('error_description'));
                    break;
                case 'access_denied':
                    /**
                     * Handle the end-user who didn't give consent
                     */
                    ;
                    $this->setErrorMessage(Tools::getValue('error_description'));
                    break;
                case 'interaction_required':
                    /**
                     * Handle the end-user who didn't authenticate
                     */
                    ;
                    $this->setErrorMessage(Tools::getValue('error_description'));
                    break;
                case 'invalid_client_id':
                    /**
                     * Handle the case in which the client_id was invalid
                     */
                    $this->setErrorMessage(Tools::getValue('error_description'));
                    break;
                case 'redirect_uri_mismatch':
                    /**
                     * Handle the case in which the redirect URI was invalid
                     */
                    $this->setErrorMessage(Tools::getValue('error_description'));
                    break;
                case 'general_error':
                    /**
                     * Handle the case of a general error
                     */
                    $this->setErrorMessage(Tools::getValue('error_description'));
                    break;
                case 'manual_check_needed':
                    /**
                     * Handle the end-user who is subject to a manual verification
                     */
                    $this->setErrorMessage(Tools::getValue('error_description'));
                    break;
                case 'cancelled_by_user':
                    /**
                     * Handle the end-user who cancelled the step-up
                     */
                    $this->setErrorMessage(Tools::getValue('error_description'));
                    break;
            }
        }

        if (Tools::getIsset('code')) {
            $this->connectToSwissID();
            $this->swissIDConnector->completeAuthentication();
            if ($this->swissIDConnector->hasError()) {
                $this->setErrorMessage($this->swissIDConnector->getError());
            }

            if ($mail = $this->swissIDConnector->getClaim('email')) {
                Tools::redirect($this->context->link->getModuleLink($this->module->name, 'authenticate', [
                    'action' => 'login',
                    'mail' => $mail
                ], true));
            } else {
                Tools::redirect($this->context->link->getBaseLink());
            }
        }

        //TODO: retrieve received data

        $this->redirectToReferer();
    }

    private function connectToSwissID()
    {
        // instantiate the SwissIDConnector object with the RP-specific configuration
        try {
            $this->swissIDConnector = new SwissIDConnector($this->clientID, $this->clientSecret, $this->redirectURL, $this->environment);
            if ($this->swissIDConnector->hasError()) {
                $this->setErrorMessage($this->module->l('An error occurred while trying to connect to SwissID.'));
                // handle the object's error if instantiating the object failed
                $error = $this->swissIDConnector->getError();
                $this->showErrors($error);
            }
        } catch (Exception | PrestaShopException $e) {
            return;
        }
    }

    /**
     * Defines {@link SwissIDConnector} and authenticates an end-user
     */
    private function authenticateUser()
    {
        $scope = 'openid profile email';
        $qoa = null;
        $qor = 'qor1';
        // customer language
        $locale = (isset($this->context->language->iso_code)) ? $this->context->language->iso_code : 'en';
        $state2pass = null;

        try {
            $nonce = bin2hex(random_bytes(8));
        } catch (Exception $e) {
            Tools::displayError($e->getMessage());
        }

        $this->connectToSwissID();
        $this->swissIDConnector->authenticate($scope, $qoa, $qor, $locale, $state2pass, $nonce);
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
                    case 'access_denied':
                        /**
                         * Handle the end-user who didn't give consent
                         */
                        ;
                        break;
                    case 'interaction_required':
                        /**
                         * Handle the end-user who didn't authenticate
                         */
                        ;
                        break;
                }
            } elseif ($error['type'] == 'object') {
                /**
                 * Handle the object's error if authentication failed
                 */;
            }

            $this->setErrorMessage($error);
        }
    }

    /**
     * Determine if the end-user has the required Quality of Registration
     * and initiate a step-up if this is not the case
     */
    private function hasUserSufficientQOR()
    {
        $this->connectToSwissID();

        if (is_null($rs = $this->swissIDConnector->getClaim('urn:swissid:qor'))) {
            /**
             * Handle the object's error if getting the claim failed
             */
            $error = $this->swissIDConnector->getError();;
        } elseif ($rs['value'] == 'qor0') {
            /**
             * Guide the end-user with QoR0 into a step-up process to attain QoR1
             */
            try {
                $nonce = bin2hex(random_bytes(8));
            } catch (Exception $e) {
            }
            $this->swissIDConnector->stepUpQoR('qor1');
            if ($this->swissIDConnector->hasError()) {
                $error = $this->swissIDConnector->getError();
                if ($error['type'] == 'swissid') {
                    switch ($error['error']) {
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
            }
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

    private function showErrors(array $errors)
    {
        if (_PS_MODE_DEV_) {
            var_dump($errors);
        }
    }

    private function setErrorMessage($message)
    {
        if (is_array($message)) {
            $this->context->cookie->__set('redirect_error', json_encode($message));
        } else {
            $this->context->cookie->__set('redirect_error', $message);
        }
    }

    private function redirectToReferer()
    {
        // redirect to the page where request came from
        Tools::redirect(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : die(Tools::displayError()));
    }
}