<?php

/**
 * Prestablog module
 *
 * @author Appside
 * @copyright Appside
 *
 */
require_once(_PS_MODULE_DIR_ . 'psblog/classes/BlogPost.php');
require_once(_PS_MODULE_DIR_ . 'psblog/classes/BlogShop.php');
require_once(_PS_MODULE_DIR_ . 'psblog/classes/BlogCategory.php');

class Psblog extends Module {

    private $_html = '';
    private $_postErrors = array();
    private static $pref = null;
    private static $blogIsAvailable = null;
    public static $default_values = array(
        "nb_max_img" => 0,
        "img_width" => 200,
        "img_list_width" => 120,
        "category_active" => 1,
        "product_active" => 1,
        "related_active" => 1,
        "product_page_related" => 1,
        "product_img_format" => "medium_default",
        "comment_active" => 1,
        "comment_moderate" => 1,
        "comment_guest" => 1,
        "comment_min_time" => 20,
        "comment_name_min_length" => 2,
        "view_display_date" => 1,
        "view_display_popin" => 1,
        "list_limit_page" => 5,
        "list_display_date" => 1,
        "file_formats" => "jpg|jpeg|png|gif|JPG|JPEG|PNG|GIF",
        "img_save_path" => "modules/psblog/uploads/",
        "rss_active" => 1,
        "rss_display" => "excerpt",
        "share_active" => 1,
        //last posts block
        "img_block_width" => 60,
        "block_limit_items" => 5,
        "block_display_date" => 1,
        "block_display_img" => 1,
        "block_articles_home" => 0,
        //category block
        "block_display_subcategories" => 0,
        "featured_block_limit" => 5,
        "featured_block_home" => 0,
        "block_left" => array("posts" => 1, "posts_position" => 1,
            "categories" => 1, "categories_position" => 2,
            "archives" => 0, "archives_position" => 0,
            "featured" => 0, "featured_position" => 0,
            "search" => 0, "search_position" => 0,
        ),
        "block_right" => array("posts" => 0, "posts_position" => 0,
            "categories" => 0, "categories_position" => 0,
            "archives" => 1, "archives_position" => 2,
            "featured" => 1, "featured_position" => 1,
            "search" => 1, "search_position" => 3,
        ),
        "block_footer" => array("posts" => 0, "posts_position" => 0,
            "categories" => 0, "categories_position" => 0,
            "archives" => 0, "archives_position" => 0,
            "featured" => 0, "featured_position" => 0,
            "search" => 0, "search_position" => 0,
        ),
    );

    public function __construct() {

        $this->bootstrap = true;

        $this->name = 'psblog';
        $this->version = '2.4';
        $this->module_key = "2eb7d51fcd2897494f1d594063c940cc";
        $this->need_instance = 0;
        $this->tab = 'front_office_features';

        parent::__construct();

        $this->checkServerConf();

        $this->author = 'APPSIDE';
        $this->displayName = $this->l('Prestablog');
        $this->description = $this->l('Blog module, articles, categories, comments and products related');
        $this->confirmUninstall = $this->l('Are you sure you want to delete all Blog posts, Blog categories and Blog comments ?');
    }

    public function install() {

       if(!parent::install() || !$this->registerHooks()) return false;

        if(!Configuration::updateValue('PSBLOG_CONF', base64_encode(serialize(self::$default_values)))) return false;

        Configuration::updateValue('PSBLOG_VERSION', $this->version);

        $this->createAdminTabs();

        require_once(dirname(__FILE__) . '/install-sql.php');

        $this->generateRewriteRules();
        BlogShop::generateSitemap();
        
        return true;
    }

    private function registerHooks(){

        if($this->registerHook('leftColumn')
            && $this->registerHook('rightColumn')
            && $this->registerHook('footer')
            && $this->registerHook('displayHome')
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('header')
            && $this->registerHook('actionHtaccessCreate')
            && $this->registerHook('displayFooterProduct')){
            return true;
        }

        return false;
    }

