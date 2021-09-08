{* smarty function *}
<p>{l s='Hello World from template' mod='training'}</p>
{* smarty modifier *}
<p>{'10.08'|convertAndFormatPrice}</p>
{* smarty modifier -> created by us *}
<p>{'Test'|trainingModifier}</p>
{* how to call basic php functions *}
{$languages|dump}
{* smarty specific use of for-loop *}
{foreach $languages as $lang}
    {$lang.name}
{/foreach}