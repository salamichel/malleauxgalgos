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
require_once(_PS_MODULE_DIR_ . 'psblog/classes/BlogComment.php');
require_once(_PS_MODULE_DIR_ . 'psblog/classes/BlogPost.php');
require_once(_PS_MODULE_DIR_ . 'psblog/classes/BlogPostRelation.php');
require_once(_PS_MODULE_DIR_ . 'psblog/classes/BlogShop.php');

class AdminBlogPostsController extends ModuleAdminController {

    protected $conf;

    public function __construct() {

        $this->bootstrap = true;

        $this->table = 'blog_post';
        $this->className = 'BlogPost';
        $this->module = 'psblog';
        $this->lang = true;

        $this->allow_export = true;

        if (Tools::getIsset('id_' . $this->table) || Tools::getIsset('add' . $this->table)) {
            $this->multishop_context = Shop::CONTEXT_ALL;
        }

        $this->_orderBy = 'id_blog_post';
        $this->_orderWay = 'DESC';

        $this->fields_list = array(
            'id_blog_post' => array('title' => $this->l('ID'), 'align' => 'center', 'width' => 30),
            'status' => array('title' => $this->l('Status'), 'align' => 'center', 'icon' => array('published' => 'icon-check', 'drafted' => 'icon-pencil', 'suspended' => 'icon-remove'), 'orderby' => false, 'search' => false, 'width' => 60));

        $nb_lang = count(Language::getLanguages(true));

        if ($nb_lang > 1) {

            $this->_select .= ' (SELECT GROUP_CONCAT(l.iso_code) FROM ' . _DB_PREFIX_ . 'blog_post_relation pr 
                                LEFT JOIN `' . _DB_PREFIX_ . 'lang` l ON (pr.`key` = "lang" AND l.`id_lang`= pr.`value`)
                                WHERE  pr.id_blog_post = a.id_blog_post) as iso_code ';
        }

        $this->fields_list['title'] = array('title' => $this->l('Title'), 'width' => 400, 'filter_key' => 'b!title');

        if ($nb_lang > 1) {
            $this->fields_list['iso_code'] = array('title' => $this->l('Lang'), 'width' => 20, 'search' => false);
        }

        if (Psblog::getConfValue('comment_active')) {

            if ($nb_lang > 1)
                $this->_select .= ',';

            $this->_select .= ' COUNT(pc.id_blog_comment) as nbcomments';
            $this->_join .= ' LEFT JOIN `' . _DB_PREFIX_ . 'blog_comment` pc ON (pc.id_blog_post = a.id_blog_post AND pc.active = 2)';
            $this->_group .= ' GROUP BY a.id_blog_post ';
            $this->fields_list['nbcomments'] = array('title' => $this->l('Comments'), 'width' => 10, 'search' => false);
        }

        $this->fields_list['date_on'] = array('title' => $this->l('Publication date'), 'width' => 120, 'type' => 'date', 'search' => false);

        $this->conf = Psblog::getPreferences();

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

    public function setMedia()
    {
        parent::setMedia();

        $this->addJS(array(_MODULE_DIR_ . $this->module->name . '/js/psblog.js',
                           _MODULE_DIR_ . $this->module->name . '/js/jquery.MultiFile.pack.js'));

        $this->addJqueryUi(array('ui.core','ui.widget','ui.datepicker'));
        $this->addjQueryPlugin(array('tagify','autocomplete'));
    }

    public function initPageHeaderToolbar()
    {
        if(empty($this->display))
            $this->page_header_toolbar_btn['new_blog_post'] = array(
                'href' => self::$currentIndex.'&addblog_post&token='.$this->token,
                'desc' => $this->l('Add new post', null, null, false),
                'icon' => 'process-icon-new'
            );

        parent::initPageHeaderToolbar();
    }

