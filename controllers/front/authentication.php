<?php

/**
 * Class SwissidAuthenticationModuleFrontController
 *
 * Handles the authentication of a SwissID customer
 */
class SwissidAuthenticationModuleFrontController extends ModuleFrontController
{
    /**
     * First entry of the controller
     * This method switches accordingly by the given action parameter
     *
     * @return bool|void Either redirects to the customer main view or the request origin
     *
     * @throws PrestaShopException
     */
    public function display()
    {
        if ($action = Tools::getValue('action')) {
            switch ($action) {
                case 'login':
                    // TODO: retrieve email by Tools::getValue()
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
                    // TODO: connect
                    $this->context->cookie->__set('redirect_success', $this->module->l('Successfully connected to your SwissID'));
                    break;
                case 'disconnect':
                    // TODO: disconnect
                    $this->context->cookie->__set('redirect_success', $this->module->l('Successfully disconnected from your SwissID'));
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
                return false;
            }
            // create a customer object
            $customer = new Customer();
            // fill an instance of a customer
            $authentication = $customer->getByEmail(trim($mail));
            // check whether the customer is already linked in the swissid table
            if (SwissidCustomer::isCustomerLinkedById($authentication->id)) {
                // if linked -> login via the updateCustomer() function
                $this->context->updateCustomer($authentication);
            } else {
                // if not -> ask customer to login with existing account and link the account
                $infoMessage = $this->module->l('A customer account with the specified email address');
                $infoMessage .= ' (' . $mail . ') ';
                $infoMessage .= $this->module->l('already exists.') . ' ';
                $infoMessage .= $this->module->l('Please try to log in to your local account and then link your account to your SwissID account.');
                $this->context->cookie->__set('redirect_info', $this->module->l($infoMessage, 'SwissidAuthentication'));
            }
        } catch (Exception | PrestaShopException $e) {
            return false;
        }
        return true;
    }

    private function cookieMessages()
    {
        // $this->context->cookie->__set('redirect_error', $this->translator->trans('Authentication failed.', [], 'Shop.Notifications.Error'));
        // $this->context->cookie->__set('redirect_warning', $this->translator->trans('Authentication failed.', [], 'Shop.Notifications.Error'));
        // $this->context->cookie->__set('redirect_info', $this->translator->trans('Authentication failed.', [], 'Shop.Notifications.Error'));
    }
}