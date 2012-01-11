<?php

/*
 * Main class to manage users and category binding
 */

if(!class_exists('cat_binding_user')) : 
	class cat_binding_user{
	
		function __construct(){
			add_option("bindusertocat", array(), "", false);
			add_action('admin_menu', array($this,"butc_menu"));
			add_filter("category_save_pre", array($this,"butc_categorySavePre"));
			add_action('admin_enqueue_scripts',array($this,'adding_css_js'),20);
			//add_action("admin_head", array($this,"butc_script"));
			//add_filter('wp_get_object_terms','category_check',100,4);
			//add_filter('get_category','category_check',0);
			
			register_activation_hook( CatUser_FILE, array($this,'table_creation'));
		}
		
		/*
		 * A table that holds the user with category
		 */
		
		function table_creation(){
			global $wpdb;
			$table = $wpdb->prefix . 'catuser';
			$sql = "CREATE TABLE IF NOT EXISTS `$table`(
				`uid` bigint unsigned NOT NULL ,
				`cid` bigint unsigned NOT NULL 
				)";
			
			//loading the dbDelta function manually
			if(!function_exists('dbDelta')) : 
				require_once(ABSPATH.'wp-admin/includes/upgrade.php');
			endif;
			dbDelta($sql);
			
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
			global $wpdb;
			$table = $wpdb->prefix . 'catuser';
			return $wpdb->get_var("SELECT cid FROM $table WHERE uid=$current_user->ID");
		}

		function butc_removeCategorySelection($page) {

			return preg_replace('#<fieldset id="categorydiv".*?</fieldset>#sim', '', $page);
		}

		function adding_css_js() {
			if ($_GET['page'] == "cat-binding-user.php"){
				wp_enqueue_script('jquery');
				wp_enqueue_script('category_user_js', CatUser_URL . '/js/category-user.js', array('jquery'));
			}
			
			if(preg_match('#/wp-admin/post\.php#', $_SERVER['REQUEST_URI']) || preg_match('#/wp-admin/post-new\.php#', $_SERVER['REQUEST_URI'])) {
				if(current_user_can('manage_options')) return ;
				
				wp_enqueue_script('jquery');
				wp_enqueue_script('category_user_hide_js', CatUser_URL . '/js/category-user-hide.js', array('jquery'));
				
				wp_register_style('category_user_hide_css', CatUser_URL .'/css/category_user_hide.css');
				wp_enqueue_style('category_user_hide_css');
				
				//getting category information
				$cat = $this->butc_getCategory();
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
			
		

		function butc_menu() {
			add_management_page(__('Bind user to category'),	__('Bind user to category'),'edit_posts', basename(__FILE__), array($this,"butc_form"));    
		}

		function butc_form() {
			$categories = get_categories(array('hide_empty'=>0));
			var_dump($categories);
			exit;
						
			global $wpdb;
			if (isset($_POST['info_update'])) {
				$updated = $this->butc_saveForm($_POST);
				if ($updated) {
					echo '<div class="updated"><p><strong>' . __('Binding successful.', 'bindusertocat') .'</strong></p></div>';
				} else {
					echo '<div class="error"><p><strong>' . __('Error while saving binding.', 'bindusertocat') .'</strong></p></div>';
				}
			}
			echo '<div class="wrap"><form method="post" action="">';
			echo '<h2>Bind user to cat settings</h2>';
			$userids = $wpdb->get_col("SELECT ID FROM $wpdb->users;");
			$users = array();
			foreach ($userids as $userid) {
				$tmp_user = new WP_User($userid);
				if ($tmp_user->wp_user_level > 7) continue;
				$users[$userid] = $tmp_user;
			}

		$wp23 = $this->butc_wp23orbetter();

		if ($wp23) {
			$cats = $wpdb->get_results("SELECT * FROM $wpdb->terms JOIN $wpdb->term_taxonomy USING (term_id) WHERE taxonomy='category' ORDER BY name");
		}
		else {
			$cats = $wpdb->get_results("SELECT * FROM $wpdb->categories ORDER BY cat_name");
		}

			//$opts = get_option("bindusertocat");
			$table = $wpdb->prefix . 'catuser';
			$opts = $wpdb->get_results("SELECT * FROM $table ORDER BY uid");
			
			$t = "<tr><td>%s</td><td>%s</td></tr>";

			echo "<table id='bindusertocat'>";

		$field = $wp23 ? 'term_id' : 'cat_ID';
		$name = $wp23 ? 'name' : 'cat_name';

			foreach ($opts as $opt) {
				
				printf($t, $this->butc_select('user[]', $users, 'ID', 'user_login', $opt->uid), $this->butc_select('cat[]', $cats, $field, $name, $opt->cid));
			}

			printf($t, $this->butc_select('user[]', $users, 'ID', 'user_login'), $this->butc_select('cat[]', $cats, $field, $name));

			echo "</table>";

			echo '<div class="submit"><input type="submit" name="info_update" value="' . __('Update settings', 'bindusertocat') . '" /></div></form></div>';

		}

		function butc_select($n, $a = array(), $v, $t, $s = '') {
			$h = '<select name="' . $n . '">';
			$h .= '<option value=""' . ($s === "" ? ' selected="selected"' : '') . '> -- </option>';
			foreach ($a as $it) {
				$h .= '<option value="' . $it->$v . '"' . ($it->$v == $s ? ' selected="selected"' : '') . '>' . $it->$t . '</option>';
			}
			$h .= '</select>';
			return $h;
		}

		function butc_saveForm() {
			global $wpdb;
			$table = $wpdb->prefix . 'catuser';

			$len = count($_POST["user"]);
						
			$opts = array();
			for ($i = 0; $i < $len; $i++) {
				if ($_POST["user"][$i] && $_POST["cat"][$i]) {
					$opts[$_POST["user"][$i]] = (int)$_POST["cat"][$i];
					
					$uid = (int)$_POST["user"][$i];
					$cat = (int)$_POST["cat"][$i];
					$exists = $wpdb->get_row("SELECT * FROM $table WHERE uid='$uid'");
					if($exists){
						$wpdb->update($table,array('cid'=>$cat),array('uid'=>$uid),array('%d'),array('%d'));
					}
					else{
						$wpdb->insert($table,array('uid'=>$uid,'cid'=>$cat),array('%d','%d'));
					}
				}
			}
			
			return true;
		}
		
		

		function butc_script() {
			if (!isset($_GET['page']) || !$_GET['page'] == "bind-user-to-cat.php") return;
			echo "<script type='text/javascript'>\n";
			readfile(dirname(__FILE__) . "/bind-user-to-cat.js");
			echo "\n</script>";

		}

		function butc_wp23orbetter() {
				static $ret = null;
			if (isset($ret)) {
				return $ret;
			}
			$version = get_bloginfo('version');
			$parts = explode('.', $version);
			if ((int)$parts[0] > 2) {
				$ret = true;
				return $ret;
			}
			if ((int)$parts[0] == 2) {
				$ret = ((int)$parts[1] >= 3);
				return $ret;
			}
			$ret = false;
			return $ret;
		}

		function category_check($terms, $object_ids, $taxonomies, $args){

		}
		
	}
	
	$cat_user = new cat_binding_user();
endif;
?>