    public function renderList() {

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $l = array('comments_waiting' => $this->l('Comments awaiting moderation'),
                   'published_articles' => $this->l('Published posts'),
                   'number_of_articles' => $this->l('Number of posts'),
                   'configuration' => $this->l('Module configuration'));

		$comments_active = $this->conf['comment_active'];
        $comment_nb_wait = BlogComment::getByStatus(1,true);
        $post_nb = BlogPost::listPosts(false, false, null, null, true, null, null, null, null, null);
        $post_nb_active = BlogPost::listPosts(false, true, null, null, true, null, null, null, null, null);
        $blog_conf_link = Psblog::getBlogConfigurationLink();
        $blog_comments_link = BlogComment::getAdminCommentsLink();

        $this->tpl_list_vars = array(
                'l' =>  $l,
				'comments_active' => $comments_active,
                'nb_comments' => $comment_nb_wait,
                'post_nb' => $post_nb,
                'post_nb_active' => $post_nb_active,
                'blog_conf_link' => $blog_conf_link,
                'blog_comments_link' => $blog_comments_link);

        return parent::renderList();
    }
    
    public function renderForm() {

        if (!($obj = $this->loadObject(true))) return;

        $lang_list = Language::getLanguages(true);

        $defaultLanguage = $this->default_form_language;

        $status_options = array(
            array('value' => 'published', 'title' => $this->l('Published')),
            array('value' => 'drafted', 'title' => $this->l('Drafted')),
            array('value' => 'suspended', 'title' => $this->l('Suspended'))
        );

        if (count($lang_list) > 1) {

            $this->fields_value['groupLang'] = Tools::getIsset('groupLang') ? Tools::getValue('groupLang') : BlogPostRelation::getRelation($this->object->id,'lang');

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

        $this->fields_form[]['form'] =  array(

            'legend' => array(
                'title' => $this->l('Content'),
                'icon' => 'icon-edit'
            ),
            'input' => array(

                $langInput,

                array(
                    'type' => 'text',
                    'label' => $this->l('Title :'),
                    'name' => 'title',
                    'size' => 50,
                    'id'   => 'name',
                    'lang' => true,
                    'required' => true,
                    'class' => 'copyMeta2friendlyURL'
                ),

                array(
                    'type' => 'textarea',
                    'label' => $this->l('Excerpt :'),
                    'name' => 'excerpt',
                    'autoload_rte' => true,
                    'lang' => true,
                    'rows' => 5,
                    'cols' => 40,
                    'hint' => $this->l('Article summary for lists')
                ),

                array(
                    'type' => 'textarea',
                    'label' => $this->l('Content :'),
                    'name' => 'content',
                    'autoload_rte' => true,
                    'lang' => true,
                    'rows' => 5,
                    'cols' => 40,
                )
            ));

        $images = (Tools::getValue('id_blog_post') ? $obj->getImages() : array());
        $relative = rtrim($this->conf['img_save_path'], '/') . "/";

        $this->fields_form[]['form']  =  array(
            'legend' => array(
                'title' => $this->l('Medias'),
                'icon' => 'icon-picture-o'
            ),
            'input' => array(
                array(
                'label' => $this->l('Images'),
                'type' => 'blog_medias',
                'name' => 'blog_img[]',
                'id' => 'blog_img',
                'images' => $images,
                'img_path' =>  __PS_BASE_URI__ . $relative . 'thumb/',
                'link_delete' => self::$currentIndex.'&deleteImg&update'.$this->table.'&token='.$this->token.'&id_' .$this->table.'='.$obj->id.'&id_img='
            )));

        $categories = BlogCategory::listCategories(false, false, true, false, 1);
        $this->fields_value['groupBox'] = Tools::getIsset('groupBox') ? Tools::getValue('groupBox') : $obj->listCategories($defaultLanguage, false, false, true);

        $articles = BlogPost::listPosts(false, false, null, null, false, null, null, $obj->id);
        $this->fields_value['groupRelated'] = Tools::getIsset('groupRelated') ? Tools::getValue('groupRelated') : $obj->getRelatedPosts(false, false, true);

        $this->fields_form[]['form']  =  array(
               'legend' => array(
                   'title' => $this->l('Related'),
                   'icon' => 'icon-folder-close'
               ),
               'input' => array(
                   array(
                       'type' => 'blog_checkbox_table',
                       'label' => $this->l('Categories :'),
                       'name' => 'groupBox',
                       'id' => 'id_blog_category',
                       'values' => array(
                           'query' => $categories,
                           'id' => 'id_blog_category',
                           'name' => 'name'
                       )
                   ),
                   array(
                       'type' => 'blog_checkbox_table',
                       'label' => $this->l('Related posts :'),
                       'name' => 'groupRelated',
                       'id' => 'id_blog_post',
                       'values' => array(
                           'query' => $articles,
                           'id' => 'id_blog_post',
                           'name' => 'title'
                       )
                   )
               ));

        if (isset($this->conf['product_active']) && $this->conf['product_active'] == 1) {

            $accessories = array();
            $productArray = array();
            if (Tools::getValue('inputAccessories')) {
                $productArray = explode('-', Tools::getValue('inputAccessories'), -1);
            } elseif ($obj->id) {
                $productArray = $obj->getProducts(true, false, true);
            }

            $i = 0;
            foreach ($productArray as $id_product) {
                $product = new Product($id_product, false,$defaultLanguage);
                $accessories[$i]['id_product'] = $product->id;
                $accessories[$i]['name'] = $product->name;
                $accessories[$i]['reference'] = $product->reference;
                $i++;
            }

            $this->fields_form[2]['form']['input'][] = array(
                'type' => 'blog_accessories',
                'label' => $this->l('Related products :'),
                'name' => 'inputAccessories',
                'accessories' => $accessories);
        }

        $this->fields_form[]['form']  =  array(
            'legend' => array(
                'title' => $this->l('SEO - Post metas'),
                'icon' => 'icon-info'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Friendly URL :'),
                    'name' => 'link_rewrite',
                    'size' => 50,
                    'lang' => true,
                    'required' => true,
                    'class' => 'copyMeta2friendlyURL',
                    'hint' => $this->l('Only letters and the minus (-) character are allowed')
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('META description :'),
                    'name' => 'meta_description',
                    'lang' => true,
                    'rows' => 5,
                    'cols' => 20,
                    'hint' => $this->l('Search engines meta description')
                ),
                array(
                    'type' => 'tags',
                    'label' => $this->l('META keywords :'),
                    'name' => 'meta_keywords',
                    'lang' => true,
                    'hint' => array(
                        $this->l('To add "tags" click in the field, write something, and then press "Enter."'),
                        $this->l('Invalid characters:').' &lt;&gt;;=#{}'
                    )
                )
            ));

        $date_on = $this->getFieldValue($this->object,'date_on');
        if($date_on == '0000-00-00') $date_on = '';
        if(!Tools::getValue('date_on') && !$obj->id){
            $date_on = date('Y-m-d');
        }

        $this->fields_value['date_on'] = $date_on;

        $this->fields_form[]['form']  =  array(

            'legend' => array(
                'title' => $this->l('Options'),
                'icon' =>	'icon-cogs',
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Status :'),
                    'name' => 'status',
                    'required' => true,
                    'options' => array(
                        'query' => $status_options,
                        'id' => 'value',
                        'name' => 'title')
                ),
                array(
                    'type' => 'blog_date',
                    'label' => $this->l('Publication date :'),
                    'name' => 'date_on'
                ),
            ));

