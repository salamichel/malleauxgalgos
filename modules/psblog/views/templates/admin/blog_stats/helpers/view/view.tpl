{extends file="helpers/view/view.tpl"}

{block name="override_tpl"}

<div class="row">

       <div class="panel">
        <h3>{l s='Blog informations' mod='psblog'}</h3>
        <p><strong>{l s='URL blog main page' mod='psblog'} :</strong> <a href="{$blog_url}">{$blog_url}</a></p>
        <p><strong><a href="{$blog_conf}">{l s='View blog configuration page' mod='psblog'}</a></strong></p>
       </div>

       <br />

    <div class="panel">
        <h3>{l s='Blog posts' mod='psblog'}</h3>
                <p><strong>{l s='Number of posts' mod='psblog'} :</strong> {$post.nb}</p>
                
                <p><strong>{l s='Published posts' mod='psblog'} :</strong> {$post.nb_active}</p>
                
                <h4>{l s='Popular posts' mod='psblog'}</h4>
                
                <div style="float:left;">
                <p><strong>{l s='Visits' mod='psblog'}</strong></p>
                
                  {if $post.popular|@count > 0}
                    <table class="table" cellspacing="0" cellpadding="0" style="width:550px;">
                    <thead>
                    <tr>
                    <th width="50px">{l s='Id' mod='psblog'}</th>
                    <th width="50px">{l s='Visits' mod='psblog'}</th>
                    <th>{l s='Name' mod='psblog'}</th>
                    </tr>
                    </thead>
                    <tbody>
                    {foreach $post.popular item=article name=postPopular}
                    <tr>
                    <td>{$article.id_blog_post}</td>
                    <td>{$article.nb_visit}</td>
                    <td>{$article.title}</td>
                    </tr>
                    {/foreach}
                    </tbody>
                    </table>
                  {else}
                      {l s='No data' mod='psblog'}
                  {/if}
                 
                </div>
                
                <div style="float:left; margin-left:50px;">
                    
                    <p><strong>{l s='Unique visitors' mod='psblog'}</strong></p>
                    
                   {if $post.popular_unique|@count > 0}
                    <table class="table" cellspacing="0" cellpadding="0" style="width:550px;">
                        <thead>
                        <tr>
                        <th width="50px">{l s='Id' mod='psblog'}</th>
                        <th width="50px">{l s='Visits' mod='psblog'}</th>
                        <th>{l s='Name' mod='psblog'}</th>
                        </tr>
                        </thead>
                        <tbody>
                        {foreach $post.popular_unique item=article name=postPopularUnique}
                        <tr>
                        <td>{$article.id_blog_post}</td>
                        <td>{$article.nb_visit}</td>
                        <td>{$article.title}</td>
                        </tr>
                        {/foreach}
                        </tbody>
                    </table>
                 {else}
                    {l s='No data' mod='psblog'}
                 {/if}
                 
                </div>
                
                <div style="clear:both"></div>
                
                <h4>{l s='More commented posts' mod='psblog'}</h4>
                    
                {if $post.commented|@count > 0}
                    <table class="table" cellspacing="0" cellpadding="0" style="width:550px;">
                        <thead>
                        <tr>
                        <th width="50px">{l s='Id' mod='psblog'}</th>
                        <th width="50px">{l s='Number' mod='psblog'}</th>
                        <th>{l s='Title' mod='psblog'}</th>
                        </tr>
                        </thead>

                        <tbody>
                        {foreach $post.commented item=article name=postCommented}
                        <tr>
                        <td>{$article.id_blog_post}</td>
                        <td>{$article.nb_comments}</td>
                        <td>{$article.title}</td>
                        </tr>
                        {/foreach}
                        </tbody>
                    </table>
                {else}
                    {l s='No data' mod='psblog'}
                 {/if}
    </div>
	<br />

        <div class="panel">
        <h3>{l s='Blog categories' mod='psblog'}</h3>
        <p><strong>{l s='Number of categories' mod='psblog'} :</strong> {$category.nb}</p>
        <p><strong>{l s='Published categories' mod='psblog'} :</strong> {$category.nb_active}</p>
        
        <h4>{l s='Popular categories' mod='psblog'}</h4>
        
        <div style="float:left;">
            
            <p><strong>{l s='Visits' mod='psblog'}</strong></p>
            
           {if $category.popular|@count > 0}
            <table class="table" cellspacing="0" cellpadding="0" style="width:550px;">
                <thead>
                <tr>
                    <th width="50px">{l s='Id' mod='psblog'}</th>
                    <th width="50px">{l s='Visits' mod='psblog'}</th>
                    <th>{l s='Name' mod='psblog'}</th>
                </tr>
                </thead>
                <tbody>
                {foreach $category.popular item=cat name=catPopular}
                <tr>
                    <td>{$cat.id_blog_category}</td>
                    <td>{$cat.nb_visit}</td>
                    <td>{$cat.name}</td>
                </tr>
                {/foreach}
                </tbody>
            </table>
            {else}
               {l s='No data' mod='psblog'}
             {/if}

           </div>

           <div style="float:left; margin-left:50px;">

                 <p><strong>{l s='Unique visitors' mod='psblog'}</strong></p>
               
                 {if $category.popular_unique|@count > 0}
                 <table class="table" cellspacing="0" cellpadding="0" style="width:550px;">
                     <thead>
                     <tr>
                     <th width="50px">{l s='Id' mod='psblog'}</th>
                     <th width="50px">{l s='Visits' mod='psblog'}</th>
                     <th>{l s='Name' mod='psblog'}</th>
                     </tr>
                     </thead>
                     <tbody>
                     {foreach $category.popular_unique item=cat name=catPopularUnique}
                     <tr>
                     <td>{$cat.id_blog_category}</td>
                     <td>{$cat.nb_visit}</td>
                     <td>{$cat.name}</td>
                     </tr>
                     {/foreach}
                     </tbody>
                 </table>
                {else}
                    {l s='No data' mod='psblog'}
                {/if}
             
             </div>

             <div style="clear:both"></div>

        </div>
        
        <br />

    <div class="panel">
        <h3>{l s='Blog comments' mod='psblog'}</h3>
        <p><strong>{l s='Number of comments' mod='psblog'} :</strong> {$comment.comment_nb}</p>
        <p><strong>{l s='Published comments' mod='psblog'} :</strong> {$comment.comment_nb_active}</p>
        <p><strong>{l s='Refused comments' mod='psblog'} :</strong> {$comment.comment_nb_inactive}</p>
        <p><strong>{l s='Comments awaiting moderation' mod='psblog'} :</strong> {$comment.comment_nb_wait}</p>
    </div>

</div>

{/block}