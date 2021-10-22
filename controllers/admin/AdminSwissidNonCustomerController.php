<?php

/** ====================================================================
 *
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
 *
 * ================================================================== **/

/**
 * Class AdminSwissidNonCustomerController
 *
 * Handles the list non SwissID customers
 */
class AdminSwissidNonCustomerController extends ModuleAdminController
{
    const FILE_NAME = 'AdminSwissidNonCustomerController';

    /**
     * AdminSwissidNonCustomerController constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'swissid_customer';
        // prevent redirection
        $this->list_no_link = true;
        $this->addRowAction('sendMail');
        parent::__construct();
    }

    public function init()
    {
        parent::init();
        $this->initList();
    }

    /**
     * Defines and adds the CSS & Js files
     *
     * @param bool $isNewTheme
     * @throws PrestaShopException
     */
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addJS($this->module->getPathUri() . 'views/js/swissid-back-mail.js');
        Media::addJsDef([
            'swissidNonCustomerController' => $this->context->link->getAdminLink($this->controller_name)
        ]);
    }

    /**
     * Creates a database query and defines the list fields that will be shown
     */
    private function initList()
    {
        $this->_select .= 'cu.firstname, cu.lastname, cu.email, cu.active, gl.name as social_title';
        $this->_join .= 'RIGHT JOIN ' . _DB_PREFIX_ . 'customer as cu ON a.id_customer = cu.id_customer';
        $this->_join .= ' LEFT JOIN ' . _DB_PREFIX_ . 'gender_lang gl ON cu.id_gender = gl.id_gender' .
            ' AND gl.id_lang = ' . (int)$this->context->language->id;
        $this->_where .= 'AND a.id_customer IS NULL AND cu.active = 1';

        $this->fields_list = [
            'social_title' => [
                'title' => $this->trans('Social title', [], 'Admin.Global'),
                'width' => 50,
                'filter_key' => 'gl!social_title'
            ],
            'firstname' => [
                'title' => $this->trans('First name', [], 'Admin.Global'),
                'width' => 'auto',
                'filter_key' => 'cu!firstname'
            ],
            'lastname' => [
                'title' => $this->trans('Last name', [], 'Admin.Global'),
                'width' => 'auto',
                'filter_key' => 'cu!lastname'
            ],
            'email' => [
                'title' => $this->trans('E-Mail', [], 'Admin.Global'),
                'width' => 'auto',
                'filter_key' => 'cu!email'
            ],
        ];
    }

    /**
     * Function to display a send mail button on the non-customer admin page
     *
     * @return string|null
     */
    public function displaySendMailLink()
    {
        try {
            return $this->context->smarty->fetch('module:swissid/views/templates/admin/mail-btn.tpl');
        } catch (Exception $e) {
            return null;
        }
    }

    public function ajaxProcessSendMail()
    {
        // set default responses
        $res['status'] = 'error';
        $res['message'] = $this->module->l(
            'An error occurred while trying to handle your request. Please try again later.',
            self::FILE_NAME
        );
        // check if mail is sent in the request
        if (Tools::getIsset('non_swissid_customer_email')) {
            $emailAddress = Tools::getValue('non_swissid_customer_email');
            // validate mail address and check if customer with given mail exists
            if (Validate::isEmail($emailAddress) && Customer::customerExists($emailAddress)) {
                // obtain the customer object
                $customer = (new Customer())->getByEmail($emailAddress);
                $data = array(
                    '{firstname}' =>
                        $customer->firstname,
                    '{lastname}' =>
                        $customer->lastname,
                    '{swissid_logo}' =>
                        Tools::getHttpHost(true) . __PS_BASE_URI__ . 'modules/swissid/views/img/swissid_logo.png',
                    '{age_verification_text}' =>
                        Configuration::get('SWISSID_AGE_VERIFICATION_TEXT', $customer->id_lang),
                    '{login_page}' =>
                        $this->context->link->getPageLink('authentication'),
                );
                if (Mail::Send(
                    (int)$customer->id_lang,
                    'reminder',
                    $this->module->l('SwissID Reminder', self::FILE_NAME),
                    $data,
                    $customer->email,
                    $customer->firstname . ' ' . $customer->lastname,
                    null,
                    null,
                    null,
                    null,
                    _PS_MODULE_DIR_ . 'swissid/mails',
                    false
                )) {
                    $res['status'] = 'success';
                    $res['message'] = $this->module->l(
                            'An e-mail was successfully sent to ',
                            self::FILE_NAME
                        ) . $customer->email;
                }
            }
        }
        echo json_encode($res);
    }
}
