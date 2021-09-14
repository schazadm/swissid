<style type="text/css">
    .swissid-btn-connect span {
        background: url({$img_dir_url}/connect.svg) no-repeat, linear-gradient(transparent, transparent);
    }
</style>

<div class="help-block">
    <ul>
        {if $error_msg}
            <li class="alert alert-danger">{$error_msg|nl2br nofilter}</li>
        {/if}
        {if $warning_msg}
            <li class="alert alert-warning">{$warning_msg|nl2br nofilter}</li>
        {/if}
        {if $info_msg}
            <li class="alert alert-info">{$info_msg|nl2br nofilter}</li>
        {/if}
    </ul>
</div>


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