        if (isset($this->conf['comment_active']) && $this->conf['comment_active'] == 1) {

            $this->fields_value['allow_comments'] = (!$this->object->id) ? 1 : $this->getFieldValue($this->object,'allow_comments');

            $this->fields_form[4]['form']['input'][] = array(
                'type' => 'switch',
                'label' => $this->l('Allow comments :'),
                'name' => 'allow_comments',
                'required' => false,
                'is_bool' => true,
                'values' => array(
                    array('id' => 'active_on','value' => 1,'label' => $this->l('Enabled')),
                    array('id' => 'active_off','value' => 0,'label' => $this->l('Disabled'))),
                'hint' => $this->l('Enable or disable comments.'));
        }

        if (Shop::isFeatureActive())
        {
            $this->fields_form[4]['form']['input'][] = array(
                'type' => 'shop',
                'label' => $this->l('Shop association'),
                'name' => 'checkBoxShopAsso');
        }

        $this->fields_form[4]['form']['submit'] = array('title' => $this->l('Save'));

        $this->fields_form[4]['form']['buttons'] =
            array('save-and-stay' => array(
            'title' => $this->l('Save and Stay'),
            'name' => 'submitAdd'.$this->table.'AndStay',
            'class'=> 'btn btn-default pull-right',
            'icon' => 'process-icon-save',
            'type' => 'submit'
            )
        );

