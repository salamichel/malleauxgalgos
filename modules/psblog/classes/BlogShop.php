<?php

require_once(_PS_CLASS_DIR_.'shop/Shop.php');

class BlogShop extends ShopCore
{
        protected static $blog_shop_tables = array('blog_post' => array('type' => 'shop'),
                                                   'blog_category' => array('type' => 'shop'));
        
	public static function addBlogAssoTables()
	{
            foreach (self::$blog_shop_tables as $key => $val){
		ShopCore::$asso_tables[$key] = $val;
            }
	}
        
        public static function addShopAssociation($table, $alias, $context = null)
	{
            if(is_null($context))
                $context = Context::getContext();

            $table_alias = $table.'_shop';
            if (strpos($table, '.') !== false)
                    list($table_alias, $table) = explode('.', $table);

            if (!array_key_exists($table,self::$blog_shop_tables)) return;

            $sql =  ' INNER JOIN '._DB_PREFIX_.$table.'_shop '.$table_alias.' ON ('.$table_alias.'.id_'.$table.' = '.$alias.'.id_'.$table;

            if (isset($context->shop->id))
                $sql .= ' AND '.$table_alias.'.id_shop = '.(int)$context->shop->id;
            elseif (Shop::checkIdShopDefault($table))
                $sql .= ' AND '.$table_alias.'.id_shop = '.$alias.'.id_shop_default';
            else
                $sql .= ' AND '.$table_alias.'.id_shop IN ('.implode(', ', Shop::getContextListShopID()).')';
                
             $sql .= ')';
             
            return $sql;
	}
        
        public static function generateSitemap(){
            	
                require_once(_PS_MODULE_DIR_.'psblog/classes/BlogCategory.php');
                require_once(_PS_MODULE_DIR_.'psblog/classes/BlogPost.php');
                
                $filename = _PS_MODULE_DIR_.'psblog/sitemap-blog.xml';
                
                if(!is_writable($filename)){ 
                    return FALSE;
                }
                
		$sql = 'SELECT s.id_shop, su.domain, su.domain_ssl, CONCAT(su.physical_uri, su.virtual_uri) as uri
				FROM '._DB_PREFIX_.'shop s
				INNER JOIN '._DB_PREFIX_.'shop_url su ON s.id_shop = su.id_shop AND su.main = 1
				WHERE s.active = 1 AND s.deleted = 0 AND su.active = 1';
                
		if (!$result = Db::getInstance()->executeS($sql)) return false;
                
                $xml = simplexml_load_file($filename);
                $sxe = new SimpleXMLElement($xml->asXML());
                unset($sxe->url);
                $sxe->asXML($filename); 
                
                foreach ($result as $row)
                {             
                    $shopContext = Context::getContext()->cloneContext();
                    $shopContext->shop = new Shop($row['id_shop']);
                    $defaultLang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
                    $shopContext->language = $defaultLang;
					 
                    $defaultCategory = new BlogCategory(1,$defaultLang->id);
                    $defaultCategoryLangs = $defaultCategory->getLangs();

                    $shopPosts = BlogPost::listPosts($shopContext,true);
                    $shopCategories = BlogCategory::listCategories($shopContext,true, false, false,array(1));
					
                    foreach($defaultCategoryLangs as $l){
                     	
			$shopContext->language = new Language($l['id_lang']);
                        $url = $sxe->addChild('url');
                
                        $listlink = str_replace('&','&amp;', BlogPost::linkList(null,$shopContext));
                        $url->addChild('loc',$listlink);
                        
                        $url->addChild('priority','0.7');
                        $url->addChild('changefreq','weekly');
                    }
               	
                    foreach($shopPosts as $post){
						
			$blogpost = new BlogPost($post['id_blog_post']);
                        $langs = $blogpost->getLangs();
						
                        foreach($langs as $l){

                            $shopContext->language = new Language($l['id_lang']);
                            $link_rewrite = isset($blogpost->link_rewrite[$l['id_lang']]) ? $blogpost->link_rewrite[$l['id_lang']] : $blogpost->link_rewrite[$defaultLang->id];

                            $postlink = str_replace('&','&amp;', BlogPost::linkPost($post['id_blog_post'],$link_rewrite,$shopContext));

                            $url = $sxe->addChild('url');
                            $url->addChild('loc', $postlink);
                            $url->addChild('priority','0.6');
                            $url->addChild('changefreq','monthly');

                        }
                    }
                    
                    foreach($shopCategories as $cat){
						
                        $blogcat = new BlogCategory($cat['id_blog_category']);
                        $langs = $blogcat->getLangs();

                        foreach($langs as $l){

                            $shopContext->language = new Language($l['id_lang']);
                            $link_rewrite = isset($blogcat->link_rewrite[$l['id_lang']]) ? $blogcat->link_rewrite[$l['id_lang']] : $blogcat->link_rewrite[$defaultLang->id];

                            $catlink = str_replace('&','&amp;', BlogCategory::linkCategory($cat['id_blog_category'],$link_rewrite,null,$shopContext));
                            $url = $sxe->addChild('url');
                            $url->addChild('loc', $catlink);
                            $url->addChild('priority','0.6');
                            $url->addChild('changefreq','monthly');
                        }
                    }
                    
                    $sxe->asXML($filename); 
                }   
        }

}