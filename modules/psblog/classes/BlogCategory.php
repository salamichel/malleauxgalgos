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
require_once(_PS_MODULE_DIR_ . 'psblog/classes/BlogShop.php');
require_once(_PS_MODULE_DIR_ . 'psblog/classes/BlogCategoryRelation.php');
require_once(_PS_MODULE_DIR_ . 'psblog/psblog.php');

class BlogCategory extends ObjectModel {

    public $name;
    public $description;
    public $link_rewrite;
    public $meta_description;
    public $meta_keywords;
    public $active = 1;
    public $position;
    public $parent;
    public $id_blog_category_parent = 0;
    public static $definition = array(
        'table' => 'blog_category',
        'primary' => 'id_blog_category',
        'multilang' => true,
        'multishop' => true,
        'fields' => array(
            'active' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'meta_description' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 255, 'lang' => true),
            'meta_keywords' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 255, 'lang' => true),
            'link_rewrite' => array('type' => self::TYPE_STRING, 'validate' => 'isLinkRewrite', 'size' => 128, 'lang' => true),
            'position' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'id_blog_category_parent' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => false),
            'name' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255, 'lang' => true),
            'description' => array('type' => self::TYPE_HTML, 'validate' => 'isString', 'size' => 3999999999999, 'lang' => true)
        )
    );

    public function __construct($id = null, $id_lang = null, $id_shop = null) {
        BlogShop::addBlogAssoTables();
        parent::__construct($id, $id_lang, $id_shop);
    }

    public function add($autodate = true, $nullValues = false) {
        return parent::add($autodate, true);
    }

    public function delete() {

        BlogCategoryRelation::cleanCategoryRelations($this->id);
        BlogPostRelation::cleanPostRelationKey('category',$this->id);

        return parent::delete();
    }

    public function isAllowed() {

        $context = Context::getContext();

        $id_lang = $context->language->id;
        $id_shop = $context->shop->id;

        $category_langs = $this->getLangs(true);
        $category_groups = $this->getGroups();
        $current_groups = FrontController::getCurrentCustomerGroups();

        $group_found = false;
        if (empty($current_groups))
            $current_groups[] = (int) Group::getCurrent()->id;
        foreach ($current_groups as $group) {
            if (in_array($group, $category_groups)) {
                $group_found = true;
            }
        }

        if (!$this->active || !$group_found || !in_array($id_lang, $category_langs) || !$this->isAssociatedToShop($id_shop))
            return false;

        return true;
    }

    public static function getSubCategories($category_id, $currentContext, $active = true) {
        $context = null;
        if ($currentContext instanceof Context) {
            $context = $currentContext;
        } elseif (is_bool($currentContext) && $currentContext === true) {
            $context = Context::getContext();
        }

        $id_lang = (is_null($context) || !isset($context->language)) ? Context::getContext()->language->id : $context->language->id;

        $query = 'SELECT DISTINCT(c.id_blog_category), cl.name, cl.link_rewrite, c.position, c.id_blog_category_parent, 
                    (SELECT GROUP_CONCAT(l.iso_code) FROM ' . _DB_PREFIX_ . 'blog_category_relation crl 
                                   LEFT JOIN `' . _DB_PREFIX_ . 'lang` l ON (crl.`key` = "lang" AND l.`id_lang`= crl.`value`)
                                   WHERE  crl.id_blog_category = c.id_blog_category) as iso_code
						
                    FROM  ' . _DB_PREFIX_ . 'blog_category c ';

        $query .= ' LEFT JOIN `' . _DB_PREFIX_ . 'blog_category_lang` cl ON cl.`id_blog_category` = c.`id_blog_category` ';

        if ($context) {
            $query .= BlogShop::addShopAssociation('blog_category', 'c', $context);

            $groups = FrontController::getCurrentCustomerGroups();
            $sql_groups = (count($groups) ? 'IN (' . implode(',', $groups) . ')' : '=' . (int) Group::getCurrent()->id);

            $query .= ' INNER JOIN `' . _DB_PREFIX_ . 'blog_category_relation` cr2 ON (cr2.`id_blog_category` = c.`id_blog_category` AND cr2.`key` = "group" AND cr2.`value` ' . $sql_groups . ') ';
        }

        if ($context && isset($context->language))
            $query .= ' INNER JOIN `' . _DB_PREFIX_ . 'blog_category_relation` cr ON (cr.`id_blog_category` = c.`id_blog_category` AND cr.`key` = "lang" AND cr.`value` = "' . $context->language->id . '") ';

        $query .= ' WHERE cl.`id_lang` = ' . (int) $id_lang . ' AND c.id_blog_category != 1 ';

        if ($active)
            $query .= ' AND c.`active` = 1';

        $query .= ' AND c.`id_blog_category_parent` =  ' . $category_id . ' ';
        $query .= ' GROUP BY c.`id_blog_category` ORDER BY c.`position` ASC, cl.`name` ASC';
        $result = Db::getInstance()->ExecuteS($query);

        if (!$result)
            return array();

        $i = 0;
        foreach ($result as $val) {
            $result[$i]['link'] = BlogCategory::linkCategory($val['id_blog_category'], $val['link_rewrite']);
            $i++;
        }

        return $result;
    }

    public static function listCategories($currentContext, $active = true, $onlyParents = false, $count = false, $exclude_defaults = null) {
        
        if(!is_null($exclude_defaults)){
            $exclude_defaults = (array) $exclude_defaults;
        }
  
        $context = null;
        if ($currentContext instanceof Context) {
            $context = $currentContext;
        } elseif (is_bool($currentContext) && $currentContext === true) {
            $context = Context::getContext();
        }
        
        $id_lang = (is_null($context) || !isset($context->language)) ? Context::getContext()->language->id : $context->language->id;
        
        if($count){
            $select = ' COUNT(DISTINCT(c.`id_blog_category`)) as nb ';
        }else{
            $select = ' DISTINCT(c.id_blog_category), c.id_blog_category, cl.name, cl.link_rewrite, c.position, c.id_blog_category_parent,
                        (SELECT GROUP_CONCAT(l.iso_code) FROM ' . _DB_PREFIX_ . 'blog_category_relation crl 
                        LEFT JOIN `' . _DB_PREFIX_ . 'lang` l ON (crl.`key` = "lang" AND l.`id_lang`= crl.`value`)
                        WHERE  crl.id_blog_category = c.id_blog_category) as iso_code ';
        }
        
        $query = 'SELECT '.$select.' FROM  ' . _DB_PREFIX_ . 'blog_category c ';

        $query .= ' LEFT JOIN `' . _DB_PREFIX_ . 'blog_category_lang` cl ON cl.`id_blog_category` = c.`id_blog_category` ';

        if ($context) {
            $query .= BlogShop::addShopAssociation('blog_category', 'c', $context);

            $groups = FrontController::getCurrentCustomerGroups();
            $sql_groups = (count($groups) ? 'IN (' . implode(',', $groups) . ')' : '=' . (int) Group::getCurrent()->id);
            $query .= ' INNER JOIN `' . _DB_PREFIX_ . 'blog_category_relation` cr2 ON (cr2.`id_blog_category` = c.`id_blog_category` AND cr2.`key` = "group" AND cr2.`value` ' . $sql_groups . ') ';
        
            if (isset($context->language))
                $query .= ' INNER JOIN `' . _DB_PREFIX_ . 'blog_category_relation` cr ON (cr.`id_blog_category` = c.`id_blog_category` AND cr.`key` = "lang" AND cr.`value` = "' . $context->language->id . '") ';
        }
   
        $query .= ' WHERE cl.`id_lang` = ' . (int) $id_lang;

        if (!is_null($exclude_defaults) && is_array($exclude_defaults))
            $query .= '  AND c.id_blog_category NOT IN ('.implode(',',$exclude_defaults).') ';

        if ($active)
            $query .= ' AND c.`active` = 1';
        
        if ($onlyParents)
            $query .= ' AND (c.`id_blog_category_parent` IS NULL OR c.`id_blog_category_parent` = 0) ';

        if ($count) {

            $result = Db::getInstance()->getRow($query);
            return $result['nb'];

        }else{
            
            $query .= ' GROUP BY c.`id_blog_category` ORDER BY c.`position` ASC, cl.`name` ASC ';

            $result = Db::getInstance()->ExecuteS($query);
            if (!$result) return array();

            $i = 0;
            foreach ($result as $val) {
                $result[$i]['link'] = BlogCategory::linkCategory($val['id_blog_category'], $val['link_rewrite']);
                $result[$i]['subcategories'] = BlogCategory::getSubCategories($val['id_blog_category'], $currentContext, true);
                $i++;
            }

            return $result;
        }
    }

    public function getPosts($checkContext = true, $publish = true, $start = 0, $limit = 5) {
        return BlogPost::listPosts($checkContext, $publish, $start, $limit, false, $this->id);
    }

    public function nbPosts($checkContext = true, $publish = true) {
        return BlogPost::listPosts($checkContext, $publish, null, null, true, $this->id);
    }

    public static function getParents($id_lang = null, $except_id = null) {

        if (is_null($id_lang))
            $id_lang = Context::getContext()->language->id;

        $query = "SELECT * FROM `" . _DB_PREFIX_ . "blog_category` c
                    LEFT JOIN `" . _DB_PREFIX_ . "blog_category_lang` l ON l.`id_blog_category` = c.`id_blog_category`
                    WHERE l.`id_lang` = " . (int) $id_lang . " AND c.`id_blog_category` != 1 AND c.`id_blog_category` != 2  
                    AND (c.`id_blog_category_parent` IS NULL OR c.`id_blog_category_parent` = 0)";

        if (!is_null($except_id) && is_numeric($except_id)) {
            $query .= ' AND c.`id_blog_category` != ' . (int) $except_id;
        }

        $result = Db::getInstance()->ExecuteS($query);

        $list = array();
        foreach ($result as $category) {
            $list[] = array('name' => $category['name'], 'id' => $category['id_blog_category']);
        }
        return $list;
    }

    public function getLangs($onlyIds = false) {

        $query = 'SELECT l.* FROM `' . _DB_PREFIX_ . 'blog_category_relation` cr
					INNER JOIN `' . _DB_PREFIX_ . 'lang` l ON (cr.`key` = "lang" AND l.`id_lang`= cr.`value`)
                    WHERE cr.`id_blog_category` = ' . intval($this->id);
        $result = Db::getInstance()->ExecuteS($query);

        if ($result && $onlyIds) {
            $resultIds = array();
            foreach ($result as $group)
                $resultIds[] = $group['id_lang'];

            return $resultIds;
        }

        return $result;
    }

    public function isAssociatedToLang($id_lang = null) {

        if ($id_lang === null)
            $id_lang = Context::getContext()->language->id;

        $query = 'SELECT l.* FROM `' . _DB_PREFIX_ . 'blog_category_relation` cr
		  INNER JOIN `' . _DB_PREFIX_ . 'lang` l ON (cr.`key` = "lang" AND l.`id_lang`= cr.`value`)
                  WHERE cr.`id_blog_category` = ' . intval($this->id) . ' AND cr.`value` = "' . $id_lang . '"';

        return (bool) Db::getInstance()->getValue($query);
    }

    public function getGroups() {
        return BlogCategoryRelation::getRelation($this->id, 'group');
    }

    public static function linkCategory($category_id, $rewrite = null, $p = null, $context = null, $params = array()) {

        if (is_null($context) || !($context instanceof Context)) {
            $context = Context::getContext();
        }

        $languages = Language::getLanguages(true, $context->shop->id);
        $shop_url = $context->shop->getBaseURL();
        
        if(!is_null($p)) $params[] = 'p=' . $p;
        
        if (Configuration::get('PS_REWRITING_SETTINGS')) {

            $lang_rewrite = Psblog::getRewriteCode($context->language->id);

            $iso = (isset($context->language) && count($languages) > 1) ? $context->language->iso_code . '/' : '';
            
            $param_str = count($params) ? '?'.implode('&',$params) : '';
            
            if ($category_id == 1) {
                return $shop_url . $iso . $lang_rewrite . $param_str;
            } else {

                if (is_null($rewrite) || trim($rewrite) == '') {
                    $category = new BlogCategory($category_id, (int) Configuration::get('PS_LANG_DEFAULT'));
                    $rewrite = $category->link_rewrite;
                }
                
                return $shop_url . $iso . $lang_rewrite . '/category/' . $category_id . '-' . $rewrite . $param_str;
            }
            
        } else {

            $id_lang = (isset($context->language) && count($languages) > 1) ? '&id_lang=' . $context->language->id : '';
            
            $param_str = count($params) ? '&'.implode('&',$params) : '';
            
            if ($category_id == 1) {
                return $shop_url . 'index.php?fc=module&module=psblog&controller=posts' . $id_lang . $param_str;
            } else {
                return $shop_url . 'index.php?fc=module&module=psblog&controller=posts&category=' . $category_id . $id_lang . $param_str;
            }
        }
    }

}

?>
