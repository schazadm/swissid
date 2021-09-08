<?php


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

    public function init()
    {
        parent::init();
        $this->initOptions();
    }

    private function initOptions()
    {
        $this->fields_options = [
            'swissid' => [
                'title' => $this->module->l('SwissID Client Settings'),
                'fields' => [
                    'SWISSID_CLIENT_ID' => [
                        'title' => $this->module->l('Client ID'),
                        'desc' => $this->module->l('Enter a valid client identifier'),
                        'hint' => $this->module->l('Specific Client identifier provided by the SwissSign Group'),
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
                    'SWISSID_AGE_VERIFICATION' => [
                        'title' => $this->module->l('Age verification'),
                        'desc' => $this->module->l('Decide whether the age should be verified'),
                        'hint' => $this->module->l('Hint...'),
                        'type' => 'bool',
                        'cast' => 'boolval',
                    ],
                    'SWISSID_AGE_VERIFICATION_OPTIONAL' => [
                        'title' => $this->module->l('Age verification optional'),
                        'desc' => $this->module->l('Decide whether the age verification should be optional or mandatory. If this is option is set to True then the age verification can be skipped.'),
                        'hint' => $this->module->l('Hint...'),
                        'type' => 'bool',
                        'cast' => 'boolval',
                    ],
                ],
                'submit' => [
                    'title' => $this->module->l('Save')
                ]
            ]
        ];
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addCSS($this->module->getPathUri() . 'views/css/swissid-back.css');
        $this->addJS($this->module->getPathUri() . 'views/js/swissid-back.js');
        Media::addJsDef([
            'context' => json_encode(Context::getContext()),
            'redirectControllerUrl' => preg_replace('#&id_lang=[0-9]{1,2}$#', '', $this->context->link->getModuleLink($this->module->name, 'RedirectManager', [], true)),
            'psBaseUrl' => Tools::getHttpHost(true),
            'psVersion' => _PS_VERSION_,
            'ageVerificationInputName' => 'SWISSID_AGE_VERIFICATION',
            'ageVerificationOptionalInputName' => 'SWISSID_AGE_VERIFICATION_OPTIONAL',
        ]);
    }

    public function beforeUpdateOptions()
    {
        parent::beforeUpdateOptions();
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
}