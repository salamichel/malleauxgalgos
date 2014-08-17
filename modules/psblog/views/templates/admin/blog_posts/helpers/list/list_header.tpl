{extends file="helpers/list/list_header.tpl"}

{block name="override_header"}

			{if $comments_active}
            <a href="{$blog_comments_link}">{$l.comments_waiting}</a>
            <a href="{$blog_comments_link}" style="color:#008000;"> : <strong>{$nb_comments}</strong></a>
			{/if}
            &nbsp;&nbsp;
            {$l.number_of_articles} : <strong><span style="color:#008000;">{$post_nb}</span></strong>
            &nbsp;&nbsp;
            {$l.published_articles} : <strong><span style="color:#008000;">{$post_nb_active}</span></strong>
            &nbsp;&nbsp;
            <span><a href="{$blog_conf_link}">{$l.configuration}</a></span>

        <br /><br />
  
{/block}