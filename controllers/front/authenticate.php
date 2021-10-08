<?php

/**
 * Class SwissidAuthenticationModuleFrontController
 *
 * Handles the authentication of a SwissID customer
 */
class SwissidAuthenticateModuleFrontController extends ModuleFrontController
{
    const COOKIE_HTTP_REF_S = 'redirect_http_ref_s';
    const COOKIE_HTTP_REF_E = 'redirect_http_ref_e';

    /** @var bool If set to true, will be redirected to authentication page */
    public $auth = false;

    /**
     * POST entry of the controller
     * Switches accordingly by the given action parameter
     *
     * @throws Exception
     */
    public function postProcess()
    {
        // whenever errors are send from 'redirect'
        if (Tools::getIsset('error')) {
            if (Tools::getIsset('error_description')) {
                $this->errors[] = Tools::getValue('error_description');
            } else {
                $this->errors[] = $this->module->l('An error occurred while trying to handle your request. Please try again later.');
            }
            $this->redirectWithNotifications($this->getRedirectPage(true));
        }
        // whenever actions are coming from 'FrontOffice' or 'BackOffice'
        if (Tools::getIsset('action')) {
            $this->context->cookie->__set(self::COOKIE_HTTP_REF_S, Tools::getIsset('redirect_s') ? Tools::getValue('redirect_s') : $this->context->link->getBaseLink());
            $this->context->cookie->__set(self::COOKIE_HTTP_REF_E, Tools::getIsset('redirect_e') ? Tools::getValue('redirect_e') : $this->context->link->getBaseLink());
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
                case 'ageVerify':
                    $this->ageVerificationAction();
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
            // $rs = Tools::getValue('response');
            $rs = Tools::getValue('response')['response'];
            if (isset($rs['email']) && !empty($rs['email'])) {
                $ageOver = null;
                if (isset($rs['age_over']) && !empty($rs['age_over'])) {
                    $ageOver = $rs['age_over'];
                }
                // authenticate with the given mail address
                if (!$this->authenticateCustomer($rs['email'], $ageOver)) {
                    // if the authentication process failed set an error message as a cookie for the hook
                    $this->errors[] = $this->translator->trans('Authentication failed.', [], 'Shop.Notifications.Error');
                    $this->redirectWithNotifications($this->getRedirectPage());
                }
            }
        } else {
            Tools::redirect(
                $this->context->link->getModuleLink(
                    $this->module->name, 'redirect', [
                        'action' => 'login'
                    ]
                )
            );
        }
    }

    /**
     *
     */
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
                $this->redirectWithNotifications($this->getRedirectPage());
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
                $this->redirectWithNotifications($this->getRedirectPage());
            }
            $ageOver = 0;
            if (isset($rs['age_over']) && !empty($rs['age_over'])) {
                $ageOver = $rs['age_over'];
            }
            // add newly created customer to swissID table
            SwissidCustomer::addSwissidCustomer($customer->id, $ageOver);
            $this->info[] = $this->module->l('Your newly created local account was automatically linked to your SwissID account.');
            // redirect to my-account overview
            $this->success[] = $this->module->l('Registration with SwissID was successful.');
            $this->redirectWithNotifications($this->getRedirectPage());
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
            $this->success[] = $this->module->l('Successfully connected to your SwissID');
            $this->redirectWithNotifications($this->getRedirectPage());
        } else {
            $this->errors[] = $this->module->l('An error occurred while connecting your SwissID account to your local account. Please try again.');
            $this->redirectWithNotifications($this->getRedirectPage(true));
        }
    }

    /**
     * Tries to unlink a local account from a SwissID account
     *
     * @throws Exception
     */
    public function disconnectAction()
    {
        if ($this->disconnectCustomer()) {
            $this->success[] = $this->module->l('Successfully disconnected from your SwissID');
            $this->redirectWithNotifications($this->getRedirectPage());
        } else {
            $this->errors[] = $this->module->l('An error occurred while disconnecting your SwissID account from your local account. Please try again.');
            $this->redirectWithNotifications($this->getRedirectPage(true));
        }
    }

    /**
     * @todo implement function
     */
    public function ageVerificationAction()
    {
        if (Tools::getIsset('response')) {
            $rs = Tools::getValue('response')['response'];
            // TODO: do this only if local account exists else just verify -> add COOKIE_GUEST_VERIFY = true
            // TODO: based on the response check if already exists in the swissid table
            // if not add new entry with age over attribute
            // if exists then only alter age over attribute
        } else {
            Tools::redirect(
                $this->context->link->getModuleLink(
                    $this->module->name,
                    'redirect',
                    [
                        'action' => 'ageVerify'
                    ],
                    true
                )
            );
        }
    }

    /**
     * Tries to authenticate a customer with the given email address
     *
     * @param string $mail A valid email address
     *
     * @param null $ageOver
     * @return bool Returns true if customer exists and is authenticated
     */
    private function authenticateCustomer($mail, $ageOver = null)
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
                if ($ageOver != null) {
                    SwissidCustomer::addSwissidCustomer($customer->id, $ageOver);
                } else {
                    SwissidCustomer::addSwissidCustomer($customer->id);
                }
            }
            $this->updateCustomer($customer);
            $this->success[] = $this->module->l('Authentication with SwissID was successful.');
            $this->redirectWithNotifications($this->getRedirectPage());
        } catch (Exception $e) {
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
     *
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
     * Link customer in the swissID table
     *
     * @return bool
     */
    private function connectCustomer()
    {
        if (!isset($this->context->customer->id)) {
            return false;
        }
        return SwissidCustomer::addSwissidCustomer($this->context->customer->id);
    }

    /**
     * Remove customer from the swissID table
     *
     * @return bool
     */
    private function disconnectCustomer()
    {
        if (!isset($this->context->customer->id)) {
            return false;
        }
        return SwissidCustomer::removeSwissidCustomerByCustomerId($this->context->customer->id);
    }

    /**
     * @param bool $error
     *
     * @return string
     */
    private function getRedirectPage($error = false)
    {
        $r = $this->context->link->getBaseLink();
        if ($error) {
            if (!empty($this->context->cookie->__get(self::COOKIE_HTTP_REF_E))) {
                $r = $this->context->cookie->__get(self::COOKIE_HTTP_REF_E);
            }
        } else {
            if (!empty($this->context->cookie->__get(self::COOKIE_HTTP_REF_S))) {
                $r = $this->context->cookie->__get(self::COOKIE_HTTP_REF_S);
            }
        }
        return $r;
    }
}
