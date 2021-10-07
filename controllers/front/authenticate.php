<?php

/**
 * Class SwissidAuthenticationModuleFrontController
 *
 * Handles the authentication of a SwissID customer
 */
class SwissidAuthenticateModuleFrontController extends ModuleFrontController
{
    const COOKIE_HTTP_REF = 'redirect_http_ref';
    const COOKIE_ERROR = 'redirect_error';
    const COOKIE_INFO = 'redirect_info';
    const COOKIE_SUCCESS = 'redirect_success';

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
        if (Tools::getIsset('error')) {
            $this->context->cookie->__set(
                self::COOKIE_ERROR,
                $this->module->l(
                    'An error occurred while trying to handle your request. Please try again later.'
                )
            );
        }

        if (Tools::getIsset('action')) {
            switch (Tools::getValue('action')) {
                case 'login':
                    $this->loginAction();
                    break;
                case 'logout':
                    $this->logoutAction();
                    break;
                case 'register':
                    $this->registerAction();
                    break;
                case 'connect':
                    $this->connectAction();
                    break;
                case 'disconnect':
                    $this->disconnectAction();
                    break;
            }
        }

        $this->redirectToReferer();
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
                if (!$this->authenticateCustomer($rs['value'])) {
                    // if the authentication process failed set an error message as a cookie for the hook
                    $this->errors[] = $this->translator->trans('Authentication failed.', [], 'Shop.Notifications.Error');
                    $this->redirectWithNotifications($this->context->link->getPageLink('authenticate', true));
                }
            }
        } else {
            Tools::redirect(
                $this->context->link->getModuleLink(
                    $this->module->name,
                    'redirect',
                    [
                        'action' => 'login'
                    ],
                    true
                )
            );
        }
    }

    public function logoutAction()
    {
        // TODO: might want to register 'actionCustomerLogoutBefore' hook and do something (like clean-up) when triggered
        echo 'swissid logout call';
    }

    /**
     * Tries to register a new SwissID customer
     *
     * @throws Exception
     */
    public function registerAction()
    {
        if (Tools::getIsset('response')) {
            $rs = Tools::getValue('response')['response'];
            $customer = $this->createCustomer($rs);
            if ($customer == null) {
                $this->errors[] = $this->module->l('An error occurred while trying to handle your request. Please try again later.');
                $this->redirectWithNotifications($this->context->link->getPageLink('authenticate', true));
            }
            $customerPersist = new CustomerPersister(
                $this->context,
                $this->get('hashing'),
                $this->getTranslator(),
                Configuration::get('PS_GUEST_CHECKOUT_ENABLED')
            );
            // try to save the customer
            if (!$customerPersist->save($customer, Tools::passwdGen())) {
                $this->errors[] = $this->module->l('An error occurred while trying to handle your request. Please try again later.');
                $this->redirectWithNotifications($this->context->link->getPageLink('authenticate', true));
            }
            // add newly created customer to swissID table
            SwissidCustomer::addSwissidCustomer($customer->id);
            $this->info[] = $this->module->l('Your newly created local account was automatically linked to your SwissID account.');
            // redirect to my-account overview
            $this->success[] = $this->module->l('Registration with SwissID was successful.');
            $this->redirectWithNotifications($this->context->link->getPageLink('my-account', true));
        }
    }

    /**
     * Tries to link a local account to a SwissID account
     *
     * @throws Exception
     */
    public function connectAction()
    {
        if ($this->connectCustomer()) {
            $this->context->cookie->__set(
                self::COOKIE_SUCCESS,
                $this->module->l('Successfully connected to your SwissID')
            );
        } else {
            $this->context->cookie->__set(
                self::COOKIE_ERROR,
                $this->module->l('An error occurred while connecting your SwissID account to your local account. Please try again.')
            );
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
            $this->context->cookie->__set(
                self::COOKIE_SUCCESS,
                $this->module->l('Successfully disconnected from your SwissID')
            );
        } else {
            $this->context->cookie->__set(
                self::COOKIE_ERROR,
                $this->module->l('An error occurred while disconnecting your SwissID account from your local account. Please try again.')
            );
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
                // request data
                Tools::redirect(
                    $this->context->link->getModuleLink(
                        $this->module->name,
                        'redirect',
                        [
                            'action' => 'register'
                        ],
                        true
                    )
                );
            }
            // obtain the customer object
            $customer = (new Customer())->getByEmail($mail);
            // check whether the customer is not linked in the swissid table
            if (!SwissidCustomer::isCustomerLinkedById($customer->id)) {
                // link customer -> first time login with swissID
                $this->info[] = $this->module->l('Your local account was automatically linked to your SwissID account.');
                SwissidCustomer::addSwissidCustomer($customer->id);
            }
            $this->updateCustomer($customer);
            $this->success[] = $this->module->l('Authentication with SwissID was successful.');
            $this->redirectWithNotifications($this->context->link->getPageLink('my-account', true));
        } catch (Exception $e) {
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
    private function updateCustomer($customer)
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
     * @param array $data
     * @return Customer|null
     */
    private function createCustomer($data)
    {
        try {
            // create customer object and fill fields
            $customer = new Customer();
            $customer->id_gender = ($data['gender'] == 'female') ? 2 : 1;
            $customer->firstname = $data['firstname'];
            $customer->lastname = $data['lastname'];
            // default the first language that is set in the shop
            $id_lang = 1;
            foreach (Language::getLanguages() as $language) {
                if (strpos($data['language'], $language['iso_code']) !== false) {
                    $id_lang = $language['id_lang'];
                }
            }
            $customer->id_lang = $id_lang;
            $customer->email = $data['email'];
            return $customer;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Prepares an information message for the customer and sets a cookie
     *
     * @param string $mail
     * @return bool
     */
    private function promptCustomer($mail)
    {
        $infoMessage = $this->module->l('A customer account with the specified email address');
        $infoMessage .= ' (' . $mail . ') ';
        $infoMessage .= $this->module->l('already exists.') . ' ';
        $infoMessage .= $this->module->l('Please try to log in to your local account and then link your account to your SwissID account.');
        try {
            $this->context->cookie->__set(
                self::COOKIE_INFO,
                $this->module->l($infoMessage, 'SwissidAuthentication')
            );
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
     */
    private function redirectToReferer()
    {
        Tools::redirect(
            (!empty($this->context->cookie->__get(self::COOKIE_HTTP_REF)))
                ? $this->context->cookie->__get(self::COOKIE_HTTP_REF)
                : $this->context->link->getBaseLink()
        );
    }
}