    private function createAdminTabs() {

        $langs = Language::getLanguages();
        $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');

        /**** create tab publications ****/

        $tab0 = new Tab();
        $tab0->class_name = "AdminBlog";
        $tab0->module = "psblog";
        $tab0->id_parent = 0;
        foreach ($langs as $l) {
            $tab0->name[$l['id_lang']] = $this->l('Blog');
        }
        $tab0->save();
        $blog_tab_id = $tab0->id;

        $tab1 = new Tab();
        $tab1->class_name = "AdminBlogPosts";
        $tab1->module = "psblog";
        $tab1->id_parent = $blog_tab_id;
        foreach ($langs as $l) {
            $tab1->name[$l['id_lang']] = $this->l('Blog posts');
        }
        $tab1->save();

        /**** create tab categories ****/
        $tab2 = new Tab();
        $tab2->class_name = "AdminBlogCategories";
        $tab2->module = "psblog";
        $tab2->id_parent = $blog_tab_id;
        foreach ($langs as $l) {
            $tab2->name[$l['id_lang']] = $this->l('Blog categories');
        }
        $tab2->save();

        /**** create tab comments ****/
        $tab3 = new Tab();
        $tab3->class_name = "AdminBlogComments";
        $tab3->module = "psblog";
        $tab3->id_parent = $blog_tab_id;
        foreach ($langs as $l) {
            $tab3->name[$l['id_lang']] = $this->l('Blog comments');
        }
        $tab3->save();
        
        /**** create tab stats ****/
        $tab4 = new Tab();
        $tab4->class_name = "AdminBlogStats";
        $tab4->module = "psblog";
        $tab4->id_parent = $blog_tab_id;
        foreach ($langs as $l) {
            $tab4->name[$l['id_lang']] = $this->l('Informations');
        }
        $tab4->save();
        
        /**** create tab conf ****/
        $tab5 = new Tab();
        $tab5->class_name = "AdminBlogConf";
        $tab5->module = "psblog";
        $tab5->id_parent = $blog_tab_id;
        foreach ($langs as $l) {
            $tab5->name[$l['id_lang']] = $this->l('Configuration');
        }
        $tab5->save();
        
        /*** RIGHTS MANAGEMENT ***/
        Db::getInstance()->Execute('DELETE FROM ' . _DB_PREFIX_ . 'access WHERE `id_tab` = ' . (int) $tab0->id . ' 
                                         OR `id_tab` = ' . (int) $tab1->id . ' OR `id_tab` = ' . (int) $tab2->id . ' 
                                         OR `id_tab` = ' . (int) $tab4->id . ' OR `id_tab` = ' . (int) $tab3->id);

        Db::getInstance()->Execute('DELETE FROM ' . _DB_PREFIX_ . 'module_access WHERE `id_module` = ' . (int) $this->id);

        $profiles = Profile::getProfiles($id_lang);

        if (count($profiles)) {
            foreach ($profiles as $p) {

                Db::getInstance()->Execute('INSERT IGNORE INTO `' . _DB_PREFIX_ . 'access`(`id_profile`,`id_tab`,`view`,`add`,`edit`,`delete`) 
                                                 VALUES (' . $p['id_profile'] . ', ' . (int) $tab0->id . ',1,1,1,1)');

                Db::getInstance()->Execute('INSERT IGNORE INTO `' . _DB_PREFIX_ . 'access`(`id_profile`,`id_tab`,`view`,`add`,`edit`,`delete`) 
                                                 VALUES (' . $p['id_profile'] . ', ' . (int) $tab1->id . ',1,1,1,1)');

                Db::getInstance()->Execute('INSERT IGNORE INTO `' . _DB_PREFIX_ . 'access`(`id_profile`,`id_tab`,`view`,`add`,`edit`,`delete`) 
                                                 VALUES (' . $p['id_profile'] . ', ' . (int) $tab2->id . ',1,1,1,1)');

                Db::getInstance()->Execute('INSERT IGNORE INTO `' . _DB_PREFIX_ . 'access`(`id_profile`,`id_tab`,`view`,`add`,`edit`,`delete`) 
                                                 VALUES (' . $p['id_profile'] . ',' . (int) $tab3->id . ',1,1,1,1)');
                
                Db::getInstance()->Execute('INSERT IGNORE INTO `' . _DB_PREFIX_ . 'access`(`id_profile`,`id_tab`,`view`,`add`,`edit`,`delete`) 
                                                 VALUES (' . $p['id_profile'] . ',' . (int) $tab4->id . ',1,1,1,1)');
                
                Db::getInstance()->Execute('INSERT IGNORE INTO `' . _DB_PREFIX_ . 'access`(`id_profile`,`id_tab`,`view`,`add`,`edit`,`delete`) 
                                                 VALUES (' . $p['id_profile'] . ',' . (int) $tab5->id . ',1,1,1,1)');
                
                Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'module_access(`id_profile`, `id_module`, `configure`, `view`)
                                                VALUES (' . $p['id_profile'] . ',' . (int) $this->id . ',1,1)');
            }
        }
    }

    public static function getRewriteCode($id_lang) {
        $category = new BlogCategory(1, $id_lang);

        if (trim($category->link_rewrite) == '') {
            $defaultCategory = new BlogCategory(1, Configuration::get('PS_LANG_DEFAULT'));
            $category->link_rewrite = $defaultCategory->link_rewrite;
        }

        return $category->link_rewrite;
    }

    public static function generateRewriteRules() {

        if (Configuration::get('PS_REWRITING_SETTINGS')) {

            $rules = "\n#start_prestablog - not remove this comment \n";

            $activeLanguages = Language::getLanguages(true);
            $default_lang_rewrite = self::getRewriteCode(Configuration::get('PS_LANG_DEFAULT'));

            $physical_uri = array();
            foreach (ShopUrl::getShopUrls() as $shop_url) {
                if (in_array($shop_url->physical_uri, $physical_uri))
                    continue;

                $rewrites = array();

                foreach ($activeLanguages as $l) {

                    $lang_rewrite = self::getRewriteCode($l['id_lang']);
                    if (trim($lang_rewrite) == '') {
                        $lang_rewrite = $default_lang_rewrite;
                    }

                    if (!in_array($lang_rewrite, $rewrites)) {
                        $rewrites[] = $lang_rewrite;

                        $rules .= "RewriteRule ^(.*)" . $lang_rewrite . "$ " . $shop_url->physical_uri . "index.php?fc=module&module=psblog&controller=posts [QSA,L] \n";
                        $rules .= "RewriteRule ^(.*)" . $lang_rewrite . "/([0-9]+)\-([a-zA-Z0-9-]*) " . $shop_url->physical_uri . "index.php?fc=module&module=psblog&controller=posts&post=$2 [QSA,L] \n";
                        $rules .= "RewriteRule ^(.*)" . $lang_rewrite . "/category/([0-9]+)\-([a-zA-Z0-9-]*) " . $shop_url->physical_uri . "index.php?fc=module&module=psblog&controller=posts&category=$2 [QSA,L] \n";
                    }
                }

                $physical_uri[] = $shop_url->physical_uri;
            }

            $rules .= "#end_prestablog \n\n";

            $path = _PS_ROOT_DIR_ . '/.htaccess';

            if (is_writable($path)) {

                $existingRules = file_get_contents($path);

                if (strpos($existingRules, "start_prestablog")) {
                    preg_match_all("/#start_prestablog(.*)#end_prestablog/Usi", $existingRules, $strings);
                    if (is_array($strings) && isset($strings[0])) {
                        $existingRules = str_replace($strings[0], "", $existingRules);
                    }
                }
                
                $handle = fopen($path, 'w');
                fwrite($handle, $rules . $existingRules);
                fclose($handle);
            }
        }
    }

    public function hookDisplayBackOfficeHeader($params){
        $css = '<style type="text/css">.icon-AdminBlog:before{ content: "\f0e6";  }</style>"';
        return $css;
    }

    public function hookActionHtaccessCreate($params) {
        $this->generateRewriteRules();
    }

    public function uninstall() {
        /**** delete AdminPsblog tab ****/
        $tab_id = Tab::getIdFromClassName("AdminBlog");
        if ($tab_id) {
            $tab = new Tab($tab_id);
            $tab->delete();
        }

        /**** delete AdminPsblogPosts tab ****/
        $tab_id = Tab::getIdFromClassName("AdminBlogPosts");
        if ($tab_id) {
            $tab = new Tab($tab_id);
            $tab->delete();
        }

        /**** delete AdminPsblogCategory tab ****/
        $tab_id = Tab::getIdFromClassName("AdminBlogCategories");
        if ($tab_id) {
            $tab = new Tab($tab_id);
            $tab->delete();
        }

        /**** delete AdminPsblogComment tab ****/
        $tab_id = Tab::getIdFromClassName("AdminBlogComments");
        if ($tab_id) {
            $tab = new Tab($tab_id);
            $tab->delete();
        }
        
        /**** delete AdminPsblogStats tab *** */
        $tab_id = Tab::getIdFromClassName("AdminBlogStats");
        if ($tab_id) {
            $tab = new Tab($tab_id);
            $tab->delete();
        }
        
        /**** delete AdminPsblogConf tab ****/
        $tab_id = Tab::getIdFromClassName("AdminBlogConf");
        if ($tab_id) {
            $tab = new Tab($tab_id);
            $tab->delete();
        }

        if (!Configuration::deleteByName('PSBLOG_CONF') OR !parent::uninstall())
            return false;

        Db::getInstance()->Execute("DROP TABLE `" . _DB_PREFIX_ . "blog_category`");
        Db::getInstance()->Execute("DROP TABLE `" . _DB_PREFIX_ . "blog_category_lang`");
        Db::getInstance()->Execute("DROP TABLE `" . _DB_PREFIX_ . "blog_post`");
        Db::getInstance()->Execute("DROP TABLE `" . _DB_PREFIX_ . "blog_post_lang`");
        Db::getInstance()->Execute("DROP TABLE `" . _DB_PREFIX_ . "blog_category_shop`");
        Db::getInstance()->Execute("DROP TABLE `" . _DB_PREFIX_ . "blog_post_shop`");
        Db::getInstance()->Execute("DROP TABLE `" . _DB_PREFIX_ . "blog_image`");
        Db::getInstance()->Execute("DROP TABLE `" . _DB_PREFIX_ . "blog_comment`");
        Db::getInstance()->Execute("DROP TABLE `" . _DB_PREFIX_ . "blog_category_relation`");
        Db::getInstance()->Execute("DROP TABLE `" . _DB_PREFIX_ . "blog_post_relation`");
        Db::getInstance()->Execute("DROP TABLE `" . _DB_PREFIX_ . "blog_visit`");
        
        return true;
    }

    public function hookHeader($params) {
        $this->context->controller->addCSS($this->_path . 'css/psblog.css', 'all');
    }

    public function hookDisplayFooterProduct($params) {

        $id_product = (int) Tools::getValue('id_product');
        if (!$id_product) return false;

        $list = BlogPost::listPosts(true, true, null, null, false, null, $id_product, null);

        $this->smarty->assign(array('post_product_list' => $list));
        return $this->display(__FILE__, 'product-footer.tpl');
    }

    /** various product page hooks * */
    public function hookExtraLeft($params) {
        return $this->hookProductTabContent($params);
    }

    public function hookExtra($params) {
        return $this->hookProductTabContent($params);
    }

    public function hookExtraRight($params) {
        return $this->hookProductTabContent($params);
    }

    public static function getPreferences() {
        if (is_null(self::$pref)) {
            $config = Configuration::get('PSBLOG_CONF');
            $options = self::$default_values;

            if ($config)
                $options = array_merge($options, unserialize(base64_decode($config)));
            self::$pref = $options;
        }
        return self::$pref;
    }

    public static function getConfValue($value) {
        $pref = self::getPreferences();
        return self::$pref[$value];
    }

    public static function blogIsAvailable() {

        $context = Context::getContext();
        $defaultCategory = new BlogCategory(1, $context->language->id);
        if (is_null(self::$blogIsAvailable)) {
            self::$blogIsAvailable = $defaultCategory->isAllowed();
        }
        return self::$blogIsAvailable;
    }

    public function checkServerConf() {
        $pref = self::getPreferences();
        $this->warning = '';
        if (!is_writable(_PS_ROOT_DIR_ . '/' . $pref['img_save_path'])) {
            $this->warning .= _PS_ROOT_DIR_ . '/' . $pref['img_save_path'] . ' ' . $this->l('must be writable') . "<br />";
        }
        if (!is_writable(_PS_ROOT_DIR_ . '/' . $pref['img_save_path'] . 'thumb/')) {
            $this->warning .= _PS_ROOT_DIR_ . '/' . $pref['img_save_path'] . 'thumb/ ' . $this->l('must be writable') . "<br />";
        }
        if (!is_writable(_PS_ROOT_DIR_ . '/' . $pref['img_save_path'] . 'list/')) {
            $this->warning .= _PS_ROOT_DIR_ . '/' . $pref['img_save_path'] . 'list/ ' . $this->l('must be writable') . "<br />";
        }
    }

    private function _postValidation() {

        $numericValues = array('img_width', 'img_list_width', 'list_limit_page', 'comment_min_time', 'comment_name_min_length',
            'img_block_width', 'block_limit_items', 'featured_block_limit');

        if (Tools::isSubmit('submitPsblog')) {

            $pref = Tools::getValue('pref');

            foreach ($numericValues as $val) {
                if (trim($pref[$val]) == '' || !is_numeric($pref[$val])) {
                    $this->_postErrors[] = $val . ' ' . $this->l(' must be a numeric value');
                }
            }
        }
    }

    private function _postProcess() {

        if (Tools::isSubmit('submitPsblog')) {
            $pref = Tools::getValue('pref');
            $old_values = self::getPreferences();

            $checkboxes = array('category_active', 'product_active', 'comment_active', 'comment_moderate',
                'comment_guest', 'list_display_date', 'view_display_date', 'related_active',
                'view_display_popin', 'rewrite_active', 'product_page_related', 'rss_active', 'share_active',
                'block_display_date', 'block_articles_home', 'block_display_img',
                'block_display_subcategories', 'featured_block_home');

            foreach ($checkboxes as $input) {
                if (!isset($pref[$input]))
                    $pref[$input] = 0;
            }

            $new_values = array_merge(self::$default_values, $pref);
            Configuration::updateValue('PSBLOG_CONF', base64_encode(serialize($new_values)));

            $this->_html .= '<div class="module_confirmation conf confirm alert alert-success">' . $this->l('Settings updated') . '</div>';

        } elseif (Tools::isSubmit('submitGenerateImg')) {

            $images = BlogPost::getAllImages();
            $save_path = _PS_ROOT_DIR_ . '/' . rtrim(self::$pref['img_save_path'], '/') . "/";

            foreach ($images as $img) {

                @unlink($save_path . 'thumb/' . $img['img_name']);
                @unlink($save_path . 'list/' . $img['img_name']);

                BlogPost::generateImageThumbs($img['id_blog_image']);
            }

            $this->_html .= '<div class="module_confirmation conf confirm alert alert-success">' . $this->l('Images regenerated') . '</div>';

        } elseif (Tools::isSubmit('submitGenerateSitemap')) {
            include_once(_PS_MODULE_DIR_ . "psblog/classes/BlogShop.php");
            BlogShop::generateSitemap();
            $this->_html .= '<div class="module_confirmation conf confirm alert alert-success">' . $this->l('Google sitemap regenerated') . '</div>';

        } elseif (Tools::isSubmit('submitUpgrade')) {

            $result = Db::getInstance()->executeS('SELECT `id_hook` FROM `'._DB_PREFIX_.'hook_module` WHERE `id_module` = '.(int)$this->id);
            foreach	($result as $row)
            {
                $this->unregisterHook((int)$row['id_hook']);
                $this->unregisterExceptions((int)$row['id_hook']);
            }

            $this->registerHooks();
            Configuration::updateValue('PSBLOG_VERSION', $this->version);

            $this->_html .= '<div class="module_confirmation conf confirm alert alert-success">' . $this->l('Module have been upgraded') . '</div>';
        }
    }

    private function _displayForm() {

        $values = (Tools::isSubmit('submitPsblog')) ? Tools::getValue('pref') : array_merge(self::$default_values, self::getPreferences());

        $this->_html .='<style tye="text/css">.blogForm input{ display:inline !important; }</style>';

        $this->_html .='<form action="' . $_SERVER['REQUEST_URI'] . '" class="defaultForm form-horizontal blogForm" method="post">
		<div class="panel">
		<h3>' . $this->l('General') . '</h3>

        <div class="form-group">
            <label class="control-label col-lg-3">' . $this->l('Active categories') . '</label>
            <div class="col-lg-9">
            <input type="checkbox" name="pref[category_active]" value="1" ' . ((isset($values['category_active']) && $values['category_active'] == '1') ? 'checked' : '') . ' />
            </div>
		</div>

		<div class="form-group">
            <label class="control-label col-lg-3">' . $this->l('Active products') . '</label>
            <div class="col-lg-9">
                <input type="checkbox" name="pref[product_active]" value="1" ' . ((isset($values['product_active']) && $values['product_active'] == '1') ? 'checked' : '') . ' />
            </div>
		</div>

		<div class="form-group">
            <label class="control-label col-lg-3">' . $this->l('Active comments') . '</label>
            <div class="col-lg-9">
            <input type="checkbox" name="pref[comment_active]" value="1" ' . ((isset($values['comment_active']) && $values['comment_active'] == '1') ? 'checked' : '') . ' />
            </div>
		</div>

		<div class="form-group">
            <label class="control-label col-lg-3">' . $this->l('Enable related articles') . '</label>
             <div class="col-lg-9">
                <input type="checkbox" name="pref[related_active]" value="1" ' . ((isset($values['related_active']) && $values['related_active'] == '1') ? 'checked' : '') . ' />
            </div>
		</div>

		<div class="form-group">
            <label class="control-label col-lg-3">' . $this->l('Enable RSS feed') . '</label>
            <div class="col-lg-9">
            <input type="checkbox" name="pref[rss_active]" value="1" ' . ((isset($values['rss_active']) && $values['rss_active'] == '1') ? 'checked' : '') . '/>
            </div>
        </div>';

        $this->_html .= '</div>';

        $this->_html .= '<div class="panel">
                <h3>' . $this->l('List settings') . '</h3>

                <div class="form-group">
                    <label class="control-label col-lg-3">' . $this->l('Number of articles per page') . '</label>
                    <div class="col-lg-9">
                        <input type="text" class="fixed-width-xs" name="pref[list_limit_page]" value="' . $values['list_limit_page'] . '" size="3" />
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">' . $this->l('Display date') . '</label>
                    <div class="col-lg-9">
                    <input type="checkbox" name="pref[list_display_date]" value="1" ' . ((isset($values['list_display_date']) && $values['list_display_date'] == '1') ? 'checked' : '') . '/>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">' . $this->l('Image width in lists') . '</label>
                    <div class="col-lg-9">
                            <input type="text" class="fixed-width-xs" name="pref[img_list_width]" value="' . $values['img_list_width'] . '" size="3" /> px
                    </div>
                </div>';

        $this->_html .= '</div>';

        $this->_html .= '<div class="panel">
                        <h3>' . $this->l('View settings') . '</h3>
                        <div class="form-group">
                            <label class="control-label col-lg-3">' . $this->l('Image width in article detail') . '</label>
                            <div class="col-lg-9">
                                <input type="text" class="fixed-width-xs" name="pref[img_width]" value="' . $values['img_width'] . '" size="3" /> px
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-lg-3">' . $this->l('Enable popin for images') . '</label>
                                <div class="col-lg-9">
                                <input type="checkbox" name="pref[view_display_popin]" value="1" ' . ((isset($values['view_display_popin']) && $values['view_display_popin'] == '1') ? 'checked' : '') . '/>
                            </div>
                        </div>

                    <div class="form-group">
                        <label class="control-label col-lg-3">' . $this->l('Display date') . '</label>
                        <div class="col-lg-9">
                        <input type="checkbox" name="pref[view_display_date]" value="1" ' . ((isset($values['view_display_date']) && $values['view_display_date'] == '1') ? 'checked' : '') . '/>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-lg-3">' . $this->l('Active Addthis') . '</label>
                        <div class="col-lg-9">
                        <input type="checkbox" name="pref[share_active]" value="1" ' . ((isset($values['share_active']) && $values['share_active'] == '1') ? 'checked' : '') . ' />
                        </div>
				    </div>

				</div>';

        $this->_html .= '<div class="panel">
                        <h3>' . $this->l('Related products settings') . '</h3>
                        <div class="form-group">
                            <label class="control-label col-lg-3">' . $this->l('Enable related articles in product page') . '</label>
                            <div class="col-lg-9">
                            <input type="checkbox" name="pref[product_page_related]" value="1" ' . ((isset($values['product_page_related']) && $values['product_page_related'] == '1') ? 'checked' : '') . '/>
                            </div>
                        </div>';

        $formats = ImageType::getImagesTypes();

        $this->_html .= '<div class="form-group">
                          <label class="control-label col-lg-3">' . $this->l('Product image format') . '</label>
                          <div class="col-lg-9">
                          <select name="pref[product_img_format]" class="fixed-width-lg">';
        foreach ($formats as $f) {
            $this->_html .= '<option value="'.$f['name'].'" '.($values['product_img_format'] == $f['name'] ? "selected" : "") . '>'.$f['name'].'&nbsp;</option>';
        }
        $this->_html .= '</select>
                           </div>';

        $this->_html .= '</div>
                        </div>';

        $this->_html .= '<div class="panel">
                                        
                        <h3>' . $this->l('Comments settings') . '</h3>

                        <div class="form-group">
                            <label class="control-label col-lg-3">' . $this->l('All comments must be validated by an employee') . '</label>
                            <div class="col-lg-9">
                            <input type="checkbox" name="pref[comment_moderate]" value="1" ' . ((isset($values['comment_moderate']) && $values['comment_moderate'] == '1') ? 'checked' : '') . '/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-lg-3">' . $this->l('Allow guest comments') . '</label>
                            <div class="col-lg-9">
                            <input type="checkbox" name="pref[comment_guest]" value="1" ' . ((isset($values['comment_guest']) && $values['comment_guest'] == '1') ? 'checked' : '') . '/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-lg-3">' . $this->l('Minimum time between 2 comments from the same user') . '</label>
                            <div class="col-lg-9">
                            <input name="pref[comment_min_time]" class="fixed-width-xs" type="text" class="text" value="' . $values['comment_min_time'] . '" style="width: 40px; text-align: right;" /> ' . $this->l('seconds') . '
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-lg-3">' . $this->l('Minimum length of user name') . '</label>
                            <div class="col-lg-9">
                                    <input name="pref[comment_name_min_length]" class="fixed-width-xs" type="text" class="text" value="' . $values['comment_name_min_length'] . '" style="width: 40px; text-align: right;" /> ' . $this->l('characters') . '
                            </div>
                        </div>';

        $this->_html .= '</div>';

        $this->_html .= '<div class="panel">
                            
                        <h3>' . $this->l('RSS settings') . '</h3>
                        <div class="form-group">
                        <label class="control-label col-lg-3">' . $this->l('Post field used for content') . '</label>
                        <div class="col-lg-9">
                        <select name="pref[rss_display]" class="fixed-width-lg">
                                <option value="excerpt" ' . ($values['rss_display'] == "excerpt" ? "selected" : "") . '>' . $this->l('Excerpt') . ' &nbsp;</option>
                                <option value="content" ' . ($values['rss_display'] == "content" ? "selected" : "") . '>' . $this->l('Content') . ' &nbsp;</option>
                        </select>
                        </div>
                        </div>';

        $this->_html .= '</div>';

        $this->_html .= '<div class="panel"><h3>' . $this->l('Block last posts settings') . '</h3>

				<div class="form-group">
                    <label class="control-label col-lg-3">' . $this->l('Number of posts to display') . '</label>
                    <div class="col-lg-9">
                    <input type="text" class="fixed-width-xs" name="pref[block_limit_items]" value="' . $values['block_limit_items'] . '" size="3" />
                    </div>
                </div>

				<div class="form-group">
                    <label class="control-label col-lg-3">' . $this->l('Display date') . '</label>
                    <div class="col-lg-9">
                    <input type="checkbox" name="pref[block_display_date]" value="1" ' . ((isset($values['block_display_date']) && $values['block_display_date'] == '1') ? 'checked' : '') . '/>
                    </div>
                </div>

				<div class="form-group">
                    <label class="control-label col-lg-3">' . $this->l('Homepage, display block in center') . '</label>
                    <div class="col-lg-9">
                    <input type="checkbox" name="pref[block_articles_home]" value="1" ' . ((isset($values['block_articles_home']) && $values['block_articles_home'] == '1') ? 'checked' : '') . '/>&nbsp; ' . $this->l('instead of column') . '
                    </div>
				</div>

				<div class="form-group">
                    <label class="control-label col-lg-3">' . $this->l('Display images') . '</label>
                    <div class="col-lg-9">
                    <input type="checkbox" name="pref[block_display_img]" value="1" ' . ((isset($values['block_display_img']) && $values['block_display_img'] == '1') ? 'checked' : '') . '/>
                    </div>
                </div>

				<div class="form-group">
                    <label class="control-label col-lg-3">' . $this->l('Image width') . '</label>
                    <div class="col-lg-9">
                        <input type="text" class="fixed-width-xs" name="pref[img_block_width]" value="' . $values['img_block_width'] . '" size="3" /> px
                    </div>
				</div>
				</div>';


        $this->_html .= '<div class="panel">
                        <h3>' . $this->l('Prestablog categories block settings') . '</h3>

                        <div class="form-group">
                            <label class="control-label col-lg-3">' . $this->l('Display subcategories') . '</label>
                            <div class="col-lg-9">
                            <input type="checkbox" name="pref[block_display_subcategories]" value="1" ' . ((isset($values['block_display_subcategories']) && $values['block_display_subcategories'] == '1') ? 'checked' : '') . '/>
                            </div>
                        </div>
				    </div>';

        $this->_html .= '<div class="panel">
                <h3>' . $this->l('Prestablog featured block settings') . '</h3>

		        <div class="form-group">
                    <label class="control-label col-lg-3">' . $this->l('Number of posts to display') . '</label>
                    <div class="col-lg-9">
                    <input type="text" class="fixed-width-xs" name="pref[featured_block_limit]"  size="3" value="' . $values['featured_block_limit'] . '" />
                    </div>
                </div>

				<div class="form-group">
                    <label class="control-label col-lg-3">' . $this->l('Homepage, display block in center') . '</label>
                    <div class="col-lg-9">
                    <input type="checkbox" name="pref[featured_block_home]" value="1" ' . ((isset($values['featured_block_home']) && $values['featured_block_home'] == '1') ? 'checked' : '') . '/>&nbsp; ' . $this->l('instead of column') . '
                    </div>
				</div>

				</div>';

        $blocks = array('block_left' => 'Left column','block_right' => 'Right column','block_footer' => 'Footer');

        foreach ($blocks as $key => $legend) {

            $this->_html .= '<div class="panel">
                                <h3>' . $legend . '</h3>';

            $this->_html .= ' <div class="form-group">
                                <label class="control-label col-lg-3">' . $this->l('Block last articles') . '</label>
                                    <div class="col-lg-9">

                                        <input type="radio" id="' . $key . '_posts_on" value="1" name="pref[' . $key . '][posts]" ' . ((isset($values[$key]['posts']) && $values[$key]['posts'] == '1') ? 'checked' : '') . ' >
                                        <label class="t" for="' . $key . '_posts_on"> <img src="../img/admin/enabled.gif" alt="' . $this->l('Enabled') . '" title="' . $this->l('Enabled') . '" /></label>

                                        &nbsp; <input type="radio" id="' . $key . '_posts_off" value="0" name="pref[' . $key . '][posts]" ' . ((isset($values[$key]['posts']) && $values[$key]['posts'] == '0') ? 'checked' : '') . ' >
                                        <label class="t" for="' . $key . '_posts_off"> <img src="../img/admin/disabled.gif" alt="' . $this->l('Disabled') . '" title="' . $this->l('Disabled') . '" /></label>

                                        &nbsp; ' . $this->l('Position') . ' <input type="text" class="fixed-width-xs" name="pref[' . $key . '][posts_position]" size="2" value="' . $values[$key]['posts_position'] . '" >
                                    </div>
								</div>';

            $this->_html .= '<div class="form-group">
                                <label class="control-label col-lg-3">' . $this->l('Block categories') . '</label>
								<div class="col-lg-9">
								
									<input type="radio" id="' . $key . '_categories_on" value="1" name="pref[' . $key . '][categories]" ' . ((isset($values[$key]['categories']) && $values[$key]['categories'] == '1') ? 'checked' : '') . ' >
									<label class="t" for="' . $key . '_categories_on"> <img src="../img/admin/enabled.gif" alt="' . $this->l('Enabled') . '" title="' . $this->l('Enabled') . '" /></label>
									
									&nbsp; <input type="radio" id="' . $key . '_categories_off" value="0" name="pref[' . $key . '][categories]" ' . ((isset($values[$key]['categories']) && $values[$key]['categories'] == '0') ? 'checked' : '') . ' >
									<label class="t" for="' . $key . '_categories_off"> <img src="../img/admin/disabled.gif" alt="' . $this->l('Disabled') . '" title="' . $this->l('Disabled') . '" /></label>
																
									&nbsp; ' . $this->l('Position') . ' <input type="text" class="fixed-width-xs" name="pref[' . $key . '][categories_position]" size="2" value="' . $values[$key]['categories_position'] . '" >

							    	</div>
							    </div>';

            $this->_html .= '<div class="form-group">
                                <label class="control-label col-lg-3">' . $this->l('Block Featured') . '</label>
								<div class="col-lg-9">
								
									<input type="radio" id="' . $key . '_featured_on" value="1" name="pref[' . $key . '][featured]" ' . ((isset($values[$key]['featured']) && $values[$key]['featured'] == '1') ? 'checked' : '') . ' >
									<label class="t" for="' . $key . '_featured_on"> <img src="../img/admin/enabled.gif" alt="' . $this->l('Enabled') . '" title="' . $this->l('Enabled') . '" /></label>
									
									&nbsp; <input type="radio" id="' . $key . '_featured_off" value="0" name="pref[' . $key . '][featured]" ' . ((isset($values[$key]['featured']) && $values[$key]['featured'] == '0') ? 'checked' : '') . ' >
									<label class="t" for="' . $key . '_featured_off"> <img src="../img/admin/disabled.gif" alt="' . $this->l('Disabled') . '" title="' . $this->l('Disabled') . '" /></label>
																
									&nbsp; ' . $this->l('Position') . ' <input type="text" class="fixed-width-xs" name="pref[' . $key . '][featured_position]" size="2" value="' . $values[$key]['featured_position'] . '" >

								    </div>
								</div>';

            $this->_html .= '<div class="form-group">
                                <label class="control-label col-lg-3">' . $this->l('Block Archives') . '</label>
								<div class="col-lg-9">
								
									<input type="radio" id="' . $key . '_archives_on" value="1" name="pref[' . $key . '][archives]" ' . ((isset($values[$key]['archives']) && $values[$key]['archives'] == '1') ? 'checked' : '') . ' >
									<label class="t" for="' . $key . '_archives_on"> <img src="../img/admin/enabled.gif" alt="' . $this->l('Enabled') . '" title="' . $this->l('Enabled') . '" /></label>
									
									&nbsp; <input type="radio" id="' . $key . '_archives_off" value="0" name="pref[' . $key . '][archives]" ' . ((isset($values[$key]['archives']) && $values[$key]['archives'] == '0') ? 'checked' : '') . ' >
									<label class="t" for="' . $key . '_archives_off"> <img src="../img/admin/disabled.gif" alt="' . $this->l('Disabled') . '" title="' . $this->l('Disabled') . '" /></label>
																
									&nbsp; ' . $this->l('Position') . ' <input type="text" class="fixed-width-xs" name="pref[' . $key . '][archives_position]" size="2" value="' . $values[$key]['archives_position'] . '" >

								    </div>
								</div>';

            $this->_html .= '<div class="form-group">
                                <label class="control-label col-lg-3">' . $this->l('Block Search') . '</label>
								<div class="col-lg-9">
								
									<input type="radio" id="' . $key . '_search_on" value="1" name="pref[' . $key . '][search]" ' . ((isset($values[$key]['search']) && $values[$key]['search'] == '1') ? 'checked' : '') . ' >
									<label class="t" for="' . $key . '_search_on"> <img src="../img/admin/enabled.gif" alt="' . $this->l('Enabled') . '" title="' . $this->l('Enabled') . '" /></label>
									
									&nbsp; <input type="radio" id="' . $key . '_search_off" value="0" name="pref[' . $key . '][search]" ' . ((isset($values[$key]['search']) && $values[$key]['search'] == '0') ? 'checked' : '') . ' >
									<label class="t" for="' . $key . '_search_off"> <img src="../img/admin/disabled.gif" alt="' . $this->l('Disabled') . '" title="' . $this->l('Disabled') . '" /></label>
																
									&nbsp; ' . $this->l('Position') . ' <input type="text" class="fixed-width-xs" name="pref[' . $key . '][search_position]" size="2" value="' . $values[$key]['search_position'] . '" >

								    </div>

								</div>';

            $this->_html .= '</div>';
        }

        $this->_html .= '<div class="panel">
                            <div class="panel-footer">
							<button class="btn btn-default pull-right" name="submitPsblog" value="1" type="submit">
							<i class="process-icon-save"></i>' . $this->l('Update settings') . '
							</button>
							</div>
					    </div>';

        $this->_html .= '<div class="panel">
                        <h3>' . $this->l('Tools') . '</h3>
                         <p>
                         <button name="submitGenerateImg" class="btn btn-default" value="1" type="submit"><i class="icon-cogs"></i> &nbsp; '.$this->l('Regenerate all blog images').'</button>
                         ' . $this->l('Useful if you change the images sizes') . '
                         </p>';

        if (self::isInstalled('gsitemap')) {

            $this->_html .= '<p>
                            <button name="submitGenerateSitemap" class="btn btn-default" type="submit" value="1">
                            <i class="icon-cogs"></i> &nbsp; ' . $this->l('Regenerate Google sitemap') . '
                            </button>
                            &nbsp; <a href="' . _PS_BASE_URL_ . __PS_BASE_URI__ . 'modules/psblog/sitemap-blog.xml" target="_blank">' . _PS_BASE_URL_ . __PS_BASE_URI__ . 'modules/psblog/sitemap-blog.xml</a>
                           </p>';
        }

        $current_version = Configuration::get('PSBLOG_VERSION');

        if($current_version < $this->version){
            $this->_html .= '<p>
                            <button name="submitUpgrade" class="btn btn-default" type="submit" value="1">
                            <i class="icon-cogs"></i> &nbsp; ' . $this->l('Upgrade to '.$this->version) . '
                            </button>
                            </p>';
        }

        $this->_html .= '</div>

                    <div class="panel">
						<p>
						' . $this->l('If url rewriting doesn\'t works, check that this above lines exist in your current .htaccess file, if no, add it manually on top of your .htaccess file') . ': <br /><br />
						  <strong>';
        $default_lang_rewrite = self::getRewriteCode(Configuration::get('PS_LANG_DEFAULT'));
        $physical_uri = array();
        foreach (ShopUrl::getShopUrls() as $shop_url) {
            if (in_array($shop_url->physical_uri, $physical_uri))
                continue;

            $activeLanguages = Language::getLanguages(true);

            $rewrites = array();
            foreach ($activeLanguages as $l) {

                $lang_rewrite = self::getRewriteCode($l['id_lang']);
                if (trim($lang_rewrite) == '') {
                    $lang_rewrite = $default_lang_rewrite;
                }
                if (!in_array($lang_rewrite, $rewrites)) {
                    $rewrites[] = $lang_rewrite;

                    $this->_html .= "RewriteRule ^(.*)" . $lang_rewrite . "$ " . $shop_url->physical_uri . "index.php?fc=module&module=psblog&controller=posts [QSA,L] <br />";
                    $this->_html .= "RewriteRule ^(.*)" . $lang_rewrite . "/([0-9]+)\-([a-zA-Z0-9-]*) " . $shop_url->physical_uri . "index.php?fc=module&module=psblog&controller=posts&post=$2 [QSA,L] <br />";
                    $this->_html .= "RewriteRule ^(.*)" . $lang_rewrite . "/category/([0-9]+)\-([a-zA-Z0-9-]*) " . $shop_url->physical_uri . "index.php?fc=module&module=psblog&controller=posts&category=$2 [QSA,L] <br />";
                    $this->_html .= "<br />";
                    $physical_uri[] = $shop_url->physical_uri;
                }
            }
        }

        $this->_html .= '</strong>
                                </p>
                              </div>';

        $this->_html .= '<div class="panel">
                            <p>
                            ' . $this->l('To declare blog sitemap xml, add this line at the end of your robots.txt file') . ': <br /><br />
							  <strong>
								Sitemap ' . _PS_BASE_URL_ . __PS_BASE_URI__ . 'modules/psblog/sitemap-blog.xml
							 </strong>
                            </p>
                        </div>';

        $this->_html .= '</form>';
    }

    public static function getRewriteConf() {
        self::getPreferences();
        return self::$pref['rewrite_active'];
    }

    public function getContent() {

        $this->checkServerConf();

        if ($this->warning != '') {
            $this->_html .= '<div style="width:680px;" class="warning bold">' . $this->warning . '</div>';
        }

        $this->_html .= '<h2>' . $this->l('Prestablog settings') . '</h2>';

        $this->_html .= '<p>' . $this->l('If you want to add articles, you must go to the Blog tab on the navigation menu') . '</p>';

        $this->_postValidation();

        if (!isset($this->_postErrors) || !sizeof($this->_postErrors)) {
            $this->_postProcess();
        } else {
            foreach ($this->_postErrors AS $err)
                $this->_html .= '<div class="alert error">' . $err . '</div>';
        }

        $this->_displayForm();
        return $this->_html;
    }

    public function hookRightColumn($params) {
        return $this->getBlocks('block_right', $params);
    }

    public function hookLeftColumn($params) {
        return $this->getBlocks('block_left', $params);
    }

    public function hookFooter($params) {
        return $this->getBlocks('block_footer', $params);
    }

    protected function getBlocks($block_type, $params) {

        if (self::$blogIsAvailable) return '';

        $pref = self::getPreferences();
        $output = '';
        $blocks = array(
            array('name' => 'search',
                'order' => $pref[$block_type]['search_position'],
                'active' => (int) $pref[$block_type]['search'],
                'call' => 'blockSearch'),
            array('name' => 'categories',
                'order' => $pref[$block_type]['categories_position'],
                'active' => (int) $pref[$block_type]['categories'],
                'call' => 'blockCategories'),
            array('name' => 'posts',
                'order' => $pref[$block_type]['posts_position'],
                'active' => (int) $pref[$block_type]['posts'],
                'call' => 'blockLastPosts'),
            array('name' => 'archives',
                'order' => $pref[$block_type]['archives_position'],
                'active' => (int) $pref[$block_type]['archives'],
                'call' => 'blockArchives'),
            array('name' => 'featured',
                'order' => $pref[$block_type]['featured_position'],
                'active' => (int) $pref[$block_type]['featured'],
                'call' => 'blockFeatured'));

        $hookBlocks = array();
        foreach ($blocks as $b)
            if ($b['active'])
                $hookBlocks[] = $b;

        $orders = array();
        foreach ($hookBlocks as $key => $b)
            $orders[$key] = $b['order'];

        array_multisort($orders, SORT_ASC, $hookBlocks);

        $this->smarty->assign('block_type', $block_type);

        foreach ($hookBlocks as $b) {
            $output .= call_user_func(array($this, $b['call']), $params, $block_type, $this, $b['order']);
        }

        return $output;
    }

    public function hookDisplayHome($params) {
        if ((int) self::getConfValue('block_articles_home') == 1)
            return $this->blockLastPosts($params, 'home');

        if ((int) self::getConfValue('featured_block_home') == 1)
            return $this->blockFeatured($params, 'home');
    }

    protected function blockLastPosts($params, $block_type, $pos = null) {

        $pref = self::getPreferences();

        $is_home = Tools::getValue('controller') == 'index' ? true : false;

        $img_path = rtrim($pref['img_save_path'], '/') . '/';
        $list = BlogPost::listPosts(true, true, 0, intval($pref['block_limit_items']));

        $this->smarty->assign(array('posts_list' => $list,
            'posts_conf' => $pref,
            'linkPosts' => BlogPost::linkList(),
            'posts_title' => $this->l('Last blog articles'),
            'posts_img_path' => _PS_BASE_URL_ . __PS_BASE_URI__ . $img_path,
            'posts_rss_url' => BlogPost::linkRss()));

        if ($is_home && $pref['block_articles_home'] == 1 && ($block_type == 'block_left' || $block_type == 'block_right')) {
            return '';
        } elseif ($is_home && $pref['block_articles_home'] == 1 && $block_type == 'home') {
            return $this->display(__FILE__, 'blockpostshome.tpl');
        } elseif ($block_type == 'block_footer') {
            return $this->display(__FILE__, 'blockposts_footer.tpl');
        } else {
            return $this->display(__FILE__, 'blockposts.tpl');
        }
    }

    protected function blockCategories($params, $block_type, $pos = null) {

        if (!$this->getConfValue('category_active')) return '';

        $pref = self::getPreferences();
        $list = BlogCategory::listCategories(true, true, true, false, array(1,2));

        if (Tools::getValue('controller') == "posts" && Tools::getIsset('category')) {
            $current_category_id = (int) Tools::getValue('category');

            $category = new BlogCategory($current_category_id, $this->context->language->id);

            if (!is_null($category->id_blog_category_parent) && !empty($category->id_blog_category_parent)) {
                $current_category_id = $category->id_blog_category_parent;
            }

            $this->smarty->assign('blog_category', $current_category_id);
        }

        $this->smarty->assign(array('post_categories' => $list, 'blog_conf' => $pref));

        if ($block_type == 'block_footer') {
            return $this->display(__FILE__, 'blockcategories_footer.tpl');
        } else {
            return $this->display(__FILE__, 'blockcategories.tpl');
        }
    }

    protected function blockSearch($params, $block_type, $pos = null) {

        $pref = self::getPreferences();

        if (Tools::getValue('search') != '') {
            $search = Tools::getValue('search');
            $search_nb = BlogPost::searchPosts($search, true, true, true);

            $this->smarty->assign('search_query', $search);
            $this->smarty->assign('search_query_nb', $search_nb);
        }

        $rewrite = (Configuration::get('PS_REWRITING_SETTINGS')) ? true : false;
        $this->smarty->assign('rewrite', $rewrite);
        $this->smarty->assign('ENT_QUOTES', ENT_QUOTES);
        $this->smarty->assign('linkPosts', BlogPost::linkList());

        return $this->display(__FILE__, 'blocksearch.tpl');
    }

    protected function blockFeatured($params, $block_type, $pos = null) {

        $pref = self::getPreferences();

        $is_home = Tools::getValue('controller') == 'index' ? true : false;
        $img_path = rtrim($pref['img_save_path'], '/') . '/';

        $category = new BlogCategory(2, $this->context->language->id);
        $list = $category->getPosts(true, true, 0, intval($pref['featured_block_limit']));

        $this->smarty->assign(array('posts_list' => $list,
            'posts_conf' => $pref,
            'posts_title' => $category->name,
            'linkPosts' => BlogPost::linkList(),
            'posts_img_path' => _PS_BASE_URL_ . __PS_BASE_URI__ . $img_path,
            'posts_rss_url' => BlogPost::linkRss()));

        if ($is_home && $pref['featured_block_home'] == 1 && ($block_type == 'block_left' || $block_type == 'block_right')) {
            return '';
        } elseif ($is_home && $pref['featured_block_home'] == 1 && $block_type == 'home') {
            return $this->display(__FILE__, 'blockpostshome.tpl');
        } elseif ($block_type == 'block_footer') {
            return $this->display(__FILE__, 'blockposts_footer.tpl');
        } else {
            return $this->display(__FILE__, 'blockposts.tpl');
        }
    }

    protected function blockArchives($params, $block_type, $pos = null) {

        $pref = self::getPreferences();

        $archives = BlogPost::getArchives();

        $langMonths = array(
            1 => $this->l('January'),
            2 => $this->l('February'),
            3 => $this->l('March'),
            4 => $this->l('April'),
            5 => $this->l('May'),
            6 => $this->l('June'),
            7 => $this->l('July'),
            8 => $this->l('August'),
            9 => $this->l('September'),
            10 => $this->l('October'),
            11 => $this->l('November'),
            12 => $this->l('December'));

        $list = array();

        foreach ($archives as $val) {

            if (!array_key_exists('year', $val) || !array_key_exists('month', $val)) continue;

            $year = $val['year'];
            $month = isset($langMonths[$val['month']]) ? $langMonths[$val['month']] : null;

            if (is_null($month) || is_null($year)) continue;

            if (isset($list[$year]))
                $list[$year]['nb'] += (int) $val['nb'];
            else
                $list[$year]['nb'] = (int) $val['nb'];

            if ($year == date('Y')) {
                $list[$year]['months'][$val['month']]['name'] = $month;
                $list[$year]['months'][$val['month']]['nb'] = $val['nb'];
            }
        }

        $linkPosts = (Configuration::get('PS_REWRITING_SETTINGS')) ? BlogPost::linkList() . '?' : BlogPost::linkList() . '&';
        $this->smarty->assign(array('blog_archives' => $list, 'blog_conf' => $pref, 'posts_rss_url' => BlogPost::linkRss()));
        $this->smarty->assign('linkPosts', $linkPosts);
        return $this->display(__FILE__, 'blockarchives.tpl');
    }
    
    public static function getBlogConfigurationLink(){
        
        $context = Context::getContext();
        $tokenModules = Tools::getAdminToken('AdminModules' . (int) (Tab::getIdFromClassName('AdminModules')) . (int) $context->employee->id);
        $blog_conf = 'index.php?controller=AdminModules&configure=psblog&module_name=psblog&token=' . $tokenModules;

        return $blog_conf;
    }

}