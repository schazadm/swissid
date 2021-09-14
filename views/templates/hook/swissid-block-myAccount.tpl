<style type="text/css">
    .swissid-btn {
        display: inline-block;
    }

    .swissid-btn-mini {
        width: 38px;
    }

    .swissid-btn-connect {
        height: 38px;
    }

    .swissid-btn-connect span {
        background: url({$img_dir_url}/connect_40x40.svg) no-repeat, linear-gradient(transparent, transparent);
        width: 40px;
        height: 40px;
    }
</style>

<a class="col-lg-4 col-md-6 col-sm-6 col-xs-12 text-sm-center"
   id="swissid-link"
   href="{$link}"
        {if !$linked}
            data-toggle="tooltip"
            title="{l s='If you connect your local customer account with your SwissID account, you can log in directly with SwissID.' mod='swissid'}"
            data-html="true"
            data-placement="top"
        {/if}
>
    <span class="link-item">
        <div class="bootstrap">
            {if $error_msg}
                <div class="alert alert-danger">{$error_msg|nl2br nofilter}</div>
            {/if}
            {if $warning_msg}
                <div class="alert alert-warning">{$warning_msg|nl2br nofilter}</div>
            {/if}
            {if $info_msg}
                <div class="alert alert-info">{$info_msg|nl2br nofilter}</div>
            {/if}
            {if $success_msg}
                <div class="alert alert-success">{$success_msg|nl2br nofilter}</div>
            {/if}
        </div>

        <i class="material-icons">
            <span class="swissid-btn swissid-btn-primary swissid-btn-connect swissid-btn-mini swissid-btn-connect-black">
                <span class="connect" aria-hidden="true"></span>
            </span>
        </i>

        {if $linked}
            {l s='Disconnect your local account to your SwissID' mod='swissid'}
        {else}
            {l s='Link your local account to your SwissID' mod='swissid'}
        {/if}
    </span>
</a>