{**
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
*}

<div class="panel">

    <div class="panel-heading">
        <i class="icon-info-circle"></i>
        {l s='SwissID RP Information' mod='swissid'}
    </div>

    <div class="row moduleConfig-header">
        <div class="col-xs-12 col-md-3 text-left">
            <img src="{$module_dir|escape:'html':'UTF-8'}views/img/swissid_logo.svg" alt="SwissID Logo"/>
        </div>
        <div class="col-xs-12 col-md-9 text-left">
            <h2>
                {l s='To use this SwissID Authentication Module you must be a registered Relying Party (RP).' mod='swissid'}
            </h2>
            <h2>
                {l s='The registration is done by SwissSign Group AG.' mod='swissid'}
            </h2>
            <a class="btn" data-toggle="collapse" data-target=".moduleConfig-content">
                {l s='More Information' mod='swissid'}
            </a>
        </div>
    </div>

    <div class="moduleConfig-content collapse">
        <hr/>

        <div class="row">
            <div class="col-xs-12">
                <h2>I. {l s='Registration' mod='swissid'}</h2>
                <h4>
                    1. {l s='Go to the official website of SwissID' mod='swissid'}
                    <a href="https://www.swissid.ch/onboarding" target="_blank">swissid.ch/onboarding</a>
                    {l s='to proceed with the registration.' mod='swissid'}
                </h4>
                <br/>
                <h4>
                    2. {l s='In addition to the other information, you will also be asked to specify a so-called "Redirection-URL". You can use the URL listed below for the registration.' mod='swissid'}
                </h4>
                <b>{l s='Redirection-URL:' mod='swissid'}</b>
                <pre>{$redirect_url}</pre>
                <br/>
                <h4>
                    3. {l s='After you receive your "Client ID" and "Secret" (password) then you are ready to start with the configuration of the module.' mod='swissid'}
                </h4>
            </div>
        </div>
    </div>
</div>
