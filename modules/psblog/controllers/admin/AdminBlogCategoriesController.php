<?php

/**
 * Prestablog tab for admin panel
 * @category admin
 *
 * @author Appside 
 * @copyright Appside
 * @link appside.net
 * 
 */
require_once(_PS_MODULE_DIR_ . 'psblog/psblog.php');
require_once(_PS_MODULE_DIR_ . 'psblog/classes/BlogCategory.php');
require_once(_PS_MODULE_DIR_ . 'psblog/classes/BlogPost.php');
require_once(_PS_MODULE_DIR_ . 'psblog/classes/BlogShop.php');
require_once(_PS_MODULE_DIR_ . 'psblog/classes/BlogCategoryRelation.php');

class AdminBlogCategoriesController extends ModuleAdminController {

    public function __construct() {

        $this->bootstrap = true;

        $this->table = 'blog_category';
        $this->className = 'BlogCategory';
        $this->module = 'psblog';
        $this->lang = true;

        if (Tools::getIsset('id_' . $this->table) || Tools::getIsset('add' . $this->table)) {
            $this->multishop_context = Shop::CONTEXT_ALL;
        }

        $this->allow_export = true;

        $this->fields_list = array(
            'id_blog_category' => array('title' => $this->l('ID'), 'align' => 'center', 'width' => 30),
            'active' => array('title' => $this->l('Active'), 'active' => 'status', 'align' => 'center', 'type' => 'bool', 'orderby' => false, 'width' => 60));

        $nb_lang = count(Language::getLanguages(true));

        if ($nb_lang > 1) {
            $this->fields_list['iso_code'] = array('title' => $this->l('Language'), 'width' => 20, 'search' => false);
        }
        $this->fields_list['position'] = array('title' => $this->l('Position'), 'width' => 10, 'search' => false);
        $this->fields_list['nbposts'] = array('title' => $this->l('Posts'), 'width' => 10, 'search' => false);

        BlogShop::addBlogAssoTables();

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Delete selected items?')
            )
        );
        $this->specificConfirmDelete = false;

        parent::__construct();
    }

    public function initPageHeaderToolbar()
    {
        $this->initToolbar();
        if(empty($this->display))
            $this->page_header_toolbar_btn['new_blog_category'] = array(
                'href' => self::$currentIndex.'&addblog_category&token='.$this->token,
                'desc' => $this->l('Add new category', null, null, false),
                'icon' => 'process-icon-new'
            );

        parent::initPageHeaderToolbar();
    }

    public function setMedia()
    {
        parent::setMedia();
        $this->addjQueryPlugin(array('tagify'));
        $this->addJS(array(_MODULE_DIR_ . $this->module->name . '/js/psblog.js'));
    }

    public function renderList()
    {
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->addRowActionSkipList('delete', array(1, 2));

        $nb_lang = count(Language::getLanguages(true));

        $this->_join .= ' LEFT JOIN `' . _DB_PREFIX_ . 'blog_category` bc2 ON (bc2.`id_blog_category` = a.`id_blog_category_parent`)';

        if ($nb_lang > 1) {
            $this->_select .= ' (SELECT GROUP_CONCAT(l.iso_code) FROM ' . _DB_PREFIX_ . 'blog_category_relation cr
								LEFT JOIN `' . _DB_PREFIX_ . 'lang` l ON (cr.`key` = "lang" AND l.`id_lang`= cr.`value`)
								WHERE  cr.id_blog_category = a.id_blog_category) as `iso_code`,';
        }

        $this->_select .= ' COUNT(pr.id_blog_post) as `nbposts`, (SELECT bcl.name FROM ' . _DB_PREFIX_ . 'blog_category bc
						LEFT JOIN `' . _DB_PREFIX_ . 'blog_category_lang` bcl ON (bcl.`id_blog_category`= bc.`id_blog_category`)
						WHERE bc.id_blog_category = a.id_blog_category_parent AND bcl.`id_lang` = b.`id_lang`) as `parent_category` ';

        $this->_join .= ' LEFT JOIN `' . _DB_PREFIX_ . 'blog_post_relation` pr ON (pr.`key` = "category" AND pr.`value` = a.id_blog_category)';
        $this->_group .= ' GROUP BY a.id_blog_category ';

        $this->fields_list['name'] = array('title' => $this->l('Category name'), 'width' => 400, 'filter_key' => 'a!name');
        $this->fields_list['parent_category'] = array('title' => $this->l('Parent'), 'width' => 200, 'search' => false);

        return parent::renderList();
    }

    public function renderForm() {

        if (!($obj = $this->loadObject(true)))
            return;

        $lang_list = Language::getLanguages(true);

        $customer_groups = Group::getGroups(Context::getContext()->language->id);
        $category_groups = array();
        if (!$this->object->id) {
            foreach ($customer_groups as $group)
                $category_groups[] = $group['id_group'];
        } else {
            $category_groups = BlogCategoryRelation::getRelation($this->object->id, 'group');
        }

        foreach ($customer_groups as $group)
            $this->fields_value['groupBox_'.$group['id_group']] = Tools::getValue('groupBox_'.$group['id_group'], in_array($group['id_group'], $category_groups));

        $except_id = ($this->object->id) ? $this->object->id : null;
        $parents = BlogCategory::getParents($this->default_form_language, $except_id);
        $parents[] = array('name' => ' &nbsp; --------------- &nbsp; ', 'id' => '0');

        if (count($lang_list) > 1) {

            $this->fields_value['groupLang'] = Tools::getIsset('groupLang') ? Tools::getValue('groupLang') : BlogCategoryRelation::getRelation($this->object->id,'lang');

            $langInput = array(
                'type' => 'blog_checkbox',
                'label' => $this->l('Language :'),
                'name' => 'groupLang',
                'id' => 'id',
                'hint' => $this->l('Languages ​​in which will be available post'),
                'values' => array(
                    'query' => $lang_list,
                    'id' => 'id_lang',
                    'name' => 'name'
                    ));
        } else {
            $this->fields_value['groupLang'] = Tools::getValue('groupLang', $lang_list[0]['id_lang']);
            $langInput = array('type' => 'hidden', 'name' => 'groupLang',);
        }

        $parentCategoryField = null;
        if (is_null($this->object->id) || (int) $this->object->id > 2) {

            $parentCategoryField = array(
                'type' => 'select',
                'label' => $this->l('Parent category :'),
                'name' => 'id_blog_category_parent',
                'options' => array(
                    'query' => $parents,
                    'id' => 'id',
                    'name' => 'name'),
            );
        }

        $this->fields_form = array(
            'tinymce' => true,
            'legend' => array(
                'title' => $this->l('Category'),
                'icon' => 'icon-edit'
            ),
            'input' => array(
                $langInput,
                array(
                    'type' => 'text',
                    'label' => $this->l('Name :'),
                    'name' => 'name',
                    'id'   => 'name',
                    'size' => 50,
                    'lang' => true,
                    'required' => true,
                    'class' => 'copyMeta2friendlyURL',
                ),
                $parentCategoryField
                ,
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Description :'),
                    'name' => 'description',
                    'lang' => true,
                    'autoload_rte' => true,
                    'rows' => 10,
                    'cols' => 100,
                    'desc' => $this->l('Will be displayed in top of blog category page')
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Meta description :'),
                    'rows' => 2,
                    'lang' => true,
                    'cols' => 80,
                    'name' => 'meta_description',
                    'hint' => $this->l('Search engines meta description')
                ),
                array(
                    'type' => 'tags',
                    'label' => $this->l('Meta keywords :'),
                    'size' => 50,
                    'lang' => true,
                    'name' => 'meta_keywords',
                    'hint' => array(
                        $this->l('To add "tags" click in the field, write something, and then press "Enter."'),
                        $this->l('Invalid characters:').' &lt;&gt;;=#{}'
                    )
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Friendly URL :'),
                    'size' => 50,
                    'lang' => true,
                    'required' => true,
                    'name' => 'link_rewrite',
                    'class' => 'copyMeta2friendlyURL',
                    'hint' => $this->l('Only letters and the minus (-) character are allowed')
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'button'
            ),
            'buttons' =>
                array('save-and-stay' => array(
                    'title' => $this->l('Save and Stay'),
                    'name' => 'submitAdd'.$this->table.'AndStay',
                    'class'=> 'btn btn-default pull-right',
                    'icon' => 'process-icon-save',
                    'type' => 'submit'
            ))
        );

        if (is_null($this->object->id) || (int) $this->object->id > 2) {
            $this->fields_form['input'][] = array(
                'type' => 'text',
                'label' => $this->l('Position :'),
                'size' => 3,
                'name' => 'position'
            );
        }

        $this->fields_form['input'][] = array(
            'type' => 'switch',
            'label' => $this->l('Active :'),
            'name' => 'active',
            'required' => false,
            'class' => 't',
            'is_bool' => true,
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->l('Enabled')
                ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('Disabled')
                )
            )
        );

        $this->fields_form['input'][] = array(
            'type' => 'group',
            'label' => $this->l('Group access:'),
            'name' => 'groupBox',
            'col' => '6',
            'values' => $customer_groups);

        if (Shop::isFeatureActive())
        {
            $this->fields_form['input'][] = array(
                'type' => 'shop',
                'label' => $this->l('Shop association'),
                'name' => 'checkBoxShopAsso',
            );
        }

        $this->fields_form['submit'] = array(
            'title' => $this->l('Save'),
        );

        $this->tpl_form_vars = array('PS_ALLOW_ACCENTED_CHARS_URL', (int)Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL'));

        return parent::renderForm();
    }

    public function processSave() {
        $object = parent::processSave();

        if ($object) {

            if ($object->id == 1 && $object->link_rewrite != 'blog') {
                Psblog::generateRewriteRules();
            }

            if (empty($this->errors)) {

                $groupLang = (array) Tools::getValue('groupLang');
                BlogCategoryRelation::cleanRelation($object->id, 'lang');
                if (sizeof($groupLang) > 0)
                    BlogCategoryRelation::saveRelation($object->id, 'lang', $groupLang);

                $groupBox = (array) Tools::getValue('groupBox');
                BlogCategoryRelation::cleanRelation($object->id, 'group');
                if (sizeof($groupBox) > 0)
                    BlogCategoryRelation::saveRelation($object->id, 'group', $groupBox);
            }
            
            BlogShop::generateSitemap();
        }

        return $object;
    }
    
    public function processExport($text_delimiter = '"')
    {
       if($this->_select != '')
                $this->_select .= ',';
        
        $this->_select .= '(SELECT GROUP_CONCAT(pr2.value) FROM ' . _DB_PREFIX_ . 'blog_category_relation pr2
                             WHERE  pr2.id_blog_category = a.id_blog_category AND pr2.`key` = "group" ) as `group` ';
        
        $this->_select .= ' ,(SELECT GROUP_CONCAT(ps.id_shop) FROM ' . _DB_PREFIX_ . 'blog_category_shop ps
                            WHERE  ps.id_blog_category = a.id_blog_category) as shop ';

        $this->fields_list['description'] = array('title' => $this->l('Description'));
        $this->fields_list['group'] = array('title' => $this->l('Group'));
        $this->fields_list['shop'] = array('title' => $this->l('Shop'));
        $this->fields_list['link_rewrite']  = array('title' => $this->l('Url'));
        $this->fields_list['meta_keywords']  = array('title' => $this->l('Meta keywords'));
        $this->fields_list['meta_description']  = array('title' => $this->l('Meta description'));
        
            // clean buffer
            if (ob_get_level() && ob_get_length() > 0)
                    ob_clean();
            $this->getList($this->context->language->id);
            if (!count($this->_list))
                    return;

            header('Content-type: text/csv');
            header('Content-Type: application/force-download; charset=UTF-8');
            header('Cache-Control: no-store, no-cache');
            header('Content-disposition: attachment; filename="'.$this->table.'_'.date('Y-m-d_His').'.csv"');

            $headers = array();
            foreach ($this->fields_list as $datas)
                    $headers[] = Tools::htmlentitiesDecodeUTF8($datas['title']);
            $content = array();
            foreach ($this->_list as $i => $row)
            {
                    $content[$i] = array();
                    $path_to_image = false;
                    foreach ($this->fields_list as $key => $params)
                    {
                            $field_value = isset($row[$key]) ? Tools::htmlentitiesDecodeUTF8($row[$key]) : '';
                            if ($key == 'image')
                            {
                                    if ($params['image'] != 'p' || Configuration::get('PS_LEGACY_IMAGES'))
                                            $path_to_image = Tools::getShopDomain(true)._PS_IMG_.$params['image'].'/'.$row['id_'.$this->table].(isset($row['id_image']) ? '-'.(int)$row['id_image'] : '').'.'.$this->imageType;
                                    else
                                            $path_to_image = Tools::getShopDomain(true)._PS_IMG_.$params['image'].'/'.Image::getImgFolderStatic($row['id_image']).(int)$row['id_image'].'.'.$this->imageType;
                                    if ($path_to_image)
                                            $field_value = $path_to_image;  
                            }
                            $field_value = str_replace(array("\r","\n",";"),"",$field_value);
                            $content[$i][] = $field_value;
                    }
            }

            $this->context->smarty->assign(array(
                    'export_precontent' => "\xEF\xBB\xBF",
                    'export_headers' => $headers,
                    'export_content' => $content
                    )
            );

            $this->layout = 'layout-export.tpl';
    }

}

?>
