<?php

/**
 * Class SwissidAuthenticationModuleFrontController
 *
 * Handles the authentication of a SwissID customer
 */
class SwissidAuthenticateModuleFrontController extends ModuleFrontController
{
    /**
     * GET entry of the controller
     * This method switches accordingly by the given action parameter
     *
     * @return bool|void Either redirects to the customer main view or the request origin
     *
     * @throws PrestaShopException
     * @throws Exception
     */
    public function initContent()
    {
        if ($action = Tools::getValue('action')) {
            switch ($action) {
                case 'login':
                    // TODO: retrieve email by Tools::getValue()
                    // TODO: also check mail validity
                    $mail = 'osr.dev@outlook.com';
                    // authenticate with the given mail address
                    if (!$this->authenticateCustomer($mail)) {
                        // if the authentication process failed set an error message as a cookie for the hook
                        $this->context->cookie->__set('redirect_error', $this->translator->trans('Authentication failed.', [], 'Shop.Notifications.Error'));
                    }
                    break;
                case 'logout':
                    // TODO: might want to register 'actionCustomerLogoutBefore' hook and do something (like clean-up) when triggered
                    echo 'swissid logout call';
                    break;
                case 'connect':
                    if ($this->connectCustomer()) {
                        $this->context->cookie->__set('redirect_success', $this->module->l('Successfully connected to your SwissID'));
                    } else {
                        $this->context->cookie->__set('redirect_error', $this->module->l('An error occurred while connecting your SwissID account to your local account. Please try again.'));
                    }
                    break;
                case 'disconnect':
                    if ($this->disconnectCustomer()) {
                        $this->context->cookie->__set('redirect_success', $this->module->l('Successfully disconnected from your SwissID'));
                    } else {
                        $this->context->cookie->__set('redirect_error', $this->module->l('An error occurred while disconnecting your SwissID account from your local account. Please try again.'));
                    }
                    break;
                default:
                    break;
            }
        }

        // redirect to the page where request came from
        Tools::redirect(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : die(Tools::displayError()));

        return true;
    }

    /**
     * POST entry of the controller
     */
    public function postProcess()
    {
        parent::postProcess();
    }

    /**
     * Tries to authenticate a customer with the given email address
     *
     * @param string $mail A valid email address
     *
     * @return bool Returns true if customer exists and is authenticated
     */
    private function authenticateCustomer($mail)
    {
        try {
            // check whether a customer with the given email address already exists
            if (!Customer::customerExists($mail)) {
                // if the customer doesn't exist -> create
                // TODO: create
                $this->createCustomer();
                return false;
            }
            // create a customer object
            $customer = (new Customer())->getByEmail(trim($mail));
            // check whether the customer is already linked in the swissid table
            if (SwissidCustomer::isCustomerLinkedById($customer->id)) {
                // if linked -> login
                $this->loginCustomer($customer);
            } else {
                // if not -> ask customer to login with existing account and link the account
                $this->promptCustomer($mail);
            }
        } catch (Exception | PrestaShopException $e) {
            return false;
        }
        return true;
    }

    /**
     * Updates the customer in the context of the shop
     *
     * @param Customer $customer
     *
     * @return bool
     */
    private function loginCustomer(Customer $customer)
    {
        try {
            $this->context->updateCustomer($customer);
        } catch (Exception | PrestaShopException $e) {
            return false;
        }
        return true;
    }

    /**
     * Creates a {@link  Customer} based on the given arguments
     */
    private function createCustomer()
    {
        // TODO: create
        // TODO: and loginCustomer(createdCustomer);
    }

    /**
     * Prepares an information message for the customer and sets a cookie
     *
     * @param string $mail
     *
     * @return bool
     */
    private function promptCustomer(string $mail)
    {
        $infoMessage = $this->module->l('A customer account with the specified email address');
        $infoMessage .= ' (' . $mail . ') ';
        $infoMessage .= $this->module->l('already exists.') . ' ';
        $infoMessage .= $this->module->l('Please try to log in to your local account and then link your account to your SwissID account.');

        try {
            $this->context->cookie->__set('redirect_info', $this->module->l($infoMessage, 'SwissidAuthentication'));
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    private function connectCustomer()
    {
        if (!isset($this->context->customer)) {
            return false;
        }

        return SwissidCustomer::addSwissidCustomer($this->context->customer->id);
    }

    private function disconnectCustomer()
    {
        if (!isset($this->context->customer)) {
            return false;
        }

        return SwissidCustomer::removeSwissidCustomerByCustomerId($this->context->customer->id);
    }

    private function cookieMessages()
    {
        // $this->context->cookie->__set('redirect_error', $this->translator->trans('Authentication failed.', [], 'Shop.Notifications.Error'));
        // $this->context->cookie->__set('redirect_warning', $this->translator->trans('Authentication failed.', [], 'Shop.Notifications.Error'));
        // $this->context->cookie->__set('redirect_info', $this->translator->trans('Authentication failed.', [], 'Shop.Notifications.Error'));
    }
}