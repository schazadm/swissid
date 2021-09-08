<style type="text/css">
    .swissid-btn-connect span {
        background: url({$img_dir_url}/connect.svg) no-repeat, linear-gradient(transparent, transparent);
    }
</style>
<form id="swissid-form" method="POST" target="_self" action="{$login_url}">
    <div id="swissid-content-login" class="text-sm-center clearfix">
        <button type="button"
                class="swissid-btn swissid-btn-primary swissid-btn-connect"
        >
            <span class="connect" aria-hidden="true"></span>
            {l s='Login with SwissID' mod='swissid'}
        </button>
    </div>
</form>

<hr>