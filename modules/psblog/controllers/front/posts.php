<?php

/**
 * Prestablog front controller
 * @category front
 *
 * @author Appside 
 * @copyright Appside
 * @link appside.net
 * 
 */

require_once(_PS_MODULE_DIR_ . "psblog/psblog.php");
require_once(_PS_MODULE_DIR_ . "psblog/classes/BlogPost.php");
require_once(_PS_MODULE_DIR_ . "psblog/classes/BlogVisit.php");
require_once(_PS_MODULE_DIR_ . "psblog/classes/BlogCategory.php");
require_once(_PS_MODULE_DIR_ . "psblog/classes/BlogComment.php");


class PsblogPostsModuleFrontController extends ModuleFrontController {

    public $conf;
    public $id_category = null;
    public $id_post = null;
    public $list_link;

    public $year = null;
    public $month = null;
	
    public function __construct() {
        parent::__construct();
        $this->context = Context::getContext();
        $this->conf = Psblog::getPreferences();
    }

    public function init() {
        Tools::switchLanguage(); //switch language if lang param, ps bug.

        /*** URL MANAGEMENT ***/

        if (Tools::getIsset('post') && is_numeric(Tools::getValue('post'))) {
            $this->id_post = (int) Tools::getValue('post');
        } elseif (Tools::getIsset('category') && is_numeric(Tools::getValue('category'))) {
            $this->id_category = (int) Tools::getValue('category');
        }else{
            $this->id_category = 1; //all	
        }

        if(Tools::getIsset('y') && is_numeric(Tools::getValue('y'))){
                $this->year = (int) Tools::getValue('y');
        }

        if(Tools::getIsset('m') && is_numeric(Tools::getValue('m'))){
                $this->month = (int) Tools::getValue('m');
        }

        parent::init();

        $this->list_link = BlogPost::linkList();
    }

    public function setMedia() {
        parent::setMedia();
        $this->addCSS($this->module->getPathUri() . 'psblog.css');
    }

    public function displayList() {

        $id_lang = $this->context->language->id;
        $id_shop = $this->context->shop->id;

        $limit_per_page = intval($this->conf['list_limit_page']);
        $current_page = Tools::getIsset('p') && is_numeric(Tools::getValue('p')) ? intval(Tools::getValue('p')) : 1;
        $start = ($current_page - 1) * $limit_per_page;

        $category = null;
        if (!is_null($this->id_category) && is_numeric($this->id_category)) {
            $category = new BlogCategory((int)$this->id_category,$id_lang);
			
            if(!$category->isAllowed() || !$category){
                $category->active = 0;
            }else{

                if(!is_null($category->id_blog_category_parent) && !empty($category->id_blog_category_parent)){
                    $parent_category = new BlogCategory((int) $category->id_blog_category_parent,$id_lang);
                    if(!$parent_category->isAllowed()){
                        $category->active = 0;
                    }else{
                        $this->context->smarty->assign('parent_category', $parent_category);
                        $this->context->smarty->assign('parent_category_link', BlogCategory::linkCategory($parent_category->id,$parent_category->link_rewrite));		
                    }
                }
            }
        }

        if ($category && $category->active && $category->id != 1) {
		
            $nb_articles = $category->nbPosts(true, true);

            // first page if page not exists
            $max_page = ceil($nb_articles / $limit_per_page);
            if ($max_page < $current_page) {
                $current_page = 1;
                $start = 0;
            }
            $list = $category->getPosts(true, true, $start, intval($this->conf['list_limit_page']));

            /* Metas */
            $curr_meta_title = $this->context->smarty->getTemplateVars('meta_title');
            $this->context->smarty->assign(array('meta_title' => $curr_meta_title . ' - ' . $category->name,
                                                'meta_description' => $category->meta_description,
                                                'meta_keywords' => $category->meta_keywords));

            $categoryLink = BlogCategory::linkCategory($category->id, $category->link_rewrite);
            $paginationLink = BlogCategory::linkCategory($category->id, $category->link_rewrite,'');
            $subcategories = BlogCategory::getSubCategories($category->id,true);
			
            $this->context->smarty->assign(array('categoryLink' => $categoryLink,
						'subcategories' => $subcategories,
                                                 'post_category' => $category,
                                                 'paginationLink' => $paginationLink));
	
        } elseif($category && $category->active && $category->id == 1){
			
            //DEFAULTS LISTS
			
	    $curr_meta_title = $this->context->smarty->getTemplateVars('meta_title');
            $this->context->smarty->assign(array('meta_title' => $curr_meta_title . ' - ' . $category->name,
                                                 'meta_description' => $category->meta_description,
                                                 'meta_keywords' => $category->meta_keywords,
                                                 'post_category' => $category));
			
            if (Tools::getIsset('search') && Tools::getValue('search') != '') {
                //search articles
                $search_query = Tools::getValue('search');

                $nb_articles = BlogPost::searchPosts($search_query, true, true, true);
                $list = BlogPost::searchPosts($search_query, true, true, false, $start, $limit_per_page);
				
                $this->context->smarty->assign('search_query_nb', $nb_articles);
                $this->context->smarty->assign('search_query', $search_query);
                $this->context->smarty->assign('paginationLink',BlogPost::linkList('',null,array('search='.$search_query)));
		
            }elseif(!is_null($this->year) || !is_null($this->month)){

                /*** archives ***/
                $nb_articles = BlogPost::listPosts(true, true, null, null, true, null, null, null,$this->year, $this->month);
                $list = BlogPost::listPosts(true, true, $start, $limit_per_page, false, null, null, null, $this->year, $this->month);
				
                $params = array();
                if(!is_null($this->year)){
                        $params[] = 'y='.$this->year;
                }

                if(!is_null($this->month)){
                        $params[] = 'm='.$this->month;
                }
			
                $this->context->smarty->assign('paginationLink',BlogPost::linkList('',null,$params));
				
            } else {

                /*** ALL ARTICLES ***/
                $nb_articles = BlogPost::listPosts(true, true, null, null, true, null, null, null);
                $list = BlogPost::listPosts(true, true, $start, $limit_per_page, false, null, null, null);
                $this->context->smarty->assign('paginationLink',BlogPost::linkList(''));
            }
            
            
        } else {

            //NOT FOUND
            $this->redirect_after = '404';
            $this->redirect();
        }
        
        BlogVisit::setNewConnexion('category',$category->id);
        
        if (isset($list) && is_array($list) && count($list)) {

            /** pagination **/
            $nb_pages = ceil($nb_articles / $limit_per_page);
			
            $next = $current_page > 1 ? true : false; //articles plus recents
            $back = $current_page >= 1 && ($current_page < $nb_pages) ? true : false; //articles precedents
			
            $i = 0;
            foreach ($list as $val) {
		$post = new BlogPost($val['id_blog_post']);
                $list[$i]['categories'] = $post->listCategories();
                $i++;
            }
             
            $this->context->smarty->assign(array(
                'post_list' => $list,
                'next' => $next,
                'back' => $back,
                'curr_page' => $current_page
            ));
        }

        $this->setTemplate('list.tpl');
    }

