
<div class="post_search_block block blog_{$block_type}">

	<h4>{l s='Search in Blog' mod='psblog'}</h4>
        
            <form action="{$linkPosts}" id="psblogsearch" method="get">
            	
                {if !$rewrite}
                    <input type="hidden" name="fc" value="module">
                    <input type="hidden" name="module" value="psblog">
                    <input type="hidden" name="controller" value="posts">
                {/if}
                
                <div class="block_content">
                        
                {if isset($search_query_nb) && $search_query_nb > 0}
                    <p class="results">
                        <a href="{$linkPosts}?search={$search_query}">{$search_query_nb} {l s='Result for the term' mod='psblog'} <br /> "{$search_query}"</a>
                    </p>
                {/if}

                 <div class="form-group">
                    <input type="text" class="form-control" name="search" value="{if isset($search_query)}{$search_query|htmlentities:$ENT_QUOTES:'utf-8'|stripslashes}{/if}" />
                   <button class="btn btn-default button button-small" type="submit">
                       <span>{l s='go' mod='psblog'}</span>
                   </button>
                </div>
			</div>
	</form>

</div>