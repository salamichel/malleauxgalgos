<?php

/**
  * Prestablog RSS feed
  * @category front
  *
  * @author Appside 
  * @copyright Appside
  * @link appside.net
  */

include(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../init.php');

require_once(_PS_MODULE_DIR_."psblog/psblog.php");
require_once(_PS_MODULE_DIR_."psblog/classes/BlogPost.php");
require_once(_PS_MODULE_DIR_."psblog/classes/BlogCategory.php");

$conf = Psblog::getPreferences();
$context = Context::getContext();

$id_lang = Tools::getValue('id_lang') ? (int) Tools::getValue('id_lang') : 0;
$id_category = Tools::getValue('id_category') ? (int)(Tools::getValue('id_category'))  : null;

if($id_lang){	
	$context = Context::getContext();
	$context->language = new Language($id_lang);
}

if(!$conf['rss_active']){
    header('HTTP/1.1 404 Not Found');
    header('Status: 404 Not Found');
    header('Location: '._PS_BASE_URL_.__PS_BASE_URI__.'404.php');
}

header("Content-Type:text/xml; charset=utf-8");
echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
?>
<rss version="2.0">
	<channel>
	
		<title><![CDATA[<?php echo Configuration::get('PS_SHOP_NAME') ?>]]></title>
		<link><?php echo _PS_BASE_URL_.__PS_BASE_URI__; ?></link>
		<mail><?php echo Configuration::get('PS_SHOP_EMAIL') ?></mail>
		<generator>PrestaShop</generator>
		<language><?php echo Language::getIsoById((int) $context->language->id); ?></language>
		<image>
			<title><![CDATA[<?php echo $context->shop->name; ?> Blog]]></title>
			<url><?php echo _PS_BASE_URL_.__PS_BASE_URI__.'img/logo.jpg'; ?></url>
			<link><?php echo _PS_BASE_URL_.__PS_BASE_URI__; ?></link>
		</image>
<?php 
        $list = BlogPost::listPosts($context,true,null,null,false,$id_category);
                
	foreach ($list AS $item)
	{	
                $post = new BlogPost($item['id_blog_post'],$context->language->id);
                
		echo "\t\t<item>\n";
		echo "\t\t\t<title><![CDATA[".$post->title."]]></title>\n";
		echo "\t\t\t<description>";	
		
		$img_relative = rtrim($conf['img_save_path'],'/')."/";
		$cdata = true;
		
		if($post->default_img['img_name'] != '' && is_file(_PS_ROOT_DIR_.'/'.$img_relative.'list/'.$post->default_img['img_name'])){
                    echo '<![CDATA[<img src="'._PS_BASE_URL_.__PS_BASE_URI__.$img_relative.'list/'.$post->default_img['img_name'].'" title="'.$post->title.'" alt="thumb" />';
                    $cdata = false;
		}
		
		if ($cdata) echo "<![CDATA[";
		
		if($conf['rss_display'] == 'content'){
			$desc = $post->content;
		}else{
			$desc = "<p>".$post->excerpt."</p>";
		}
		
		echo $desc."]]></description>\n";
		
		echo "\t\t\t<link><![CDATA[".htmlspecialchars($item['link'])."]]></link>\n";
		echo "<pubDate>".$post->date_on."</pubDate>";
		echo "\t\t</item>\n";
	}
?>		
	</channel>
</rss>