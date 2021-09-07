<?php
/**
 * 2007-2021 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2021 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Swissid extends Module
{
    private $_html = '';

    public function __construct()
    {
        $this->name = 'swissid';
        $this->tab = 'other_modules';
        $this->version = '1.0.0';
        $this->author = 'Online Services Rieder GmbH';
        $this->need_instance = 1;

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('SwissID');
        $this->description = $this->l('Log in easily and securely with SwissID.');
        $this->confirmUninstall = $this->l('Are you sure about removing the registered clients?');
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
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
        // check whether a post was made
        if (Tools::isSubmit('submitSwissidModule')) {
            if ($this->validateSubmittedValues()) {
                $this->updateValues();
            }
            $this->addConfirmationMessage();
            $this->addErrorMessage();
        }

        $this->context->smarty->assign('module_dir', $this->_path);
        $this->_html .= $this->fetch($this->local_path . 'views/templates/admin/configure.tpl');
        $this->_html .= $this->renderForm();
        return $this->_html;
    }

    /**
     * @throws PrestaShopException
     */
    private function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSwissidModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    private function getConfigForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('SwissID Client Settings'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'col' => 4,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-building"></i>',
                        'desc' => $this->l('Enter a valid client identifier'),
                        'name' => 'SWISSID_CLIENT_ID',
                        'label' => $this->l('Client ID'),
                        'required' => true,
                        'maxlength' => 256,
                    ],
                    [
                        'col' => 4,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-key"></i>',
                        'desc' => $this->l('Enter a valid client secret'),
                        'name' => 'SWISSID_CLIENT_SECRET',
                        'label' => $this->l('Secret'),
                        'required' => true,
                        'maxlength' => 256,
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Age verification'),
                        'name' => 'SWISSID_AGE_VERIFICATION',
                        'desc' => $this->l('Decide whether the age should be verified'),
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            ]
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Age verification optional'),
                        'name' => 'SWISSID_AGE_VERIFICATION_OPTIONAL',
                        'desc' => $this->l('Decide whether the age verification should be optional or mandatory. If this is option is set to True then the age verification can be skipped.'),
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            ]
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    private function getConfigFormValues()
    {
        return [
            'SWISSID_CLIENT_ID' => Configuration::get('SWISSID_CLIENT_ID'),
            'SWISSID_CLIENT_SECRET' => Configuration::get('SWISSID_CLIENT_SECRET'),
            'SWISSID_AGE_VERIFICATION' => Configuration::get('SWISSID_AGE_VERIFICATION'),
            'SWISSID_AGE_VERIFICATION_OPTIONAL' => Configuration::get('SWISSID_AGE_VERIFICATION_OPTIONAL'),
        ];
    }

    private function validateSubmittedValues()
    {
        if (!Tools::getValue('SWISSID_CLIENT_ID') || empty(Tools::getValue('SWISSID_CLIENT_ID'))) {
            $this->_errors[] = $this->l('Client ID is required');
        }

        if (!Tools::getValue('SWISSID_CLIENT_SECRET') || empty(Tools::getValue('SWISSID_CLIENT_SECRET'))) {
            $this->_errors[] = $this->l('Client Secret is required');
        }

        if (count($this->_errors) > 0) {
            return false;
        }

        return true;
    }

    private function updateValues()
    {
        foreach (array_keys($this->getConfigFormValues()) as $key) {
            if (!Configuration::updateValue($key, Tools::getValue($key))) {
                $this->_errors[] = $this->l('An error has occurred during the operation of saving %1$s', [$key]);
            }
        }

        if (count($this->_errors) <= 0) {
            $this->_confirmations[] = $this->trans('The settings have been successfully updated.', [], 'Admin.Notifications.Success');
        }
    }

    private function addErrorMessage()
    {
        if (count($this->_errors) > 0) {
            foreach ($this->_errors as $err) {
                $this->_html .= $this->displayError($err);
            }
        }
    }

    private function addConfirmationMessage()
    {
        if (count($this->_confirmations) > 0) {
            foreach ($this->_confirmations as $conf) {
                $this->_html .= $this->displayConfirmation($conf);
            }
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
        $this->context->controller->addCSS($this->_path . '/views/css/swissid-login-button.css');
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
        return $this->fetch($this->getLocalPath() . 'views/templates/hook/swissid-login-button.tpl',
            [
                'login_url' => '/',
                'img_dir_url' => $this->_path . 'views/img'
            ]
        );
    }
}
