<?php

/*
 * Main class to manage users and category binding
 */

if(!class_exists('cat_binding_user')) : 
	class cat_binding_user{
	
		function __construct(){
			add_option("bindusertocat", array(), "", false);
			//add_action('admin_menu', array($this,"butc_menu"));
			add_filter("category_save_pre", array($this,"butc_categorySavePre"));
			add_action('admin_enqueue_scripts',array($this,'adding_css_js'),20);
			//add_action("admin_head", array($this,"butc_script"));
			//add_filter('wp_get_object_terms','category_check',100,4);
			//add_filter('get_category','category_check',0);
			add_filter('manage_users_columns',array($this,'add_column'));
			add_filter('manage_users_custom_column',array($this,'free_users'),10,3);
			//add_action('cat_user_ajax_data',array($this,'ajax'));
			add_action('wp_ajax_cat_user_ajax_data',array($this,'ajaxmanipulation'));
			add_action('wp_ajax_nopriv_cat_user_ajax_data',array($this,'ajaxmanipulation'));			
			
		}
		
		
		//ajax data manipulation
		function ajaxmanipulation(){
			$user = $_REQUEST['uid'];
			$category = $_REQUEST['cat'];
			$userid = preg_replace('/[^0-9]/','',$user);
			
			$userid = (int) $userid;
			update_user_meta($userid, 'bindingcategory', $category);
			echo 'yes';
			exit;
		}


		/*
		 * add a new column for the users table
		 */		
		function add_column($columns){
			$columns['cat-bind'] = '<a id ="category-binding" href="#">Bind Category </a>';
			return $columns;
		}
		
		// populate coustom column of the table
		function free_users($empty='',$column,$id){
			$cat_id = get_user_meta($id,'bindingcategory',true);
			$img = CatUser_URL . '/image/ajax-loader.gif';
			$categories = get_categories(array('hide_empty'=>0));
			$option = '<select style="width:100px" class="bind-category" id="bind-cat-' . $id . '"><option value="">Select</option>';
			foreach($categories as $cat){
				$option .= "<option " . $this->selected($cat_id,$cat->term_id) . " value='$cat->term_id'>$cat->name </option>";
			}
			$option .= '</select>' ;
			$option .= '<img id="imgajax-bind-cat-'.$id.'" style="width:20px;margin-left:5px;vertical-align:middle;display:none;" src="'.$img.'" alt="" />';
			
			return $option;	
		}
		
		//selected
		function selected($a, $b){
			if($a == $b){
				return "selected='selected'";
			}
			return '';
		}
		
		function butc_categorySavePre($in) {
			$cat = $this->butc_getCategory();
			if ($cat) {
				return array($cat);
			}
			return $in;
		}

		function butc_getCategory() {
			global $current_user;
			get_currentuserinfo();
			
			return get_user_meta($current_user->ID, 'bindingcategory', true);
		}

		function butc_removeCategorySelection($page) {

			return preg_replace('#<fieldset id="categorydiv".*?</fieldset>#sim', '', $page);
		}

		function adding_css_js() {
			if ($_GET['page'] == "cat-binding-user.php"){
				wp_enqueue_script('jquery');
				wp_enqueue_script('category_user_js', CatUser_URL . '/js/category-user.js', array('jquery'));
			}
			
			if(preg_match('/users.php/', $_SERVER['REQUEST_URI'])){
				wp_enqueue_script('jquery');
				wp_enqueue_script('category_user_ajax', CatUser_URL . '/js/category-user-ajax.js', array('jquery'));
				$img = CatUser_URL . '/image/ajax-loader.gif';
				wp_localize_script( 'category_user_ajax', 'CatUserAjax', array( 
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'img' => $img
				));	
			}
			
			if(preg_match('#/wp-admin/post\.php#', $_SERVER['REQUEST_URI']) || preg_match('#/wp-admin/post-new\.php#', $_SERVER['REQUEST_URI'])) {
				if(current_user_can('manage_options')) return ;
				$cat = $this->butc_getCategory();
				if($cat =='' || empty($cat)) return;
				
				wp_enqueue_script('jquery');
				wp_enqueue_script('category_user_hide_js', CatUser_URL . '/js/category-user-hide.js', array('jquery'));
				
				wp_register_style('category_user_hide_css', CatUser_URL .'/css/category_user_hide.css');
				wp_enqueue_style('category_user_hide_css');
				
				//getting category information
				
				$class_cat = array();
				$categories = get_categories(array('hide_empty'=>0));
				foreach($categories as $category){
					if($cat == $category->term_id) continue;
					$class_cat[] = $category->term_id;
				}
				
				if (!$cat) {
					$cat = 0;
				}
								
				wp_localize_script( 'category_user_hide_js', 'CatUser', array( 
					'cid' => $cat,
					'aid' => implode('-',$class_cat)
				));				
			}
		}
		
		
	}
	
	$cat_user = new cat_binding_user();
endif;
?>
