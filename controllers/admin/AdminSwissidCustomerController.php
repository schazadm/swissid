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
 * Class AdminSwissidCustomerController
 *
 * Handles the list and the form of the SwissID customers
 */
class AdminSwissidCustomerController extends ModuleAdminController
{
    const FILE_NAME = 'AdminSwissidCustomerController';

    /**
     * AdminSwissidCustomerController constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'swissid_customer';
        $this->className = 'SwissidCustomer';
        // prevent redirection
        $this->list_no_link = true;
        // add default edit and delete functions which work out of the box
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        parent::__construct();
    }

    /**
     * @throws PrestaShopDatabaseException
     */
    public function init()
    {
        parent::init();
        $this->initList();
        // disable manual add -> because merchant doesn't know sub_id and that is important
        // $this->initForm();
    }

    /**
     * Defines and adds the CSS & Js files
     *
     * @param bool $isNewTheme
     */
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addCSS($this->module->getPathUri() . 'views/css/swissid-back.css');
    }

    /**
     * Creates a database query and defines the list fields that will be shown
     */
    private function initList()
    {
        $this->_select .= 'cu.firstname, cu.lastname, cu.email, cu.active, ';
        $this->_select .= 'gl.name as social_title ';
        $this->_join .= 'LEFT JOIN ' . _DB_PREFIX_ . 'customer as cu ' .
            'ON a.id_customer = cu.id_customer ';
        $this->_join .= 'LEFT JOIN ' . _DB_PREFIX_ . 'gender_lang gl ' .
            'ON cu.id_gender = gl.id_gender ' .
            'AND gl.id_lang = ' . (int)$this->context->language->id;
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
            'age_over' => [
                'title' => $this->module->l('Age over 18', self::FILE_NAME),
                'width' => 'auto',
                'filter_key' => 'a!age_over',
                'type' => 'bool',
            ],
        ];
        $this->bulk_actions = [
            'delete' => [
                'text' => $this->trans('Delete selected', [], 'Admin.Actions'),
                'icon' => 'icon-trash',
                'confirm' => $this->trans('Delete selected items?', [], 'Admin.Notifications.Warning'),
            ],
        ];
    }

    /**
     * Defines the form if an entry is being added or edited
     *
     * @throws PrestaShopDatabaseException
     */
    private function initForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->module->l('SwissID Customer Link', self::FILE_NAME),
                'icon' => 'icon-info-sign'
            ],
            'input' => [
                [
                    'type' => 'select',
                    'label' => $this->trans('Customer', [], 'Admin.Global'),
                    'name' => 'id_customer',
                    'col' => 4,
                    'desc' => $this->module->l(
                        'Choose the Customer whom will be linked to the SwissID',
                        self::FILE_NAME
                    ),
                    'options' => [
                        'query' => $this->getCustomers(),
                        'name' => 'name',
                        'id' => 'id_customer'
                    ]
                ],
                [
                    'type' => 'switch',
                    'label' => $this->module->l('Age over', self::FILE_NAME),
                    'name' => 'age_over',
                    'col' => 4,
                    'desc' => $this->module->l('Choose if the Customer is over 18', self::FILE_NAME),
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'age_over_on',
                            'value' => 1,
                            'label' => $this->trans('Enabled', [], 'Admin.Global'),
                        ],
                        [
                            'id' => 'age_over_off',
                            'value' => 0,
                            'label' => $this->trans('Disabled', [], 'Admin.Global'),
                        ],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->trans('Save', [], 'Admin.Actions')
            ]
        ];
    }

    /**
     * Return customers list.
     *
     * @param bool|null $onlyActive Returns only active customers when `true`
     * @return array Customers
     * @throws PrestaShopDatabaseException
     */
    private function getCustomers($onlyActive = null)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
            SELECT `id_customer`, `email`, `firstname`, `lastname`, CONCAT(`firstname`," ",`lastname`) AS `name` 
            FROM `' . _DB_PREFIX_ . 'customer`
            WHERE 1 ' . Shop::addSqlRestriction(Shop::SHARE_CUSTOMER) .
            ($onlyActive ? ' AND `active` = 1' : '') . '
            ORDER BY `id_customer` ASC'
        );
    }
}
