{extends file="helpers/form/form.tpl"}

{block name="field"}

    {if $input.type == 'blog_checkbox'}
        <div class="col-lg-{if isset($input.col)}{$input.col|intval}{else}9{/if} {if !isset($input.label)}col-lg-offset-3{/if}">
            {foreach $input.values.query as $value}
                <div class="checkbox">
                {assign var=id value=$value.{$input.values.id}}
                <label for="{$input.name|cat:$id}" class="t">
                <input type="checkbox" name="{$input.name|cat:'[]'}" id="{$input.name|cat:$id}"
                    class="{if isset($input.class)}{$input.class}{/if}" value="{$id}"
                    {if isset($fields_value[$input.name]) && is_array($fields_value[$input.name]) && in_array($id,$fields_value[$input.name])}checked="checked"{/if} />
                {$value[$input.values.name]}</label>
                </div>
            {/foreach}
        </div>
     {else}
	    {$smarty.block.parent}
     {/if}
     
{/block}

{block name="input"}

    {if $input.name == "link_rewrite"}

        <script type="text/javascript">
            {if isset($PS_ALLOW_ACCENTED_CHARS_URL) && $PS_ALLOW_ACCENTED_CHARS_URL}
            var PS_ALLOW_ACCENTED_CHARS_URL = 1;
            {else}
            var PS_ALLOW_ACCENTED_CHARS_URL = 0;
            {/if}
        </script>
        {$smarty.block.parent}

    {else}
        {$smarty.block.parent}
    {/if}

{/block}