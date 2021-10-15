<?php

/**
 * Class AdminSwissidNonCustomerController
 *
 * Handles the list non SwissID customers
 */
class AdminSwissidNonCustomerController extends ModuleAdminController
{
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
     */
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addJS($this->module->getPathUri() . 'views/js/swissid-back-mail.js');
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
}