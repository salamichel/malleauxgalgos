<?php

/**
 * PsblogCategory class
 * Prestablog module
 * @category classes
 *
 * @author Appside
 * @copyright Appside
 *
 */

require_once(_PS_MODULE_DIR_ . 'psblog/psblog.php');

class BlogVisit extends ObjectModel {
    
    public $type_page; //category, post
    public $id_page;
    public $ip_address;
    public $counter;
    public $date_add;
    
    public static $definition = array(
        'table' => 'blog_visit',
        'primary' => 'id_blog_visit',
		'multilang' => false,
		'multishop' => false,
        'fields' => array(
            
            'type_page' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true,'size' => 32),
            'id_page' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt','required' => true),
			'ip_address' => array('type' => self::TYPE_STRING),
            'counter' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
        )
    );
        
    public static function setNewConnexion($type_page,$id_page){
        $ip_adress = str_replace('.','',Tools::getRemoteAddr());
        $query = 'SELECT * FROM `' . _DB_PREFIX_ . 'blog_visit` v 
                  WHERE v.`type_page` = "'.$type_page.'" 
                  AND v.`id_page` = '.$id_page.' AND v.`ip_address` = "'.$ip_adress.'"';
        
        $result = Db::getInstance()->getRow($query);
        
        if($result){
            $id_blog_visit = $result['id_blog_visit'];
            Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'blog_visit` SET `counter` = `counter`+1 
                                        WHERE `id_blog_visit` = '.$id_blog_visit);
        }else{
            
            $visit = new BlogVisit();
            $visit->id_page = $id_page;
            $visit->type_page = $type_page;
            $visit->ip_address = $ip_adress;
            $visit->counter = 1;
            $visit->add();
        
        }   
    }
    
    public static function getPopularPosts($limit = 10, $unique = false){
        
        $id_lang = Context::getContext()->language->id;
        
        if($unique)
            $select = 'COUNT(v.`id_page`)';
        else
            $select = 'SUM(v.`counter`)';
        
         $query = 'SELECT '.$select.' as nb_visit, pl.`title`, pl.`id_blog_post` 
                   FROM `' . _DB_PREFIX_ . 'blog_visit` v 
                   INNER JOIN `' . _DB_PREFIX_ . 'blog_post_lang` pl ON (v.`id_page` = pl.`id_blog_post` AND v.`type_page` = "post") 
                   WHERE pl.id_lang = '.$id_lang.'
                   GROUP BY v.`id_page`
                   ORDER BY nb_visit DESC 
                   LIMIT 0,'.$limit;

         return Db::getInstance()->ExecuteS($query);
    }
    
    public static function getPopularCategories($limit = 10, $unique = false){
        
        $id_lang = Context::getContext()->language->id;
        
        if($unique)
            $select = 'COUNT(v.`id_page`)';
        else
            $select = 'SUM(v.`counter`)';
        
         $query = 'SELECT '.$select.' as nb_visit, cl.`name`, cl.`id_blog_category` 
                   FROM `' . _DB_PREFIX_ . 'blog_visit` v 
                   INNER JOIN `' . _DB_PREFIX_ . 'blog_category_lang` cl ON (v.`id_page` = cl.`id_blog_category` AND v.`type_page` = "category") 
                   WHERE cl.id_lang = '.$id_lang.'
                   GROUP BY v.`id_page`
                   ORDER BY nb_visit DESC 
                   LIMIT 0,'.$limit;

         return Db::getInstance()->ExecuteS($query);
    }
    
    
}