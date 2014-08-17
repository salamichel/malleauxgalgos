
{if $posts_list && $posts_list|@count > 0}
 <section class="posts_block blog_{$block_type} footer-block col-xs-12 col-sm-3">
	<h4>{if $posts_conf.rss_active}<a href="{$posts_rss_url}" title="RSS"><img src="{$modules_dir}psblog/img/rss.png" alt="RSS" /></a>{/if}
        &nbsp; <a href="{$linkPosts}" title="{$posts_title}">{$posts_title}</a></h4>
        
	<div class="block_content toggle-footer">
	
            <ul class="tree dynamized">
                {foreach from=$posts_list item=post name=posts_list}

                    <li {if $smarty.foreach.posts_list.last} class="last_item" {/if}>
						<!--
                        {if $post.default_img && $posts_conf.block_display_img}
                        <div class="post_img" style="width:{$posts_conf.img_block_width}px; ">
                            <a href="{$post.link}"><img src="{$posts_img_path}list/{$post.default_img_name}" width="{$posts_conf.img_block_width}" /></a>
                        </div>
                        {/if}
						-->
                       
                        <h5><a href="{$post.link}">{$post.title}</a></h5>
                        {if $posts_conf.block_display_date && $post.date_on != "" && $post.date_on != "0000-00-00"}
                        	<span>{dateFormat date=$post.date_on|escape:'html':'UTF-8' full=0}</span>
                        {/if}
                    </li>
                {/foreach}
            </ul>
	</div>
</section>
{/if}