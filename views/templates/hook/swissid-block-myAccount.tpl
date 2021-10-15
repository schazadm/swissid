<style type="text/css">
    .swissid-myAccountBlock.swissid-btn {
        display: inline-block;
    }

    .swissid-myAccountBlock.swissid-btn-mini {
        width: 38px;
    }

    .swissid-myAccountBlock.swissid-btn-connect {
        height: 38px;
    }

    .swissid-myAccountBlock.swissid-btn-connect span {
        background: url({$img_dir_url}/connect_40x40.svg) no-repeat, linear-gradient(transparent, transparent);
        width: 40px;
        height: 40px;
    }
</style>

{* BLOCK SWISSID CONNECT - DISCONNECT *}
<a class="col-lg-4 col-md-6 col-sm-6 col-xs-12 text-sm-center"
   id="swissid-link"
   href="{$link}"
   data-toggle="tooltip"
   data-html="true"
   data-placement="top"
        {if !$linked}
            title="{l s='If you connect your local customer account with your SwissID account, you can log in directly with SwissID.' mod='swissid'}"
        {else}
            title=""
        {/if}
>
    <span class="link-item">
        <i class="material-icons">
            <span class="swissid-myAccountBlock swissid-btn swissid-btn-primary swissid-btn-connect swissid-btn-mini swissid-btn-connect-black">
                <span class="connect" aria-hidden="true"></span>
            </span>
        </i>

        {if $linked}
            {l s='Disconnect your local account from your SwissID' mod='swissid'}
        {else}
            {l s='Connect your local account to your SwissID' mod='swissid'}
        {/if}
    </span>
</a>

{* BLOCK - SWISSID AGE VERFICATION *}
{if isset($age_verification) && $age_verification}
    {if isset($age_over) && !$age_over}
        {if isset($age_verification_optional) && !$age_verification_optional}
            <a class="col-lg-4 col-md-6 col-sm-6 col-xs-12 text-sm-center"
               id="swissid-age"
               href="#"
               data-toggle="modal"
               data-target="#ageVerificationModal"
            >
            <span class="link-item">
                <i class="material-icons">
                    <span class="swissid-myAccountBlock swissid-btn swissid-btn-primary swissid-btn-connect swissid-btn-mini swissid-btn-connect-black">
                        <span class="connect" aria-hidden="true"></span>
                    </span>
                </i>
                {l s='Verify your age with the help of SwissID' mod='swissid'}
                <br/>
            </span>
            </a>
            {include file="./swissid-age-verification-modal.tpl"}
        {/if}
    {/if}
{/if}
