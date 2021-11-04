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

/**
 * Class SwissidAuthenticationModuleFrontController
 *
 * Handles the authentication of a SwissID customer
 */
class SwissidAuthenticateModuleFrontController extends ModuleFrontController
{
    const COOKIE_HTTP_REF_S = 'redirect_http_ref_s';
    const COOKIE_HTTP_REF_E = 'redirect_http_ref_e';
    const AGE_OVER_FLAG = '18';
    const FILE_NAME = 'authenticate';

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
                $this->errors[] = $this->module->l(
                    'An error occurred while trying to handle your request. Please try again later.',
                    self::FILE_NAME
                );
            }
            $this->redirectWithNotifications($this->getRedirectPage(true));
        }
        // whenever actions are coming from 'FrontOffice' or 'BackOffice'
        if (Tools::getIsset('action')) {
            $this->setRedirectLinks();
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
     * @throws Exception
     */
    private function setRedirectLinks()
    {
        if (Tools::getIsset('redirect_s')) {
            $this->context->cookie->__set(self::COOKIE_HTTP_REF_S, Tools::getValue('redirect_s'));
        }
        if (Tools::getIsset('redirect_e')) {
            $this->context->cookie->__set(self::COOKIE_HTTP_REF_E, Tools::getValue('redirect_e'));
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
            $rs = Tools::getValue('response')['response'];
            if (isset($rs['sub_id']) && isset($rs['email']) && !empty($rs['sub_id']) && !empty($rs['email'])) {
                $ageOver = null;
                if (isset($rs['age_over']) && !empty($rs['age_over'])) {
                    $ageOver = $this->isAgeOver($rs['age_over']);
                }
                // authenticate with the given mail address
                if (!$this->authenticateCustomer($rs['sub_id'], $rs['email'], $ageOver)) {
                    // if the authentication process failed set an error message as a cookie for the hook
                    $this->errors[] = $this->translator->trans(
                        'Authentication failed.',
                        [],
                        'Shop.Notifications.Error'
                    );
                    $this->redirectWithNotifications($this->getRedirectPage(true));
                }
            }
        } else {
            Tools::redirect(
                $this->context->link->getModuleLink(
                    $this->module->name,
                    'redirect',
                    [
                        'action' => 'login'
                    ]
                )
            );
        }
    }

    /**
     * Tries to logout a SwissID customer
     */
    public function logoutAction()
    {
        if (Tools::getIsset('response')) {
            $rs = Tools::getValue('response')['response'];
            if ($rs['code'] == 'success') {
                $this->redirectWithNotifications($this->getRedirectPage());
            } else {
                $this->errors[] = $this->module->l(
                    'An error occurred while trying to end the session between SwissID.',
                    self::FILE_NAME
                );
                $this->redirectWithNotifications($this->getRedirectPage(true));
            }
        } else {
            Tools::redirect(
                $this->context->link->getModuleLink(
                    $this->module->name,
                    'redirect',
                    [
                        'action' => 'logout'
                    ]
                )
            );
        }
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
                $this->errors[] = $this->module->l(
                    'An error occurred while trying to handle your request. Please try again later.',
                    self::FILE_NAME
                );
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
                $this->errors[] = $this->module->l(
                    'An error occurred while trying to handle your request. Please try again later.',
                    self::FILE_NAME
                );
                $this->redirectWithNotifications($this->getRedirectPage());
            }
            $ageOver = 0;
            if (isset($rs['age_over']) && !empty($rs['age_over'])) {
                $ageOver = $this->isAgeOver($rs['age_over']);
            }
            // add newly created customer to swissID table
            SwissidCustomer::addSwissidCustomer($customer->id, $rs['sub_id'], $ageOver);
            $this->info[] = $this->module->l(
                'Your newly created local account was automatically linked to your SwissID account.',
                self::FILE_NAME
            );
            // redirect to my-account overview
            $this->success[] = $this->module->l(
                'Registration with SwissID was successful.',
                self::FILE_NAME
            );
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
        // a logged in customer is required
        if (!isset($this->context->customer->id)) {
            $this->errors[] = $this->module->l(
                'An error occurred while trying to handle your request.',
                self::FILE_NAME
            );
            $this->redirectWithNotifications($this->getRedirectPage(true));
        }
        if (Tools::getIsset('response')) {
            // get response
            $rs = Tools::getValue('response')['response'];
            // check if email is set in the response and is not empty
            if (isset($rs['sub_id']) && !empty($rs['sub_id'])) {
                // check whether sub id exists
                if (SwissidCustomer::doesSubIdExists($rs['sub_id'])) {
                    // sub id is already given to another customer -> duplicate entries are forbidden
                    $this->errors[] = $this->module->l(
                        'A customer is already assigned to the specific SwissID.',
                        self::FILE_NAME
                    );
                    $this->redirectWithNotifications($this->getRedirectPage(true));
                }
                $ageOver = 0;
                // if age over is also given set it else just default 0
                if (isset($rs['age_over']) && !empty($rs['age_over'])) {
                    $ageOver = $this->isAgeOver($rs['age_over']);
                }
                // try to add the SwissID customer entry
                if (SwissidCustomer::addSwissidCustomer($this->context->customer->id, $rs['sub_id'], $ageOver)) {
                    $this->success[] = $this->module->l(
                        'Successfully connected to your SwissID',
                        self::FILE_NAME
                    );
                    $this->redirectWithNotifications($this->getRedirectPage());
                }
            } else {
                $this->errors[] = $this->module->l(
                    'An error occurred while connecting your SwissID account to your local account.',
                    self::FILE_NAME
                );
            }
            $this->redirectWithNotifications($this->getRedirectPage(true));
        } else {
            Tools::redirect(
                $this->context->link->getModuleLink(
                    $this->module->name,
                    'redirect',
                    [
                        'action' => 'connect'
                    ]
                )
            );
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
            $this->success[] = $this->module->l(
                'Successfully disconnected from your SwissID',
                self::FILE_NAME
            );
            $this->redirectWithNotifications($this->getRedirectPage());
        } else {
            $this->errors[] = $this->module->l(
                'An error occurred while disconnecting your SwissID account from your local account. ' .
                'Please try again.',
                self::FILE_NAME
            );
            $this->redirectWithNotifications($this->getRedirectPage(true));
        }
    }

    /**
     *
     */
    public function ageVerificationAction()
    {
        if (!isset($this->context->customer->id)) {
            $this->errors[] = $this->module->l(
                'An error occurred while trying to handle your request.',
                self::FILE_NAME
            );
            $this->redirectWithNotifications($this->getRedirectPage(true));
        }
        if (Tools::getIsset('response')) {
            $rs = Tools::getValue('response')['response'];
            // check if email is set in the response and is not empty
            if (isset($rs['sub_id']) && !empty($rs['sub_id'])) {
                // check the age over flag
                if (isset($rs['age_over'])) {
                    $ageOver = $this->isAgeOver($rs['age_over']);
                    // check whether the customer is already linked in the swissid table
                    if (SwissidCustomer::isCustomerLinkedById($this->context->customer->id)) {
                        // compare sub_id's of table entry and the given one
                        $subIdOfCustomer = SwissidCustomer::getSubIdByCustomerId($this->context->customer->id);
                        if ($rs['sub_id'] !== $subIdOfCustomer) {
                            $this->errors[] = $this->module->l(
                                'The SwissID account is different from the registered one.',
                                self::FILE_NAME
                            );
                            $this->redirectWithNotifications($this->getRedirectPage(true));
                        }
                        // try to update the existing entry
                        if (!SwissidCustomer::updateCustomerAgeOver($this->context->customer->id, $ageOver)) {
                            $this->errors[] = $this->module->l(
                                'An error occurred while processing the age verification.',
                                self::FILE_NAME
                            );
                            $this->redirectWithNotifications($this->getRedirectPage(true));
                        }
                    } else {
                        // check whether sub id exists
                        if (SwissidCustomer::doesSubIdExists($rs['sub_id'])) {
                            // sub id is already given to another customer -> duplicate entries are forbidden
                            $this->errors[] = $this->module->l(
                                'A customer is already assigned to the specific SwissID.',
                                self::FILE_NAME
                            );
                            $this->redirectWithNotifications($this->getRedirectPage(true));
                        }
                        // try to add the SwissID customer entry
                        if (!SwissidCustomer::addSwissidCustomer($this->context->customer->id, $rs['sub_id'], $ageOver)) {
                            $this->errors[] = $this->module->l(
                                'An error occurred while processing the age verification.',
                                self::FILE_NAME
                            );
                            $this->redirectWithNotifications($this->getRedirectPage(true));
                        }
                        $this->info[] = $this->module->l(
                            'Your local account was also linked to your SwissID account. ' .
                            'You are now able to login with your SwissID.',
                            self::FILE_NAME
                        );
                    }
                    if (isset($rs['birthday']) && !empty($rs['birthday'])) {
                        // if the birthday is set -> save the birth date
                        $this->saveDateOfBirth($rs['birthday']);
                    }
                    $isAgeOver = ($ageOver == 1) ? true : false;
                    $this->success[] = $this->module->l(
                        'Your age has been verified.',
                        self::FILE_NAME
                    );
                    if (!$isAgeOver) {
                        $this->warning[] = $this->module->l(
                            'Unfortunately, you are not over 18 years old and you may not be able to ' .
                            'complete a checkout process because you are underage.',
                            self::FILE_NAME
                        );
                    }
                    $query = [
                        'isAgeOver' => $isAgeOver
                    ];
                    // return to the success redirection
                    $this->redirectWithNotifications($this->getRedirectPage() . '?' . http_build_query($query));
                } else {
                    $this->errors[] = $this->module->l(
                        'A technical error occurred while trying to request an age verification.',
                        self::FILE_NAME
                    );
                }
            } else {
                $this->errors[] = $this->module->l(
                    'An error occurred while verifying your age.',
                    self::FILE_NAME
                );
            }
            $this->redirectWithNotifications($this->getRedirectPage(true));
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
     * @param string $sub_id A valid sub identifier
     * @param string $mail A valid email address
     * @param null $ageOver
     * @return bool Returns true if customer exists and is authenticated
     */
    private function authenticateCustomer($sub_id, $mail, $ageOver = null)
    {
        try {
            // check whether sub id exists
            if (SwissidCustomer::doesSubIdExists($sub_id)) {
                // if exists get the customer id
                $customer_id = SwissidCustomer::getCustomerBySubId($sub_id);
                // get customer object by id
                $customer = new Customer((int)$customer_id);
                // update age over
                $this->updateAgeOver($customer->id, $ageOver);
                // log in the customer
                $this->updateCustomer($customer);
            } else {
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
                // check the automatic e-mail matching
                if (Configuration::get('SWISSID_EMAIL_MATCHING')) {
                    // check whether the customer is not linked in the swissid table
                    if (!SwissidCustomer::isCustomerLinkedById($customer->id)) {
                        // link customer -> first time login with swissID
                        $this->info[] = $this->module->l(
                            'Your local account was automatically linked to your SwissID account.',
                            self::FILE_NAME
                        );
                        if ($ageOver != null) {
                            SwissidCustomer::addSwissidCustomer($customer->id, $sub_id, $ageOver);
                        } else {
                            SwissidCustomer::addSwissidCustomer($customer->id, $sub_id);
                        }
                    } else {
                        $this->updateAgeOver($customer->id, $ageOver);
                    }
                    // log in the customer
                    $this->updateCustomer($customer);
                } else {
                    $this->info[] = $this->module->l(
                        'A local account with this e-mail address already exists. ' .
                        'Please authenticate with your local credentials and ' .
                        'connect your local account to your SwissID afterwards.',
                        self::FILE_NAME
                    );
                    $this->redirectWithNotifications($this->getRedirectPage(true));
                }
            }
        } catch (Exception $e) {
            return false;
        }
        return false;
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
            $this->success[] = $this->module->l(
                'Authentication with SwissID was successful.',
                self::FILE_NAME
            );
            $this->redirectWithNotifications($this->getRedirectPage());
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
            if ($data['gender'] == 'female') {
                $customer->id_gender = 2;
            } elseif ($data['gender'] == 'male') {
                $customer->id_gender = 1;
            }
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
            if (isset($data['birthday']) && !empty($data['birthday'])) {
                $customer->birthday = $data['birthday'];
            }
            return $customer;
        } catch (Exception $e) {
            return null;
        }
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
     * Returns the redirection page either the error redirection or success
     *
     * @param bool $error
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

    /**
     * Tries to save the given date of birth of the current customer
     *
     * @param $birthday
     * @return bool
     */
    private function saveDateOfBirth($birthday)
    {
        if (!isset($this->context->customer->id)) {
            return false;
        }
        if (isset($birthday) && !empty($birthday)) {
            $this->context->customer->birthday = $birthday;
            try {
                $this->context->customer->update();
            } catch (PrestaShopDatabaseException | PrestaShopException $e) {
                return false;
            }
        }
        return true;
    }

    /**
     * Tries to update the age over of the swissid customer
     *
     * @param $customerId
     * @param $ageOver
     * @return bool
     */
    private function updateAgeOver($customerId, $ageOver)
    {
        if ($ageOver != null) {
            return SwissidCustomer::updateCustomerAgeOver($customerId, $ageOver);
        }
        return false;
    }

    /**
     * Checks if the age over flag is satisfied
     * Only accept >=18 (>16 and <18 is not accepted)
     *
     * @param $ageOver
     * @return int
     */
    private function isAgeOver($ageOver)
    {
        if ($ageOver == self::AGE_OVER_FLAG) {
            return 1;
        }
        return 0;
    }
}
