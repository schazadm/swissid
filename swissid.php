<?php

use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

    private $errorMsg;
    private $warningMsg;
    private $infoMsg;
    private $successMsg;

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

        $this->fillMessages();
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
        $route = $this->context->link->getAdminLink(static::ADMIN_SWISSID_CONFIGURATION_CONTROLLER);
        // $route = $this->getModuleContainer()->get('router')->generate('swissid_admin_configruation');
        Tools::redirectAdmin($route);
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
                'name' => $this->trans('Configuration', [], 'Admin.Global'),
                'parent_class_name' => static::ADMIN_SWISSID_PARENT_CONTROLLER,
                'class_name' => static::ADMIN_SWISSID_CONFIGURATION_CONTROLLER
            ],
            [
                'name' => 'SwissID ' . $this->trans('Customer', [], 'Admin.Global'),
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
        $action = 'connect';
        $linked = false;
        if (isset($this->context->customer)) {
            if (SwissidCustomer::isCustomerLinkedById($this->context->customer->id)) {
                $action = 'disconnect';
                $linked = true;
            }
        }

        return $this->fetch($this->getLocalPath() . 'views/templates/hook/swissid-block-myAccount.tpl',
            [
                'link' => $this->context->link->getModuleLink($this->name, 'authentication', ['action' => $action], true),
                'img_dir_url' => $this->_path . 'views/img',
                'linked' => $linked,
                'error_msg' => $this->errorMsg,
                'warning_msg' => $this->warningMsg,
                'info_msg' => $this->infoMsg,
                'success_msg' => $this->successMsg,
            ]
        );
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
                'login_url' => $this->context->link->getModuleLink($this->name, 'authentication', ['action' => 'login'], true),
                'img_dir_url' => $this->_path . 'views/img',
                'error_msg' => $this->errorMsg,
                'warning_msg' => $this->warningMsg,
                'info_msg' => $this->infoMsg,
            ]
        );
    }

    private function fillMessages()
    {
        if (isset($this->context->cookie->redirect_error)) {
            $this->errorMsg = $this->context->cookie->redirect_error;
            unset($this->context->cookie->redirect_error);
        }
        if (isset($this->context->cookie->redirect_warning)) {
            $this->warningMsg = $this->context->cookie->redirect_warning;
            unset($this->context->cookie->redirect_warning);
        }
        if (isset($this->context->cookie->redirect_info)) {
            $this->infoMsg = $this->context->cookie->redirect_info;
            unset($this->context->cookie->redirect_info);
        }
        if (isset($this->context->cookie->redirect_success)) {
            $this->successMsg = $this->context->cookie->redirect_success;
            unset($this->context->cookie->redirect_success);
        }
    }
}