    public function displayPost() {

        $id_lang = $this->context->language->id;
        $id_shop = $this->context->shop->id;

        if (is_null($this->id_post) || !is_numeric($this->id_post)) {
            return $this->displayList();
        }

        $post = new BlogPost($this->id_post,$id_lang);
		
        if ($post && $post->status == 'published' && strtotime($post->date_on) < time() && $post->isAssociatedToLang($id_lang) && $post->isAssociatedToShop($id_shop)) {

            //comment submit 
            if (Tools::isSubmit('submitMessage') && $this->conf['comment_active'] && $post->allow_comments) {
                $comment = new BlogComment();

                try {
                    $message = trim(strip_tags(Tools::getValue('blog_comment')));
                    $comment->id_blog_post = $this->id_post;
                    $comment->customer_name = pSQL(Tools::getValue('customer_name'));

                    if ($message == '' || strlen($comment->customer_name) < (int) $this->conf['comment_name_min_length'])
                        Throw new Exception('error_input');

                    if (!Validate::isMessage($message) || !Validate::isGenericName($comment->customer_name))
                        Throw new Exception('error_input_invalid');

                    $comment->content = $message;

                    $id_customer = (int) $this->context->customer->id;
                    $id_guest = (int) $this->context->cookie->id_guest;

                    if (!$this->conf['comment_guest'] && empty($id_customer))
                        Throw new Exception('error_guest');

                    //get last comment from customer
                    $customerComment = BlogComment::getByCustomer($this->id_post, $id_customer, true, $id_guest);

                    $comment->id_customer = $id_customer;
                    $comment->id_guest = $id_guest;
                    $comment->id_lang = $id_lang;
                    $comment->id_shop = $id_shop;

                    if ($customerComment['content'] == $comment->content)
                        Throw new Exception('error_already');

                    if ($customerComment && (strtotime($customerComment['date_add']) + (int) $this->conf['comment_min_time']) > time())
                        Throw new Exception('error_delay');

                    $comment->active = ($this->conf['comment_moderate']) ? 1 : 2;
                    $comment->save();

                    $this->context->smarty->assign('psblog_confirmation', true);
                } catch (Exception $e) {

                    $comment->content = Tools::getValue('blog_comment');
                    $comment->customer_name = Tools::getValue('customer_name');

                    $this->context->smarty->assign('psblog_error', $e->getMessage());
                    $this->context->smarty->assign('comment', $comment);
                }
            }

            /*** view article ***/
            $images = $post->getImages(false);
            $categories = $post->listCategories($id_lang,true);
            $products = $post->getProducts(true);
            $related = $post->getRelatedPosts(true, true);
	
            /* SEO metas */
            $curr_meta_title = $this->context->smarty->getTemplateVars('meta_title');
            $this->context->smarty->assign(array('meta_title' => $curr_meta_title . ' - ' . $post->title,
                'meta_description' => $post->meta_description,
                'meta_keywords' => $post->meta_keywords));

            if ($this->conf['view_display_popin'] == 1) {
                $this->addjqueryPlugin('fancybox');
                $this->addJS($this->module->getPathUri().'js/popin.js');
            }
			
            $comments = $post->getComments();
            
            BlogVisit::setNewConnexion('post',$post->id);
            
            $this->context->smarty->assign(array(
                'post_images' => $images,
                'post_products' => $products,
                'post_related' => $related,
                'post_categories' => $categories,
                'post_comments' => $comments
            ));
            
        } else {
            $post->status = 'suspended';
        }
        
        $this->context->smarty->assign('post', $post);
        $this->setTemplate('view.tpl');
    }

    public function initContent() {
        parent::initContent();

        if (!is_null($this->id_post) && is_numeric($this->id_post)) {
            $this->displayPost();
        } else {
            $this->displayList();
        }
		
		$defaultCategory = new BlogCategory(1,$this->context->language->id);
		
        $img_path = rtrim($this->conf['img_save_path'], '/') . '/';
		
        $this->context->smarty->assign(array('posts_img_path' => _PS_BASE_URL_ . __PS_BASE_URI__ . $img_path,
            'blog_conf' => $this->conf,
			'blog_title' => $defaultCategory->name,
			'logged' => $this->context->customer->isLogged(),
			'customerName' => ($this->context->customer->logged ? $this->context->customer->firstname : false),
            'listLink' => $this->list_link,
            'posts_rss_url' => BlogPost::linkRss()));
    	}

}
