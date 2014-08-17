<?php

if(!defined('_MYSQL_ENGINE_')){
	define(_MYSQL_ENGINE_,'MyISAM');
}


Db::getInstance()->Execute("CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."blog_category` (
                            `id_blog_category` int(10) unsigned NOT NULL AUTO_INCREMENT,
                            `active` tinyint(1) unsigned DEFAULT '1',
                            `position` int(10) unsigned DEFAULT NULL,
                            `id_blog_category_parent` int(10) NOT NULL DEFAULT '0',
                            PRIMARY KEY (`id_blog_category`)
                          ) ENGINE="._MYSQL_ENGINE_."  DEFAULT CHARSET=utf8;");

Db::getInstance()->Execute("CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."blog_category_lang` (
                            `id_blog_category` int(10) unsigned NOT NULL,
                            `id_lang` int(11) unsigned NOT NULL,
                            `name` varchar(255) CHARACTER SET utf8 NOT NULL,
                            `description` text CHARACTER SET utf8 NOT NULL,
                            `link_rewrite` varchar(128) CHARACTER SET utf8 NOT NULL,
                            `meta_keywords` varchar(255) CHARACTER SET utf8 NOT NULL,
                            `meta_description` text CHARACTER SET utf8 NOT NULL,
                            KEY `id_blog_category` (`id_blog_category`,`id_lang`)
                          ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8;");
													  
Db::getInstance()->Execute("CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."blog_post` (
                            `id_blog_post` int(10) unsigned NOT NULL AUTO_INCREMENT,
                            `date_on` date DEFAULT NULL,
                            `status` enum('published','drafted','suspended') DEFAULT NULL,
                            `allow_comments` tinyint(1) DEFAULT '0',
                            PRIMARY KEY (`id_blog_post`)
                          ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8;");

Db::getInstance()->Execute("CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."blog_post_lang` (
                            `id_blog_post` int(10) unsigned NOT NULL,
                            `id_lang` int(11) unsigned NOT NULL,
                            `title` varchar(255) NOT NULL,
                            `content` text,
                            `excerpt` text,
                            `link_rewrite` varchar(128) DEFAULT NULL,
                            `meta_keywords` varchar(255) DEFAULT NULL,
                            `meta_description` text,
                            KEY `id_lang` (`id_lang`),
                            KEY `id_blog_post` (`id_blog_post`)
                          ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8;");
					  
Db::getInstance()->Execute("CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."blog_category_shop` (
                            `id_blog_category` int(10) unsigned NOT NULL,
                            `id_shop` int(10) unsigned NOT NULL,
                            PRIMARY KEY (`id_blog_category`,`id_shop`)
                          ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8;");

Db::getInstance()->Execute("CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."blog_comment` (
                            `id_blog_comment` int(10) unsigned NOT NULL AUTO_INCREMENT,
                            `id_blog_post` int(10) unsigned NOT NULL,
                            `id_customer` int(10) unsigned DEFAULT NULL,
                            `id_guest` int(10) unsigned DEFAULT NULL,
                            `id_lang` int(10) unsigned NOT NULL,
                            `id_shop` int(10) unsigned NOT NULL,
                            `customer_name` varchar(128) NOT NULL,
                            `content` text NOT NULL,
                            `date_add` datetime NOT NULL,
                            `active` tinyint(1) unsigned NOT NULL DEFAULT '0',
                            PRIMARY KEY (`id_blog_comment`),
                            KEY `id_blog_post` (`id_blog_post`),
                            KEY `id_customer` (`id_customer`)
                          ) ENGINE="._MYSQL_ENGINE_."  DEFAULT CHARSET=utf8;");

Db::getInstance()->Execute("CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."blog_image` (
                            `id_blog_image` int(10) NOT NULL AUTO_INCREMENT,
                            `id_blog_post` int(10) NOT NULL,
                            `img_name` varchar(255) NOT NULL,
                            `default` tinyint(1) NOT NULL DEFAULT '0',
                            `position` int(10) unsigned NOT NULL DEFAULT '0',
                            PRIMARY KEY (`id_blog_image`)
                          ) ENGINE="._MYSQL_ENGINE_."  DEFAULT CHARSET=utf8;");

Db::getInstance()->Execute("CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."blog_post_relation` (
                            `id_blog_post_relation` int(10) unsigned NOT NULL AUTO_INCREMENT,
                            `id_blog_post` int(10) unsigned NOT NULL,
                            `key` varchar(24) NOT NULL,
                            `value` text NOT NULL,
                            PRIMARY KEY (`id_blog_post_relation`),
                            KEY `id_blog_post` (`id_blog_post`)
                          ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8");

Db::getInstance()->Execute("CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."blog_category_relation` (
                            `id_blog_category_relation` int(10) unsigned NOT NULL AUTO_INCREMENT,
                            `id_blog_category` int(10) unsigned NOT NULL,
                            `key` varchar(24) NOT NULL,
                            `value` text NOT NULL,
                            PRIMARY KEY (`id_blog_category_relation`),
                            KEY `id_blog_category` (`id_blog_category`)
                          ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8");

Db::getInstance()->Execute("CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."blog_post_shop` (
                            `id_blog_post` int(10) unsigned NOT NULL,
                            `id_shop` int(10) unsigned NOT NULL,
                            PRIMARY KEY (`id_blog_post`,`id_shop`)
                          ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8;");

Db::getInstance()->Execute("CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."blog_visit` (
                            `id_blog_visit` int(10) unsigned NOT NULL AUTO_INCREMENT,
                            `type_page` varchar(32) NOT NULL,
                            `id_page` int(10) unsigned NOT NULL,
                            `ip_address` bigint(20) unsigned NOT NULL,
                            `counter` int(10) unsigned NOT NULL,
                            `date_add` datetime NOT NULL,
                            PRIMARY KEY (`id_blog_visit`),
                            KEY `id_page` (`id_page`)
                          ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8");

//default categories

$categories = array();
$categories[] =array('name' => array('en' => 'Blog','fr' => 'Blog','es' => 'Blog'),
                                    'link' => array('en' => 'blog', 'fr' => 'blog', 'es' => 'blog'));
								 
$categories[] = array('name' => array('en' => 'Blog featured posts','fr' => 'Articles à la une','es' => 'Artículos destacados'),
                                    'link' => array('en' => 'blog-featured-posts', 'fr' => 'article-a-la-une', 'es' => 'articulos-destacados'));

$languages = Language::getLanguages();
$shops = Shop::getShops();
$customer_groups = Group::getGroups(Context::getContext()->language->id);

$i = 1;
foreach($categories as $value){
	
    $category_groups = array();
    $category_langs = array();

    Db::getInstance()->Execute("DELETE FROM `"._DB_PREFIX_."blog_category` WHERE `id_blog_category` = ".$i);
	Db::getInstance()->Execute("INSERT INTO `"._DB_PREFIX_."blog_category`(`id_blog_category`,`active`,`position`,`id_blog_category_parent`) VALUES (".$i.",1,0,0)");
	Db::getInstance()->Execute("DELETE FROM `"._DB_PREFIX_."blog_category_lang` WHERE `id_blog_category` = ".$i);

	foreach($languages as $l){
		
		$title = array_key_exists($l['iso_code'],$value['name']) ? $value['name'][$l['iso_code']] : $value['name']['en'];
		$link = array_key_exists($l['iso_code'],$value['link']) ? $value['link'][$l['iso_code']] : $value['link']['en'];
	
		Db::getInstance()->Execute("INSERT INTO `"._DB_PREFIX_."blog_category_lang`(`id_blog_category`,`id_lang`,`name`,`description`,`link_rewrite`,`meta_keywords`,`meta_description`) 
										VALUES(".$i.",".$l['id_lang'].",'".$title."','".$title."', '".$link."', '".$title."','".$title."')");		
		$category_langs[] = $l['id_lang'];
	}
		
	BlogCategoryRelation::saveRelation($i,'lang',$category_langs);
	
	Db::getInstance()->Execute("DELETE FROM `"._DB_PREFIX_."blog_category_shop` WHERE `id_blog_category` = ".$i);
	foreach($shops as $shop){
		Db::getInstance()->Execute("INSERT INTO `"._DB_PREFIX_."blog_category_shop`(`id_shop`,`id_blog_category`) VALUES(".$shop['id_shop'].",".$i.")");
	}
	
	foreach($customer_groups as $group)
		$category_groups[] = $group['id_group']; 
		
	BlogCategoryRelation::saveRelation($i,'group',$category_groups);
	
	$i++;
}

?>