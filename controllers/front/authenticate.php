<?php

/**
 * Class SwissidAuthenticationModuleFrontController
 *
 * Handles the authentication of a SwissID customer
 */
class SwissidAuthenticateModuleFrontController extends ModuleFrontController
{
    /** @var bool If set to true, will be redirected to authentication page */
    public $auth = false;

    /**
     * POST entry of the controller
     * Switches accordingly by the given action parameter
     *
     * @throws PrestaShopException
     * @throws Exception
     */
    public function postProcess()
    {
        if ($action = Tools::getValue('action')) {
            switch ($action) {
                case 'login':
                    $this->loginAction();
                    break;
                case 'logout':
                    $this->logoutAction();
                    break;
                case 'create':
                    $this->createAction();
                    break;
                case 'connect':
                    $this->connectAction();
                    break;
                case 'disconnect':
                    $this->disconnectAction();
                    break;
                default:
                    $this->redirectToReferer();
                    break;
            }
        }
    }

    /**
     * Tries to authenticate a SwissID customer
     *
     * @throws Exception
     */
    public function loginAction()
    {
        if (Tools::getIsset('response')) {
            $rs = Tools::getValue('response');
            if (isset($rs['claim']) && $rs['claim'] == 'email') {
                // authenticate with the given mail address
                if (!$this->authenticateCustomer($rs['email'])) {
                    // if the authentication process failed set an error message as a cookie for the hook
                    $this->context->cookie->__set('redirect_error', $this->translator->trans('Authentication failed.', [], 'Shop.Notifications.Error'));
                }
            }
        } else {
            Tools::redirect($this->context->link->getModuleLink($this->module->name, 'redirect', ['action' => 'login'], true));
        }
    }

    public function createAction()
    {
        // TODO: create
    }

    public function logoutAction()
    {
        // TODO: might want to register 'actionCustomerLogoutBefore' hook and do something (like clean-up) when triggered
        echo 'swissid logout call';
    }

    /**
     * Tries to link a local account to a SwissID account
     *
     * @throws Exception
     */
    public function connectAction()
    {
        if ($this->connectCustomer()) {
            $this->context->cookie->__set('redirect_success', $this->module->l('Successfully connected to your SwissID'));
        } else {
            $this->context->cookie->__set('redirect_error', $this->module->l('An error occurred while connecting your SwissID account to your local account. Please try again.'));
        }
        $this->redirectToReferer();
    }

    /**
     * Tries to unlink a local account from a SwissID account
     *
     * @throws Exception
     */
    public function disconnectAction()
    {
        if ($this->disconnectCustomer()) {
            $this->context->cookie->__set('redirect_success', $this->module->l('Successfully disconnected from your SwissID'));
        } else {
            $this->context->cookie->__set('redirect_error', $this->module->l('An error occurred while disconnecting your SwissID account from your local account. Please try again.'));
        }
        $this->redirectToReferer();
    }

    /**
     * Tries to authenticate a customer with the given email address
     *
     * @param string $mail A valid email address
     * @return bool Returns true if customer exists and is authenticated
     */
    private function authenticateCustomer($mail)
    {
        try {
            // check whether a customer with the given email address already exists
            if (!Customer::customerExists($mail)) {
                // if the customer doesn't exist -> create
                return $this->createCustomer();
            }
            // create a customer object
            $customer = (new Customer())->getByEmail(trim($mail));
            // check whether the customer is already linked in the swissid table
            if (SwissidCustomer::isCustomerLinkedById($customer->id)) {
                // if linked -> login
                $this->updateCustomer($customer);
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
     * @return bool
     */
    private function updateCustomer(Customer $customer)
    {
        try {
            $this->context->updateCustomer($customer);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Creates a {@link  Customer} based on the given arguments
     *
     * @throws Exception
     */
    private function createCustomer()
    {
        // TODO: create
        // TODO: and loginCustomer(createdCustomer);
        $this->context->cookie->__set('redirect_info', 'Mail: ' . Tools::getValue('mail') . " needs to be created.");

        // TODO: remove after debugging
        Tools::redirect($this->context->link->getPageLink('my-account', true));

        return true;
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
        if (!isset($this->context->customer->id)) {
            return false;
        }

        return SwissidCustomer::addSwissidCustomer($this->context->customer->id);
    }

    private function disconnectCustomer()
    {
        if (!isset($this->context->customer->id)) {
            return false;
        }

        return SwissidCustomer::removeSwissidCustomerByCustomerId($this->context->customer->id);
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
}
