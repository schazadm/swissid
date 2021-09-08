<?php

use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use Symfony\Component\DependencyInjection\ContainerInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Swissid extends Module
{
    const ADMIN_SWISSID_PARENT_CONTROLLER = 'AdminSwissidParent';
    const ADMIN_SWISSID_CONFIGURATION_CONTROLLER = 'AdminSwissidConfiguration';
    const ADMIN_SWISSID_CUSTOMER_CONTROLLER = 'AdminSwissidCustomer';

    public function __construct()
    {
        $this->name = 'swissid';
        $this->tab = 'other';
        $this->version = '1.0.0';
        $this->author = 'Online Services Rieder GmbH';
        $this->need_instance = 1;

        parent::__construct();

        $this->displayName = $this->l('SwissID');
        $this->description = $this->l('Log in easily and securely with SwissID.');
        $this->confirmUninstall = $this->l('Are you sure about removing the registered clients?');
        $this->ps_versions_compliancy = ['min' => '1.7.5.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        include(dirname(__FILE__) . '/sql/install.php');

        if (!parent::install()) {
            return false;
        }

        $hooks = [
            'header',
            'backOfficeHeader',
            'displayCustomerAccount',
            'displayCustomerAccountForm',
            'displayCustomerAccountFormTop',
            'displayCustomerLoginFormAfter',
        ];

        if (!$this->registerHook($hooks)) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        include(dirname(__FILE__) . '/sql/uninstall.php');

        if (!parent::uninstall()) {
            return false;
        }

        return true;
    }

    /**
     * @throws PrestaShopException
     */
    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink(static::ADMIN_SWISSID_CONFIGURATION_CONTROLLER));
    }

    /**
     * @return ContainerInterface
     */
    public function getModuleContainer()
    {
        return SymfonyContainer::getInstance();
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
                'name' => 'Configuration',
                'parent_class_name' => static::ADMIN_SWISSID_PARENT_CONTROLLER,
                'class_name' => static::ADMIN_SWISSID_CONFIGURATION_CONTROLLER
            ],
            [
                'name' => 'Customer',
                'parent_class_name' => static::ADMIN_SWISSID_PARENT_CONTROLLER,
                'class_name' => static::ADMIN_SWISSID_CUSTOMER_CONTROLLER
            ]
        ];
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
         $this->context->controller->addJS($this->_path . '/views/js/swissid-front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/swissid-front.css');
        $this->context->controller->addCSS($this->_path . '/views/css/sesam-buttons.css');
    }

    /**
     * hook is displayed on the page 'my-account'
     */
    public function hookDisplayCustomerAccount()
    {
        echo 'hookDisplayCustomerAccount';
    }

    /**
     * hook is displayed on the page 'my-account -> profile'
     */
    public function hookDisplayCustomerAccountForm()
    {
        echo 'hookDisplayCustomerAccountForm';
    }

    /**
     * hook is displayed on the page 'login' on the create form page
     */
    public function hookDisplayCustomerAccountFormTop()
    {
        echo 'hookDisplayCustomerAccountFormTop';
    }

    /**
     * hook is displayed on the page 'login' after form
     */
    public function hookDisplayCustomerLoginFormAfter()
    {
        return $this->fetch($this->getLocalPath() . 'views/templates/hook/swissid-login.tpl',
            [
                'login_url' => '/',
                'img_dir_url' => $this->_path . 'views/img'
            ]
        );
    }
}