        $this->tpl_form_vars = array('PS_ALLOW_ACCENTED_CHARS_URL',(int)Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL'));

        $this->multiple_fieldsets = true;

        return parent::renderForm();
    }

    public function initProcess() {
        parent::initProcess();
        if (Tools::isSubmit('deleteImg') && Tools::getValue('id_img')) {
            $this->action = 'postimage';
        }
    }

    public function processPostimage() {

        if (Tools::isSubmit('deleteImg') && Tools::getValue('id_img')) {

            $id = intval(Tools::getValue($this->identifier));
            if (!$id)
                return false;

            $id_blog_image = Tools::getValue('id_img');
            $object = new $this->className($id);
            $object->removeImage($id_blog_image);

            $this->redirect_after = self::$currentIndex . '&' . $this->identifier . '=' . $object->id . '&conf=7&update' . $this->table . '&token=' . $this->token;
        }

    }

    public function processSave() {

        $object = parent::processSave();

        if ($object) {

            if (empty($this->errors)) {

                $save_path = rtrim($this->conf['img_save_path'], '/') . "/";

                $groupList = Tools::getValue('groupBox');

                BlogPostRelation::cleanRelation($object->id, 'category');
                if (is_array($groupList) AND sizeof($groupList) > 0)
                    BlogPostRelation::saveRelation($object->id, 'category', $groupList);

                BlogPostRelation::cleanRelation($object->id, 'product');
                if (Tools::getValue('inputAccessories')) {
                    $productArray = explode('-', Tools::getValue('inputAccessories'), -1);
                    BlogPostRelation::saveRelation($object->id, 'product', $productArray);
                }

                $groupLang = (array) Tools::getValue('groupLang');
                BlogPostRelation::cleanRelation($object->id, 'lang');
                if (sizeof($groupLang) > 0)
                    BlogPostRelation::saveRelation($object->id, 'lang', $groupLang);

                $groupRelated = Tools::getValue('groupRelated');
                BlogPostRelation::cleanRelation($object->id, 'post');
                if (is_array($groupRelated) AND sizeof($groupRelated) > 0)
                    BlogPostRelation::saveRelation($object->id, 'post', $groupRelated);

                $images_position = Tools::getValue('img_pos');
                if (is_array($images_position) && count($images_position)) {
                    $object->setImagesPosition($images_position);
                }

                if (isset($_FILES['blog_img']) && !empty($_FILES['blog_img']['tmp_name'][0])) {

                    $j = 0;
                    foreach ($_FILES['blog_img']['name'] as $key => $val) {

                        if (empty($val))
                            continue;

                        $id_img = null;

                        try {

                            $file['name'] = $val;
                            $file['type'] = $_FILES['blog_img']['type'][$key];
                            $file['error'] = $_FILES['blog_img']['error'][$key];
                            $file['size'] = $_FILES['blog_img']['size'][$key];
                            $file['tmp_name'] = $_FILES['blog_img']['tmp_name'][$key];

                            //img name
                            $curr_time = time() + $j;
                            $ext = substr(strrchr($file['name'], '.'), 1);
                            $img_name = $curr_time . '.' . $ext;

                            $dest = _PS_ROOT_DIR_ . '/' . $save_path;

                            $this->checkFile($file, $dest);
                            $id_img = $object->addImage($img_name, 0);
                            $this->uploadFile($file, $dest . $img_name);

                            $object->generateImageThumbs($id_img);
                        } catch (Exception $e) {
                            $this->_errors[] = Tools::displayError($e->getMessage());
                            if (!is_null($id_img))
                                $object->removeImage($id_img);
                        }

                        $j++;
                    }
                }

                BlogShop::generateSitemap();

                if (Tools::getValue('blog_img_default')) {
                    $idImg = Tools::getValue('blog_img_default');
                    $object->setImageDefault($idImg);
                } elseif ($object->getImages(true) > 0) {
                    $object->setImageDefault(0);
                }
            }
        }

        return $object;
    }

