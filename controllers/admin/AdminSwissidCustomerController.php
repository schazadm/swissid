<?php


class AdminSwissidCustomerController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;

        $this->table = 'training_article';
        $this->className = 'TrainingArticle';
        $this->lang = true;
        // prevent redirection
        $this->list_no_link = true;
        // add default edit and delete functions which work out of the box
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        parent::__construct();
    }

    public function init()
    {
        parent::init();
        $this->initList();
        $this->initForm();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addCSS($this->module->getPathUri() . 'views/css/swissid-back.css');
        $this->addJS($this->module->getPathUri() . 'views/js/swissid-back.js');

        Media::addJsDef([
            'trainingArticleController' => $this->context->link->getAdminLink($this->controller_name)
        ]);
    }

    private function initList()
    {
        // table 'a' stands for the current table which is training_article as defined in the constructor
        // table 'b' stands for the lang table which in our case is training_article_lang
        // table 'c' stands for the shop table but not quite sure...
        $this->_select .= ' pl.name as product_name, b.name as article_name';
        $this->_join .= ' LEFT JOIN ' . _DB_PREFIX_ . 'product_lang as pl ' .
            'ON pl.id_product = a.id_product ' .
            'AND pl.id_lang = ' . (int)$this->context->language->id;


        $this->fields_list = [
            'id_training_article' => [
                'title' => $this->module->l('Id'),
                'width' => 30
            ],
            'article_name' => [
                'title' => $this->module->l('Name'),
                'width' => 'auto',
                'filter_key' => 'b!name' // add the filter key to prevent errors
            ],
            'type' => [
                'title' => $this->module->l('Type'),
                'width' => 'auto'
            ],
            'product_name' => [
                'title' => $this->module->l('Product'),
                'width' => 'auto',
                'filter_key' => 'pl!name' // add the filter key to prevent errors
            ],
            'description' => [
                'title' => $this->module->l('Description'),
                'width' => 'auto',
                'orderby' => false, // preventing
                'search' => false, // preventing
                'callback' => 'getDescription'
            ]
        ];

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->trans('Delete selected', [], 'Admin.Actions'),
                'icon' => 'icon-trash',
                'confirm' => $this->trans('Delete selected items?', [], 'Admin.Notifications.Warning'),
            ],
        ];
    }

    private function initForm()
    {
        $this->fields_form = [
            'tinymce' => true,
            'legend' => [
                'title' => $this->module->l('Article'),
                'icon' => 'icon-info-sign'
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->module->l('Name'),
                    'name' => 'name',
                    'lang' => true,
                    'required' => true,
                    'col' => 4,
                    'desc' => $this->module->l('Desc')
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->module->l('Description'),
                    'name' => 'description',
                    'lang' => true,
                    'col' => 6,
                    'desc' => $this->module->l('Desc'),
                    'autoload_rte' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->l('Type'),
                    'name' => 'type',
                    'col' => 4,
                    'desc' => $this->module->l('Desc')
                ],
                [
                    'type' => 'select',
                    'label' => $this->module->l('Product ID'),
                    'name' => 'id_product',
                    'col' => 4,
                    'desc' => $this->module->l('Desc'),
                    'options' => [
                        'query' => Product::getProducts($this->context->language->id, 0, 100, 'id_product', 'ASC'),
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

    public function initProcess()
    {
        // Catch actions while it's initialising
        /*
        if (Tools::isSubmit('deletetraining_article')
            || Tools::isSubmit('submitAddtraining_article')
            || Tools::isSubmit('addtraining_article')
            || Tools::isSubmit('updatetraining_article')
            || Tools::isSubmit('submitBulkdeletetraining_article')) {
        }
        */

        parent::initProcess();
    }

    public function getDescription($description, $params)
    {
        $twig = $this->module->getModuleContainer()->get('twig');
        return $twig->render('@Modules/' . $this->module->name . '/views/templates/admin/description.html.twig',
            [
                'id_training_article' => $params['id_training_article']
            ]
        );
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function ajaxProcessGetDescription()
    {
        $trainingArticleId = Tools::getValue('id_training_article');
        $article = new TrainingArticle($trainingArticleId, $this->context->language->id);

        echo $article->description;
    }
}