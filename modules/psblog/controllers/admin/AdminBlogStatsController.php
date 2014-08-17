<?php

require_once(_PS_MODULE_DIR_ . "psblog/psblog.php");
require_once(_PS_MODULE_DIR_ . "psblog/classes/BlogPost.php");
require_once(_PS_MODULE_DIR_ . "psblog/classes/BlogVisit.php");
require_once(_PS_MODULE_DIR_ . "psblog/classes/BlogCategory.php");
require_once(_PS_MODULE_DIR_ . "psblog/classes/BlogComment.php");

class AdminBlogStatsController extends ModuleAdminController
{
    public function __construct() {
        $this->bootstrap = true;
        $this->lang = true;
        parent::__construct();
    }

    public function initContent()
	{
		$this->display = 'view';
		parent::initContent();
	}
        
	public function renderView()
	{
           $post_nb = BlogPost::listPosts(false, false, null, null, true, null, null, null, null, null);
           $post_nb_active = BlogPost::listPosts(false, true, null, null, true, null, null, null, null, null);
           $post_popular = BlogVisit::getPopularPosts();
           $post_popular_unique = BlogVisit::getPopularPosts(10,true);
           $post_commented = BlogComment::getCommentedPosts(10);
           
           $category_nb = BlogCategory::listCategories(false, false, false,true, null);
           $category_nb_active = BlogCategory::listCategories(false, true, false, true, null);
           $category_popular = BlogVisit::getPopularCategories(10);
           $category_popular_unique = BlogVisit::getPopularCategories(10,true);
           
           $comment_nb_wait = BlogComment::getByStatus(1,true);
           $comment_nb = BlogComment::getByStatus(null,true);
           $comment_nb_active = BlogComment::getByStatus(2,true);
           $comment_nb_inactive = BlogComment::getByStatus(3,true);
           
           $blog_url = BlogCategory::linkCategory(1);
           $blog_conf = Psblog::getBlogConfigurationLink();

           $this->tpl_view_vars = array(
                'blog_url' => $blog_url,
                'blog_conf' => $blog_conf,
                'comment' => array(
                            'comment_nb_wait' => $comment_nb_wait,
                            'comment_nb' => $comment_nb,
                            'comment_nb_active' => $comment_nb_active,
                            'comment_nb_inactive' => $comment_nb_inactive),
                'post' => array(
                            'nb' => $post_nb,
                            'nb_active' => $post_nb_active,
                            'popular' => $post_popular,
                            'popular_unique' => $post_popular_unique,
                            'commented' => $post_commented),
                'category' => array(
                            'nb' => $category_nb,
                            'nb_active' => $category_nb_active,
                            'popular' => $category_popular,
                            'popular_unique' => $category_popular_unique));

       return parent::renderView();
    }
        
    public function initToolBar()
	{
		return;
	}
}