<?php

require_once(_PS_MODULE_DIR_ . "psblog/psblog.php");

class AdminBlogConfController extends ModuleAdminController
{
    
    public function __construct() {
        
        $url = Psblog::getBlogConfigurationLink();
        
        header("Location:".$url);
        
        parent::__construct();
    }        
      
}