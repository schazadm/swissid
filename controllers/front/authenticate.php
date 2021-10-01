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
    const COOKIE_WARNING = 'redirect_warning';
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
                    $this->context->cookie->__set(
                        self::COOKIE_ERROR,
                        $this->translator->trans('Authentication failed.', [], 'Shop.Notifications.Error')
                    );
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
     * @throws Exception
     */
    public function registerAction()
    {
        if (Tools::getIsset('response')) {
            $rs = Tools::getValue('response')['response'];
            // create customer object and fill fields
            $customer = new Customer();
            $customer->id_gender = ($rs['gender'] == 'female') ? 2 : 1;
            $customer->firstname = $rs['firstname'];
            $customer->lastname = $rs['lastname'];
            $id_lang = 0;
            foreach (Language::getLanguages() as $language) {
                if (strpos($rs['language'], $language['iso_code']) !== false) {
                    $id_lang = $language['id_lang'];
                }
            }
            $customer->id_lang = $id_lang;
            $customer->email = $rs['email'];
            // customer saver
            $customerPersist = new CustomerPersister(
                $this->context,
                $this->get('hashing'),
                $this->getTranslator(),
                Configuration::get('PS_GUEST_CHECKOUT_ENABLED')
            );
            if (!$customerPersist->save($customer, Tools::passwdGen())) {
                $this->context->cookie->__set(
                    self::COOKIE_ERROR,
                    $this->module->l(
                        'An error occurred while trying to handle your request. Please try again later.'
                    )
                );
                Tools::redirect($this->context->link->getPageLink('authenticate', true));
            }
            SwissidCustomer::addSwissidCustomer($customer->id);
            Tools::redirect($this->context->link->getPageLink('my-account', true));
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
                SwissidCustomer::addSwissidCustomer($customer->id);
            }
            $this->updateCustomer($customer);
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
        // TODO: request info in order to be able to create a customer

        // TODO: create
        // TODO: and loginCustomer(createdCustomer);
        $this->context->cookie->__set(
            self::COOKIE_INFO,
            'Mail: ' . Tools::getValue('mail') . " needs to be created."
        );
        // TODO: remove after debugging
        Tools::redirect(
            $this->context->link->getPageLink('my-account', true)
        );
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
