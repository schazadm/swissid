{extends file="helpers/options/options.tpl"}

{block name="leadin" append}
    {include file="$info_tpl"}
{/block}

{block name="input" prepend}
    {if $field['type'] == 'osr_password'}
        <div class="col-lg-9">{if isset($field['suffix'])}
            <div class="input-group{if isset($field.class)} {$field.class}{/if}">{/if}
                <input type="password"
                        {if isset($field['id'])} id="{$field['id']}"{/if}
                       size="{if isset($field['size'])}{$field['size']|intval}{else}5{/if}"
                       name="{$key}"
                       value="PLACEHOLDER"
                />
                {if isset($field['suffix'])}
                    <span class="input-group-addon">{$field['suffix']|strval}</span>
                {/if}
                {if isset($field['suffix'])}</div>{/if}
        </div>
    {/if}
{/block}