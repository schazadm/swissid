<?php

/**
 * Class AdminSwissidConfigurationController
 *
 * Handles the view and the form of the module configuration page
 */
class AdminSwissidConfigurationController extends ModuleAdminController
{
    /**
     * AdminSwissidConfigurationController constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    /**
     * Initialises and assignees variables to the smarty template
     */
    public function init()
    {
        $this->context->smarty->assign([
            'module_dir' => $this->module->getPathUri(),
            'info_tpl' => $this->module->getLocalPath() . 'views/templates/admin/info.tpl',
            'redirect_url' => Configuration::get('SWISSID_REDIRECT_URL'),
        ]);
        parent::init();
        $this->context->smarty->assign([
            'form' => $this->initOptions()
        ]);
    }

    /**
     * Defines the fields that can be adjusted based on the merchant.
     * Based on the configuration a connection SwissID is possible.
     *
     * @return string
     */
    public function initOptions()
    {
        $this->fields_options = [
            'swissid_client' => [
                'title' => $this->module->l('SwissID Client Settings'),
                'fields' => [
                    'SWISSID_CLIENT_ID' => [
                        'title' => $this->module->l('Client ID'),
                        'desc' => $this->module->l('Enter a valid client identifier'),
                        'hint' => $this->module->l('Specific Client identifier is provided by the SwissSign Group'),
                        'type' => 'text',
                        'required' => true,
                    ],
                    'SWISSID_CLIENT_SECRET' => [
                        'title' => $this->module->l('Secret'),
                        'desc' => $this->module->l('Enter a valid client secret'),
                        'hint' => $this->module->l('The secret is an extra layer of security and is also provided by the SwissSign Group'),
                        'type' => 'text',
                        'required' => true,
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions')
                ]
            ],
            'swissid_age_verification' => [
                'title' => $this->module->l('SwissID Age Verification'),
                'fields' => [
                    'SWISSID_AGE_VERIFICATION' => [
                        'title' => $this->module->l('Age verification'),
                        'desc' => $this->module->l('Decide whether the age should be verified (≥18)'),
                        'type' => 'bool',
                        'cast' => 'boolval',
                    ],
                    'SWISSID_AGE_OVER_PRODUCT' => [
                        'title' => $this->module->l('Over 18 Products'),
                        'desc' => $this->module->l('Decide whether a general age verification is needed or just for specific products.'),
                        'hint' => $this->module->l('If you activate age verification for specific products, then you can manage your ≥18 products under its separate tab.'),
                        'type' => 'bool',
                        'cast' => 'boolval',
                    ],
                    'SWISSID_AGE_VERIFICATION_OPTIONAL' => [
                        'title' => $this->module->l('Age verification optional'),
                        'desc' => $this->module->l('Decide whether the age verification should be optional or mandatory. If this is option is set to \'True\' then the age verification can be skipped.'),
                        'type' => 'bool',
                        'cast' => 'boolval',
                    ],
                    'SWISSID_AGE_VERIFICATION_TEXT' => [
                        'title' => $this->module->l('Age verification text'),
                        'desc' => $this->module->l('Decide which text should be displayed during the verification process'),
                        'type' => 'textareaLang',
                        'lang' => true,
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions')
                ]
            ]
        ];
        return $this->renderOptions();
    }

    /**
     * Defines and adds CSS & Js files. It also adds variables to Js
     *
     * @param bool $isNewTheme
     */
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addCSS($this->module->getPathUri() . 'views/css/swissid-back.css');
        $this->addJS($this->module->getPathUri() . 'views/js/swissid-back-conf.js');
        Media::addJsDef([
            'context' => json_encode(Context::getContext()),
            'redirectControllerUrl' => preg_replace('#&id_lang=[0-9]{1,2}$#', '', $this->context->link->getModuleLink($this->module->name, 'RedirectManager', [], true)),
            'psBaseUrl' => Tools::getHttpHost(true),
            'psVersion' => _PS_VERSION_,
            'ageVerificationInputName' => 'SWISSID_AGE_VERIFICATION',
            'ageVerificationOptionalInputName' => 'SWISSID_AGE_VERIFICATION_OPTIONAL',
            'ageVerificationTextInputName' => 'SWISSID_AGE_VERIFICATION_TEXT',
            'ageOverProductInputName' => 'SWISSID_AGE_OVER_PRODUCT',
        ]);
    }

    /**
     * Before updating try to check values
     *
     * @throws PrestaShopException
     */
    /*
    public function beforeUpdateOptions()
    {
        try {
            $secretPlainText = Tools::getValue('SWISSID_CLIENT_SECRET');
            if (strlen($secretPlainText) < 25) {
                $this->errors[] = $this->module->l('The secret length is incorrect.');
            }
            $cipher = (new PhpEncryption(_NEW_COOKIE_KEY_))->encrypt($secretPlainText);
            $_POST['SWISSID_CLIENT_SECRET'] = $cipher;
        } catch (Exception $e) {
            Tools::displayError($e->getMessage());
        }
        parent::beforeUpdateOptions();
    }
    */
}