<?php
    /*
     * plugin name: Category Binding For User
     * author: Mahibul Hasan Sohag
     * Description: Adds a control panel which the admin can use to restrict posts by selected users to a selected category. Restricted users won't view the category selection panel in edit screens.
     * version: 2.0.1
     * author URI: http://sohag.me
     * 
     */

	define('CatUser_URL',plugins_url('',__FILE__));
	define('CatUser_DIR', dirname(__FILE__));
	define('CatUser_FILE',__FILE__);
     
     //including classes and necessary files
     include CatUser_DIR . '/classes/cat-binding-user.php';
?>