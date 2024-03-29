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

<style type="text/css">
    .swissid-verifyAge.swissid-btn {
        display: inline-block;
    }

    .swissid-verifyAge.swissid-btn-connect {
        height: 38px;
        line-height: inherit;
    }

    .swissid-verifyAge.swissid-btn-connect span {
        background: url({$img_dir_url}/connect_40x40.svg) no-repeat, linear-gradient(transparent, transparent);
        width: 40px;
        height: 40px;
    }

    .modal-body .shop-logo {
        padding: 20px 0;
    }

    .modal-header {
        display: flex;
    }

    .modal-header .close {
        padding: 1rem;
        margin: -1rem -1rem -1rem auto;
    }

    .modal-body .divide-right {
        border-right: 1px solid #dbdbdb;
    }

    .modal-body .logo-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100px;
        max-height: 100%;
    }
</style>

<div class="modal fade" id="ageVerificationModal" tabindex="-1" role="dialog"
     aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    {if isset($isAgeOver) && !$isAgeOver}
                        {l s='You cannot proceed with the checkout' mod='swissid'}
                    {else}
                        {l s='Verify your Age with the help of SwissID' mod='swissid'}
                    {/if}
                </h4>
                {if !isset($show)}
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="material-icons">close</i></span>
                    </button>
                {/if}
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-5 divide-right logo-container">
                        {if isset($isAgeOver) && !$isAgeOver}
                            <img class="logo img-responsive" src="{$img_dir_url}/-18.png" alt="SwissID Logo"
                                 style="width: 100px;"
                            >
                        {else}
                            <img class="logo img-responsive" src="{$img_dir_url}/swissid_logo.svg" alt="SwissID Logo">
                        {/if}
                    </div>
                    <div class="col-md-7">
                        <p>
                            {if isset($isAgeOver) && !$isAgeOver}
                                {l s='Unfortunately, because you are not over 18 years old, you will not be able to complete the checkout process.' mod='swissid'}
                            {else}
                                {$age_verification_text}
                            {/if}
                        </p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <form id="swissid-form"
                      method="POST"
                      target="_self"
                      action="{if isset($age_verification_url)}{$age_verification_url}{/if}"
                >
                    <button type="submit" class="swissid-verifyAge swissid-btn swissid-btn-primary swissid-btn-connect">
                        <span class="connect" aria-hidden="true"></span>
                        {l s='Verify Age with SwissID' mod='swissid'}
                    </button>
                    {if isset($age_verification_optional) && $age_verification_optional}
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            {l s='Skip verification' mod='swissid'}
                        </button>
                    {/if}
                    {if isset($isAgeOver) && !$isAgeOver}
                        <a href="/" class="btn btn-secondary"
                                {if isset($age_verification_optional) && $age_verification_optional}
                                    style="margin-top: 10px;"
                                {/if}
                        >
                            {l s='Back to the Homepage' mod='swissid'}
                        </a>
                    {/if}
                </form>
            </div>
        </div>
    </div>
</div>

{if isset($show)}
    <script type="application/javascript">
        var waitForJQuery = setInterval(function () {
            if (typeof $ != 'undefined') {
                $(document).ready(function () {
                    $('#ageVerificationModal').modal({
                        show: true,
                        keyboard: false,
                        backdrop: 'static'
                    });
                });
                clearInterval(waitForJQuery);
            }
        }, 10);
    </script>
{/if}