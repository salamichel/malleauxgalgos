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
require_once(_PS_MODULE_DIR_ . 'psblog/classes/BlogPost.php');
require_once(_PS_MODULE_DIR_ . 'psblog/classes/BlogComment.php');

class AdminBlogCommentsController extends ModuleAdminController {

    public function __construct() {

        $this->bootstrap = true;

        $this->table = 'blog_comment';
        $this->className = 'BlogComment';

        $this->module = 'psblog';
        $this->multishop_context = Shop::CONTEXT_ALL;

        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->allow_export = true;
        
        $this->bulk_actions = array('delete' => array('text' => $this->l('Delete selected'), 'confirm' => $this->l('Delete selected items?')));

        $this->_select .= ' SUBSTRING(p.`title`,1,40) AS `post_title`, l.`iso_code`, s.`name` AS `shop_name`, a.`active` as `status` ';
        $this->_join .= ' LEFT JOIN ' . _DB_PREFIX_ . 'blog_post_lang p on (p.id_blog_post = a.id_blog_post AND p.id_lang = a.id_lang) ';
        $this->_join .= ' LEFT JOIN ' . _DB_PREFIX_ . 'lang l on l.id_lang = a.id_lang ';
        $this->_join .= ' LEFT JOIN ' . _DB_PREFIX_ . 'shop s on s.id_shop = a.id_shop ';

        $this->_orderBy = 'id_blog_comment';
        $this->_orderWay = 'DESC';
        
        $statusIcon = array();
        $statusIcon[] = ''; 
        $statusIcon[1] = array('src' => '../admin/warning.gif', 'alt' => 'New');
        $statusIcon[2] = array('src' => '../admin/enabled.gif', 'alt' => '');
        $statusIcon[3] = array('src' => '../admin/disabled.gif', 'alt' => '');
        
        $status = array();
        $status[1] = $this->l('Waiting');
        $status[2] = $this->l('Approved');
        $status[3] = $this->l('Disapproved');
        
        $this->fields_list = array('id_blog_comment' => array('title' => $this->l('ID'), 'align' => 'center', 'width' => 30),
            'status' => array('title' => $this->l('Active'), 'type' => 'select', 'list' => $status  ,'align' => 'center', 'filter_key' => 'a!active', 'icon' => $statusIcon, 'width' => 60),
            'post_title' => array('title' => $this->l('Post'), 'width' => 170),
            'shop_name' => array('title' => $this->l('Shop'), 'width' => 120, 'filter_key' => 's!shop_name'),
            'iso_code' => array('title' => $this->l('Lang'), 'width' => 20),
            'customer_name' => array('title' => $this->l('Customer'), 'width' => 70),
            'content' => array('title' => $this->l('Comment'), 'width' => 280, 'callback' => 'getDescriptionClean', 'orderby' => false),
            'date_add' => array('title' => $this->l('Date'), 'width' => 30));

        parent::__construct();
    }

    public function renderForm() {
        if (!($obj = $this->loadObject(true)))
            return;

        $post = new BlogPost($obj->id_blog_post, $obj->id_lang);
        $obj->post_title = $post->title;

        if (!is_null($obj->id_customer) && !empty($obj->id_customer)) {
            $tokenCustomer = Tools::getAdminToken('AdminCustomers' . (int) (Tab::getIdFromClassName('AdminCustomers')) . (int) $this->context->employee->id);
            $linkCustomer = '?tab=AdminCustomers&id_customer=' . $obj->id_customer . '&viewcustomer&token=' . $tokenCustomer;

            $obj->customer_name_label = '<a href="' . $linkCustomer . '"><strong>' . $obj->customer_name . '</strong></a>';
        } else {

            $obj->customer_name_label = $obj->customer_name;
        }

        $iso = $this->context->language->getIsoById($obj->id_lang);
        $obj->iso_code = ($iso) ? $iso : '&nbsp;';

        $shop = $this->context->shop->getShop($obj->id_shop);
        $obj->shop_name = ($shop) ? $shop['name'] : '&nbsp;';

        $this->fields_form = array(
            'legend' => array('title' => '<img src="../img/admin/comment.gif"> ' . $this->l('Comment')),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'button'
            )
        );

        $this->fields_form['input'][] = array(
            'type' => 'text_label',
            'label' => $this->l('Date :'),
            'name' => 'date_add');

        $this->fields_form['input'][] = array(
            'type' => 'text_label',
            'label' => $this->l('Post title :'),
            'name' => 'post_title');

        $this->fields_form['input'][] = array(
            'type' => 'text_label',
            'label' => $this->l('Customer :'),
            'name' => 'customer_name_label');

        $this->fields_form['input'][] = array(
            'type' => 'text_label',
            'label' => $this->l('Shop :'),
            'name' => 'shop_name');

        $this->fields_form['input'][] = array(
            'type' => 'text_label',
            'label' => $this->l('Lang :'),
            'name' => 'iso_code');

        $this->fields_form['input'][] = array('type' => 'hidden', 'name' => 'customer_name');
        $this->fields_form['input'][] = array('type' => 'hidden', 'name' => 'id_blog_post');

        $this->fields_form['input'][] = array(
            'type' => 'textarea',
            'label' => $this->l('Message :'),
            'rows' => 4,
            'cols' => 92,
            'id' => 'comment_content',
            'name' => 'content');
        
        
        $status = array();
        $status[] = array('name' => $this->l('Waiting'),'id' => 1);
        $status[] = array('name' => $this->l('Approved'),'id' => 2);
        $status[] = array('name' => $this->l('Disapproved'),'id' => 3);
        
        $this->fields_form['input'][] = array(
            'type' => 'select',
            'label' => $this->l('Active :'),
            'name' => 'active',
            'required' => false,
            'options' => array(
                    'query' => $status,
                    'id' => 'id',
                    'name' => 'name')
        );

        $this->tpl_form_vars = array('comment' => $obj);

        return parent::renderForm();
    }
    
    public static function getDescriptionClean($description)
    {
            $description = strip_tags(stripslashes($description));
            $description = str_replace(array("\r","\n",";"),"",$description);
            
            return $description;
    }

    public function processExport($text_delimiter = '"')
    {   
        $this->fields_list['content'] = array('title' => $this->l('Comment'));
        $this->fields_list['id_blog_post'] = array('title' => $this->l('Post ID'));
        $this->fields_list['id_shop'] = array('title' => $this->l('Shop ID'));
        $this->fields_list['id_lang'] = array('title' => $this->l('Lang ID'));
        $this->fields_list['id_customer'] = array('title' => $this->l('Customer ID'));
        
                
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
            
    public function initToolbar() {
        parent::initToolbar();
        unset($this->toolbar_btn['new']);
    }

}

