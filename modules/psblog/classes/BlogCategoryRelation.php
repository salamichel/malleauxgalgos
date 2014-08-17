<?php

class BlogCategoryRelation extends ObjectModel {

    public $id_blog_category;
	public $key;
	public $value;
	 
	public static $definition = array(
        'table' => 'blog_category_relation',
        'primary' => 'id_blog_category_relation',
        'fields' => array(
            'id_blog_category' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt','required' => true),
            'key' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 24),
			'value' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255),
        )
    );
	
	public static function saveRelation($id_blog_category,$key,$data){
		
		if(!is_numeric($id_blog_category) || empty($id_blog_category)) return false;
		
		self::cleanRelation($id_blog_category,$key);
				
		if(is_array($data) && count($data)){
			foreach ($data as $value) {
				$row = array('id_blog_category' => intval($id_blog_category),'key' => $key,'value' => $value);
				Db::getInstance()->insert('blog_category_relation', $row);
			}
		}
		
		return true;
	}
	
	public static function cleanRelation($id_blog_category,$key){	
		if(!is_numeric($id_blog_category) || empty($id_blog_category)) return false;	
		return Db::getInstance()->Execute('DELETE FROM `' . _DB_PREFIX_ . 'blog_category_relation` WHERE `id_blog_category` = '.intval($id_blog_category).' AND `key` = "'.$key.'"');	
	}
	
	
	public static function cleanCategoryRelations($id_blog_category){
		if(!is_numeric($id_blog_category) || empty($id_blog_category)) return false;
		return Db::getInstance()->Execute('DELETE FROM `' . _DB_PREFIX_ . 'blog_category_relation` WHERE `id_blog_category` = '.intval($id_blog_category));
	}

	public static function getRelation($id_blog_category,$key){
		
		$values = array();
		
		if(!is_numeric($id_blog_category) || empty($id_blog_category)) return $values;
		
		$query = 'SELECT * FROM `' . _DB_PREFIX_ . 'blog_category_relation` pr WHERE pr.`key` = "'.$key.'" AND pr.`id_blog_category` = '.$id_blog_category;
		$result = Db::getInstance()->ExecuteS($query);
		
		if(is_array($result) && count($result)){
			foreach($result as $row)
				$values[] = $row['value'];
		}
		return $values;
	}
	
}