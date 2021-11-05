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

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class Swissid
 *
 * Is the main class of the module.
 * Defines the main arguments and handles the redirection to the configuration page.
 * Handles the hook registrations and handles if the hooks are triggered.
 */
class Swissid extends Module
{
    const ADMIN_SWISSID_PARENT_CONTROLLER = 'AdminSwissidParent';
    const ADMIN_SWISSID_CONFIGURATION_CONTROLLER = 'AdminSwissidConfiguration';
    const ADMIN_SWISSID_CUSTOMER_CONTROLLER = 'AdminSwissidCustomer';
    const ADMIN_SWISSID_NON_CUSTOMER_CONTROLLER = 'AdminSwissidNonCustomer';
    const ADMIN_SWISSID_AGE_OVER_PRODUCT_CONTROLLER = 'AdminSwissidAgeOverProduct';

    public function __construct()
    {
        $this->name = 'swissid';
        $this->version = '1.0.0';
        $this->author = 'Online Services Rieder GmbH';
        $this->need_instance = 1;
        $this->tab = 'dashboard';
        parent::__construct();
        $this->displayName = $this->l('SwissID');
        $this->description = $this->l('Provides the possibility for a customer to log in with his SwissID. This allows customers to authenticate themselves securely and easily. In addition, this module enables age verification through the SwissID. This way you can introduce a general age restriction or an age restriction on certain articles.');
        $this->confirmUninstall = $this->l('If you uninstall this module, your customers will no longer be able to log in with their SwissID. Before you uninstall the module, make sure that you made a back-up of your database.');
        $this->ps_versions_compliancy = ['min' => '1.7.5.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        // install database tables
        include(dirname(__FILE__) . '/sql/install.php');
        if (!parent::install()) {
            return false;
        }
        // define hooks that needs to be registered
        $hooks = [
            'header',
            'displayHeader',
            'backOfficeHeader',
            'displayCustomerAccount',
            'displayCustomerAccountFormTop',
            'displayCustomerLoginFormAfter',
            'displayPersonalInformationTop',
            'actionObjectCustomerDeleteAfter',
            'actionObjectProductDeleteAfter',
            'actionCustomerLogoutAfter',
        ];
        // register hooks
        if (!$this->registerHook($hooks)) {
            return false;
        }
        // install configuration values
        if (!$this->installConfigValues()) {
            return false;
        }
        return true;
    }

    public function uninstall()
    {
        // remove installed database tables
        include(dirname(__FILE__) . '/sql/uninstall.php');
        if (!parent::uninstall()) {
            return false;
        }
        // remove configuration values
        if (!$this->uninstallConfigValues()) {
            return false;
        }
        return true;
    }

    /**
     * @throws PrestaShopException
     */
    public function getContent()
    {
        $route = $this->context->link->getAdminLink(static::ADMIN_SWISSID_CONFIGURATION_CONTROLLER);
        Tools::redirectAdmin($route);
    }

    /**
     * @return array
     */
    public function getTabs()
    {
        return [
            [
                'name' => $this->displayName,
                'parent_class_name' => 'AdminParentModulesSf',
                'class_name' => static::ADMIN_SWISSID_PARENT_CONTROLLER,
                'visible' => 'false'
            ],
            [
                'name' => $this->trans('Configuration', [], 'Admin.Global'),
                'parent_class_name' => static::ADMIN_SWISSID_PARENT_CONTROLLER,
                'class_name' => static::ADMIN_SWISSID_CONFIGURATION_CONTROLLER
            ],
            [
                'name' => 'SwissID ' . $this->trans('Customers', [], 'Admin.Global'),
                'parent_class_name' => static::ADMIN_SWISSID_PARENT_CONTROLLER,
                'class_name' => static::ADMIN_SWISSID_CUSTOMER_CONTROLLER
            ],
            [
                'name' => $this->l('Local') . ' ' . $this->trans('Customers', [], 'Admin.Global'),
                'parent_class_name' => static::ADMIN_SWISSID_PARENT_CONTROLLER,
                'class_name' => static::ADMIN_SWISSID_NON_CUSTOMER_CONTROLLER
            ],
            [
                'name' => $this->l('≥18 Products'),
                'parent_class_name' => static::ADMIN_SWISSID_PARENT_CONTROLLER,
                'class_name' => static::ADMIN_SWISSID_AGE_OVER_PRODUCT_CONTROLLER
            ],
        ];
    }

    /**
     * Defines and sets configuration values
     *
     * @return bool
     */
    private function installConfigValues()
    {
        try {
            // define the redirect URL which is used to authenticate the end-user
            Configuration::updateValue(
                'SWISSID_REDIRECT_URL',
                $this->context->link->getBaseLink() . 'module/' . $this->name . '/redirect'
            );
            Configuration::updateValue('SWISSID_CLIENT_ID', '');
            Configuration::updateValue('SWISSID_CLIENT_SECRET', '');
            Configuration::updateValue('SWISSID_AGE_VERIFICATION', '');
            Configuration::updateValue('SWISSID_AGE_VERIFICATION_TEXT', '');
            Configuration::updateValue('SWISSID_AGE_VERIFICATION_OPTIONAL', '');
            Configuration::updateValue('SWISSID_AGE_OVER_PRODUCT', '');
            Configuration::updateValue('SWISSID_EMAIL_MATCHING', '');
        } catch (Exception | PrestaShopException $e) {
            return false;
        }
        return true;
    }

    /**
     * Removes configuration values
     *
     * @return bool
     */
    private function uninstallConfigValues()
    {
        try {
            Configuration::deleteByName('SWISSID_REDIRECT_URL');
            Configuration::deleteByName('SWISSID_CLIENT_ID');
            Configuration::deleteByName('SWISSID_CLIENT_SECRET');
            Configuration::deleteByName('SWISSID_AGE_VERIFICATION');
            Configuration::deleteByName('SWISSID_AGE_VERIFICATION_TEXT');
            Configuration::deleteByName('SWISSID_AGE_VERIFICATION_OPTIONAL');
            Configuration::deleteByName('SWISSID_AGE_OVER_PRODUCT');
            Configuration::deleteByName('SWISSID_EMAIL_MATCHING');
        } catch (Exception | PrestaShopException $e) {
            return false;
        }
        return true;
    }

    /**
     * @return string|null
     * @throws Exception
     */
    public function hookDisplayHeader()
    {
        // add CSS and JS assets to the front context
        $this->context->controller->addJS($this->_path . '/views/js/swissid-front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/swissid-front.css');
        $this->context->controller->addCSS($this->_path . '/views/css/sesam-buttons.css');
        // check whether we're on the order page and age verification is on
        if (Tools::getValue('controller') == 'order'
            && Configuration::get('SWISSID_AGE_VERIFICATION')
        ) {
            // if there is no customer logged in then let them login first
            if (!isset($this->context->customer->id)) {
                return null;
            }
            // check whether a customer is logged in and is already verified
            if (isset($this->context->customer->id)
                && SwissidCustomer::isCustomerLinkedById($this->context->customer->id)
                && SwissidCustomer::isCustomerAgeOver($this->context->customer->id)
            ) {
                return null;
            }
            // check if age over for specific products is on
            if (Configuration::get('SWISSID_AGE_OVER_PRODUCT')) {
                // check cart if a product matches with swissid_age_over_product table
                $productsInCart = $this->context->cart->getProducts();
                foreach ($productsInCart as $product) {
                    if (SwissidAgeOverProduct::isProductInTable($product['id_product'])) {
                        return $this->getAgeVerifyModal();
                    }
                }
                return null;
            }
            // if age verification is not optional always return verify modal
            if (!Configuration::get('SWISSID_AGE_VERIFICATION_OPTIONAL')) {
                return $this->getAgeVerifyModal();
            }
            // if age verification is optional then try to determine if its first time ask or else don't show
            if (!isset($this->context->cookie->swissid_verify_asked)) {
                $this->context->cookie->__set('swissid_verify_asked', true);
                return $this->getAgeVerifyModal();
            }
            // if cookie is set and it's set to false then show
            if (isset($this->context->cookie->swissid_verify_asked)
                && !$this->context->cookie->swissid_verify_asked
            ) {
                $this->context->cookie->__set('swissid_verify_asked', true);
                return $this->getAgeVerifyModal();
            }
        }
        return null;
    }

    /**
     * @return string
     */
    private function getAgeVerifyModal()
    {
        $isAgeOver = null;
        if (Tools::getIsset('isAgeOver')) {
            $isAgeOver = Tools::getValue('isAgeOver');
        }
        return $this->fetch(
            $this->getLocalPath() . 'views/templates/hook/swissid-age-verification-modal.tpl',
            [
                'show' => true,
                'img_dir_url' => $this->_path . 'views/img',
                'age_verification' => Configuration::get('SWISSID_AGE_VERIFICATION'),
                'age_verification_optional' => Configuration::get('SWISSID_AGE_VERIFICATION_OPTIONAL'),
                'age_verification_url' => $this->context->link->getModuleLink($this->name, 'authenticate', [
                    'action' => 'ageVerify',
                    'redirect_s' => $this->context->link->getPageLink('order'),
                    'redirect_e' => $this->context->link->getPageLink('cart')
                ], true),
                'age_verification_text' => $this->getAgeVerificationText(),
                'isAgeOver' => $isAgeOver
            ]
        );
    }

    /**
     * hook is displayed on the page 'my-account'
     *
     * @return string
     */
    public function hookDisplayCustomerAccount()
    {
        $action = 'connect';
        $linked = false;
        $ageOver = false;
        if (isset($this->context->customer->id)) {
            if (SwissidCustomer::isCustomerLinkedById($this->context->customer->id)) {
                $action = 'disconnect';
                $linked = true;
            }
            if (SwissidCustomer::isCustomerAgeOver($this->context->customer->id)) {
                $ageOver = true;
            }
        }
        return $this->fetch(
            $this->getLocalPath() . 'views/templates/hook/swissid-block-myAccount.tpl',
            [
                'link' => $this->context->link->getModuleLink($this->name, 'authenticate', [
                    'action' => $action,
                    'redirect_s' => $this->context->link->getPageLink('my-account'),
                    'redirect_e' => $this->context->link->getPageLink('my-account')
                ], true),
                'img_dir_url' => $this->_path . 'views/img',
                'linked' => $linked,
                'age_over' => $ageOver,
                'age_verification' => Configuration::get('SWISSID_AGE_VERIFICATION'),
                'age_verification_optional' => Configuration::get('SWISSID_AGE_VERIFICATION_OPTIONAL'),
                'age_verification_url' => $this->context->link->getModuleLink($this->name, 'authenticate', [
                    'action' => 'ageVerify',
                    'redirect_s' => $this->context->link->getPageLink('my-account'),
                    'redirect_e' => $this->context->link->getPageLink('my-account'),
                ], true),
                'age_verification_text' => $this->getAgeVerificationText(),
            ]
        );
    }

    /**
     * @return string
     */
    private function getAgeVerificationText()
    {
        return Configuration::get('SWISSID_AGE_VERIFICATION_TEXT', $this->context->language->id);
    }

    /**
     * hook is displayed on the page 'login' on the create form page
     *
     * @return string
     */
    public function hookDisplayCustomerAccountFormTop()
    {
        $linked = false;
        if (isset($this->context->customer->id)) {
            if (SwissidCustomer::isCustomerLinkedById($this->context->customer->id)) {
                $linked = true;
            }
        }
        return $this->fetch(
            $this->getLocalPath() . 'views/templates/hook/swissid-login.tpl',
            [
                'login_url' => $this->context->link->getModuleLink($this->name, 'authenticate', [
                    'action' => 'login',
                    'redirect_s' => $this->context->link->getPageLink('my-account'),
                    'redirect_e' => $this->context->link->getPageLink('authentication', ['create_account' => 1]),
                ], true),
                'img_dir_url' => $this->_path . 'views/img',
                'linked' => $linked,
            ]
        );
    }

    /**
     * hook is displayed on the page 'login' after form
     *
     * @return string
     */
    public function hookDisplayCustomerLoginFormAfter()
    {
        return $this->fetch(
            $this->getLocalPath() . 'views/templates/hook/swissid-login.tpl',
            [
                'login_url' => $this->context->link->getModuleLink($this->name, 'authenticate', [
                    'action' => 'login',
                    'redirect_s' => $this->context->link->getPageLink('my-account'),
                    'redirect_e' => $this->context->link->getPageLink('authentication'),
                ], true),
                'img_dir_url' => $this->_path . 'views/img',
            ]
        );
    }

    /**
     * hook is displayed on the page 'order'
     *
     * @return string|null
     */
    public function hookDisplayPersonalInformationTop()
    {
        if (isset($this->context->customer->id)) {
            if (SwissidCustomer::isCustomerLinkedById($this->context->customer->id)) {
                return null;
            }
        }
        return $this->fetch(
            $this->getLocalPath() . 'views/templates/hook/swissid-login.tpl',
            [
                'login_url' => $this->context->link->getModuleLink($this->name, 'authenticate', [
                    'action' => 'login',
                    'redirect_s' => $this->context->link->getPageLink('order'),
                    'redirect_e' => $this->context->link->getPageLink('order'),
                ], true),
                'img_dir_url' => $this->_path . 'views/img',
            ]
        );
    }

    /**
     * hook to react if a customer is deleted from the shop by the merchant
     *
     * @param $params
     * @return bool
     */
    public function hookActionObjectCustomerDeleteAfter($params)
    {
        $customer = $params['object'];
        if (!isset($customer->id)) {
            return false;
        }
        return SwissidCustomer::removeSwissidCustomerByCustomerId($customer->id);
    }

    /**
     * hook to react if ca product is deleted from the shop by the merchant
     *
     * @param array $parameters
     * @return bool
     */
    public function hookActionObjectProductDeleteAfter($parameters)
    {
        $product = $parameters['object'];
        if (!isset($product->id)) {
            return false;
        }
        return SwissidAgeOverProduct::removeAgeOverProductByProductId($product->id);
    }

    /**
     * hook to react if a customer logs himself out
     */
    public function hookActionCustomerLogoutAfter()
    {
        $redirectTo = $this->context->link->getModuleLink($this->name, 'authenticate', [
            'action' => 'logout',
            'redirect_s' => $this->context->link->getPageLink('authentication'),
            'redirect_e' => $this->context->link->getPageLink('authentication'),
        ], true);
        Tools::redirect($redirectTo);
    }
}
