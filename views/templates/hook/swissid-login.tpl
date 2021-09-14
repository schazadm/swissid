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