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
 * Class AdminSwissidConfigurationController
 *
 * Handles the view and the form of the module configuration page
 */
class AdminSwissidConfigurationController extends ModuleAdminController
{
    const FILE_NAME = 'AdminSwissidConfigurationController';

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
                'title' => $this->module->l('SwissID Client Settings', self::FILE_NAME),
                'fields' => [
                    'SWISSID_CLIENT_ID' => [
                        'title' => $this->module->l(
                            'Client ID',
                            self::FILE_NAME
                        ),
                        'desc' => $this->module->l(
                            'Enter a valid client identifier',
                            self::FILE_NAME
                        ),
                        'hint' => $this->module->l(
                            'Specific Client identifier is provided by the SwissSign Group',
                            self::FILE_NAME
                        ),
                        'type' => 'text',
                        'required' => true,
                    ],
                    'SWISSID_CLIENT_SECRET' => [
                        'title' => $this->module->l(
                            'Secret',
                            self::FILE_NAME
                        ),
                        'desc' => $this->module->l(
                            'Enter a valid client secret',
                            self::FILE_NAME
                        ),
                        'hint' => $this->module->l(
                            'The secret is an extra layer of security and is also provided by the SwissSign Group',
                            self::FILE_NAME
                        ),
                        'type' => 'text',
                        'required' => true,
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions')
                ]
            ],
            'swissid_age_verification' => [
                'title' => $this->module->l(
                    'SwissID Age Verification',
                    self::FILE_NAME
                ),
                'fields' => [
                    'SWISSID_AGE_VERIFICATION' => [
                        'title' => $this->module->l(
                            'Age verification',
                            self::FILE_NAME
                        ),
                        'desc' => $this->module->l(
                            'Decide whether the age should be verified (≥18)',
                            self::FILE_NAME
                        ),
                        'type' => 'bool',
                        'cast' => 'boolval',
                    ],
                    'SWISSID_AGE_OVER_PRODUCT' => [
                        'title' => $this->module->l(
                            'Over 18 Products',
                            self::FILE_NAME
                        ),
                        'desc' => $this->module->l(
                            'Decide whether a general age verification is needed or just for specific products.',
                            self::FILE_NAME
                        ),
                        'hint' => $this->module->l(
                            'If you activate age verification for specific products, ' .
                            'then you can manage your ≥18 products under its separate tab.',
                            self::FILE_NAME
                        ),
                        'type' => 'bool',
                        'cast' => 'boolval',
                    ],
                    'SWISSID_AGE_VERIFICATION_OPTIONAL' => [
                        'title' => $this->module->l(
                            'Age verification optional',
                            self::FILE_NAME
                        ),
                        'desc' => $this->module->l(
                            'Decide whether the age verification should be optional or mandatory. ' .
                            'If this is option is set to \'True\' then the age verification can be skipped.',
                            self::FILE_NAME
                        ),
                        'type' => 'bool',
                        'cast' => 'boolval',
                    ],
                    'SWISSID_AGE_VERIFICATION_TEXT' => [
                        'title' => $this->module->l(
                            'Age verification text',
                            self::FILE_NAME
                        ),
                        'desc' => $this->module->l(
                            'Decide which text should be displayed during the verification process',
                            self::FILE_NAME
                        ),
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
            'ageVerificationInputName' => 'SWISSID_AGE_VERIFICATION',
            'ageVerificationOptionalInputName' => 'SWISSID_AGE_VERIFICATION_OPTIONAL',
            'ageVerificationTextInputName' => 'SWISSID_AGE_VERIFICATION_TEXT',
            'ageOverProductInputName' => 'SWISSID_AGE_OVER_PRODUCT',
        ]);
    }
}
