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
    /** @var string RP identifier */
    private $clientID;
    /** @var string RP secret */
    private $clientSecret;
    /** @var string Redirect-URL for calls */
    private $redirectURL;
    /** @var string INT|PROD */
    private $environment;
    /** @var SwissIDConnector connector */
    private $swissIDConnector;

    /**
     * SwissidRedirectModuleFrontController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        // get the configuration
        $configValues = $this->getConfigValues();
        // define the configuration values
        $this->clientID = $configValues['SWISSID_CLIENT_ID'];
        $this->clientSecret = $configValues['SWISSID_CLIENT_SECRET'];
        $this->redirectURL = $configValues['SWISSID_REDIRECT_URL'];
        $this->environment = 'INT';
        // instantiate the SwissIDConnector object with the RP-specific configuration
        /*
        $this->swissIDConnector = new SwissIDConnector($this->clientID, $this->clientSecret, $this->redirectURL, $this->environment);
        if ($this->swissIDConnector->hasError()) {
            // handle the object's error if instantiating the object failed
            $error = $this->swissIDConnector->getError();
            $this->showErrors($error);
        }
        */
    }

    public function display()
    {
        echo 'swissid redirect front controller';
    }

    /**
     * Defines {@link SwissIDConnector} and authenticates an end-user
     *
     * @throws PrestaShopException
     */
    private function authenticateUser()
    {
        $scope = 'openid profile';
        $qoa = null;
        $qor = 'qor1';
        // customer language
        $locale = (isset($this->context->customer)) ? Language::getIsoById($this->context->customer->id_lang) : 'en';
        $state2pass = null;

        try {
            $nonce = bin2hex(random_bytes(8));
        } catch (Exception $e) {
            Tools::displayError($e->getMessage());
        }

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
        }
    }

    /**
     * Determine if the end-user has the required Quality of Registration
     * and initiate a step-up if this is not the case
     */
    private function hasUserSufficientQOR()
    {
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
                            ;
                            break;
                        case 'redirect_uri_mismatch':
                            /**
                             * Handle the case in which the redirect URI was invalid
                             */
                            ;
                            break;
                        case 'general_error':
                            /**
                             * Handle the case of a general error
                             */
                            ;
                            break;
                        case 'manual_check_needed':
                            /**
                             * Handle the end-user who is subject to a manual verification
                             */
                            ;
                            break;
                        case 'cancelled_by_user':
                            /**
                             * Handle the end-user who cancelled the step-up
                             */
                            ;
                            break;
                        case 'access_denied':
                            /**
                             * Handle the end-user who didn't give consent
                             */
                            ;
                            break;
                    }
                } elseif ($error['type'] == 'object') {
                    /**
                     * Handle the object's error if authentication failed
                     */;
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

    private function showErrors(array $errors)
    {
        foreach ($errors as $error) {
            var_dump($error);
        }
    }
}