{** ====================================================================
 *
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
 *
 ================================================================== **}

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