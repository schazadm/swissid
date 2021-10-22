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
{if isset($linked) && $linked}
{else}
    <form id="swissid-form" method="POST" target="_self" action="{$login_url}">
        <div id="swissid-content-login" class="text-sm-center clearfix">
            <button type="submit"
                    class="swissid-btn swissid-btn-primary swissid-btn-connect"
            >
                <span class="connect" aria-hidden="true"></span>
                {l s='Login with SwissID' mod='swissid'}
            </button>
        </div>
    </form>
    <hr>
{/if}