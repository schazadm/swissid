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