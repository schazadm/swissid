<?php

/**
 * Class AdminSwissidAgeOverProductController
 *
 * Handles the list and the form of the SwissID age over products
 */
class AdminSwissidAgeOverProductController extends ModuleAdminController
{
    /**
     * AdminSwissidAgeOverProductController constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'swissid_age_over_product';
        $this->className = 'SwissidAgeOverProduct';
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
        $this->initForm();
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
        $this->_select .= 'pl.name, p.active ';
        $this->_join .= 'LEFT JOIN ' . _DB_PREFIX_ . 'product as p ' .
            'ON a.id_product = p.id_product ';
        $this->_join .= 'LEFT JOIN `ps_product_lang` pl ' .
            'ON (pl.`id_product` = p.`id_product` ' .
            'AND pl.`id_lang` = ' . $this->context->language->id . ' ' .
            'AND pl.`id_shop` = ' . $this->context->shop->id . ')';
        $this->fields_list = [
            'id_product' => [
                'title' => $this->trans('ID', [], 'Admin.Global'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
            ],
            'name' => [
                'title' => $this->trans('Name', [], 'Admin.Global'),
                'width' => 'auto',
                'filter_key' => 'pl!name'
            ],
            'active' => [
                'title' => $this->trans('Active', [], 'Admin.Global'),
                'align' => 'center',
                'type' => 'bool',
                'class' => 'fixed-width-sm',
                'orderby' => false,
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
                'title' => $this->module->l('Add over 18 Product'),
                'icon' => 'icon-info-sign'
            ],
            'input' => [
                [
                    'type' => 'select',
                    'label' => $this->trans('Product', [], 'Admin.Global'),
                    'name' => 'id_product',
                    'col' => 4,
                    'desc' => $this->module->l('Choose the Product which needs a age verification'),
                    'options' => [
                        'query' => $this->getProducts(),
                        'name' => 'name',
                        'id' => 'id_product'
                    ]
                ],
            ],
            'submit' => [
                'title' => $this->module->l('Save')
            ]
        ];
    }

    /**
     * Return products list.
     *
     * @param bool|null $onlyActive Returns only active products when `true`
     * @return array Products
     * @throws PrestaShopDatabaseException
     */
    private function getProducts($onlyActive = null)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
            SELECT p.`id_product` AS `id_product`, pl.`name` AS `name` 
            FROM `' . _DB_PREFIX_ . 'product` p 
            LEFT JOIN `ps_product_lang` pl ON (pl.`id_product` = p.`id_product` AND pl.`id_lang` = ' . $this->context->language->id . ' AND pl.`id_shop` = ' . $this->context->shop->id . ') 
            WHERE 1 ' .
            ($onlyActive ? ' AND `active` = 1' : '') . '
            ORDER BY `id_product` ASC'
        );
    }
}