    protected function checkFile($_file, $save_path) {

        $supported_formats = explode("|", $this->conf['file_formats']);
        $save_path = rtrim($save_path, '/') . "/";
        $MAX_FILENAME_LENGTH = 128;
        $max_file_size_in_bytes = 2147483647;

        $uploadErrors = array(0 => "There is no error, the file uploaded with success",
            1 => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
            2 => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
            3 => "The uploaded file was only partially uploaded",
            4 => "No file was uploaded",
            6 => "Missing a temporary folder");
        $tmp_file = $_file['tmp_name'];
        $file_name = $_file["name"];
        //erreurs diverses
        if (isset($_file["error"]) && $_file["error"] != 0) {
            throw new Exception("Error in file " . $file_name . " with message : " . $uploadErrors[$_file["error"]]);
        }
        $ext = substr(strrchr($file_name, '.'), 1);
        if (count($supported_formats) > 0) {
            if (!in_array($ext, $supported_formats))
                throw new Exception("File format \"." . $ext . "\" is not allowed  (" . implode(',', $supported_formats) . ")");
        }
        $file_size = @filesize($tmp_file);
        //size
        if ($file_size > $max_file_size_in_bytes) {
            throw new Exception("the file " . $file_name . " is too heavy (" . $file_size . "), max size : " . $max_file_size_in_bytes);
        }
        //check filename
        if (strlen($file_name) == 0 || strlen($file_name) > $MAX_FILENAME_LENGTH) {
            throw new Exception("the filename is not valid.");
        }
        return true;
    }

    protected function uploadFile($_file, $file_name) {
        $tmp_file = $_file['tmp_name'];
        if (!@move_uploaded_file($tmp_file, $file_name)) {
            throw new Exception("the file " . $file_name . " was not uploaded");
        }
        return true;
    }


    public function processExport($text_delimiter = '"')
    {
        if($this->_select != '')
                $this->_select .= ',';
        
        $this->_select .= '(SELECT GROUP_CONCAT(pr2.value) FROM ' . _DB_PREFIX_ . 'blog_post_relation pr2
                             WHERE  pr2.id_blog_post = a.id_blog_post AND pr2.`key` = "category" ) as category ';
        
        $this->_select .= ' ,(SELECT GROUP_CONCAT(pr3.value) FROM ' . _DB_PREFIX_ . 'blog_post_relation pr3
                            WHERE  pr3.id_blog_post = a.id_blog_post AND pr3.`key` = "product" ) as product ';
        
        $this->_select .= ' ,(SELECT GROUP_CONCAT(pr4.value) FROM ' . _DB_PREFIX_ . 'blog_post_relation pr4
                            WHERE pr4.id_blog_post = a.id_blog_post AND pr4.`key` = "post" ) as `post` ';
        
         $this->_select .= ' ,(SELECT GROUP_CONCAT(pi.img_name) FROM ' . _DB_PREFIX_ . 'blog_image pi
                                WHERE pi.id_blog_post = a.id_blog_post) as `image_name` ';
         
         $this->_select .= ' ,(SELECT GROUP_CONCAT(ps.id_shop) FROM ' . _DB_PREFIX_ . 'blog_post_shop ps
                             WHERE ps.id_blog_post = a.id_blog_post) as `shop` ';
         
         $this->_select .= ' ,(SELECT GROUP_CONCAT(ps.id_shop) FROM ' . _DB_PREFIX_ . 'blog_post_shop ps
                             WHERE ps.id_blog_post = a.id_blog_post) as `shop` ';
         
         $this->_select .= ' ,(SELECT pi2.img_name FROM ' . _DB_PREFIX_ . 'blog_image pi2
                                WHERE pi2.id_blog_post = a.id_blog_post AND pi2.`default` = 1) as `image_default` ';
         
        $this->fields_list['content'] = array('title' => $this->l('Content'));
        $this->fields_list['link_rewrite']  = array('title' => $this->l('Url'));
        $this->fields_list['category'] = array('title' => $this->l('Categories'));
        $this->fields_list['product'] = array('title' => $this->l('Related products'));
        $this->fields_list['post'] = array('title' => $this->l('Related posts'));
        $this->fields_list['image_default'] = array('title' => $this->l('Image default'));
        $this->fields_list['image_name'] = array('title' => $this->l('Images'));
        $this->fields_list['meta_keywords']  = array('title' => $this->l('Meta keywords'));
        $this->fields_list['meta_description']  = array('title' => $this->l('Meta description'));
        $this->fields_list['shop'] = array('title' => $this->l('Shops')); 
        
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