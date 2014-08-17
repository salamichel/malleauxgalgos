{extends file="helpers/form/form.tpl"}

{block name="label"}
    {if $input.type == 'text_label' && !isset($customer)}
            {if isset($input.label)}<label>{$input.label} </label>{/if}
    {else}
            {$smarty.block.parent}
    {/if}
{/block}

{block name="field"}
    {if $input.type == 'text_label'}
        <div class="margin-form">{$fields_value[$input.name]}</div>
     {else}
	{$smarty.block.parent}
     {/if}
     
{/block}