<?php
add_action('admin_init','plugin_options_sinapicv2::init',99);
class plugin_options_sinapicv2{
	public static $iden = 'sinapicv2';
	/**
	 * init
	 * 
	 * @return 
	 * @version 1.0.0
	 * @author KM@INN STUDIO
	 */
	public static function init(){
		if(!self::is_options_page()) return false;
		self::save_options();
		self::redirect();
	}
	/**
	 * is_options_page
	 * 
	 * @return bool
	 * @version 1.0.1
	 * @author KM@INN STUDIO
	 * @date PM 9:52 2013/9/24
	 */
	private static function is_options_page(){
		if(is_admin() && isset($_GET['page']) && $_GET['page'] === self::$iden . '-options'){
			return true;
		}else{
			return false;
		}
	}
	/**
	 * redirect
	 * 
	 * @return n/a
	 * @version 1.0.0
	 * @author KM@INN STUDIO
	 */
	private static function redirect(){
		if(self::is_options_page() && isset($_POST['action']) && $_POST['action'] === 'save_options'){
			if(isset($_GET['updated']) && $_GET['updated'] === 'true'){
				$redirect_updated = null;
			}else{
				$redirect_updated = '&updated=true';
			}
			/** refer */
			$current_url = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
			$current_url .= $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"];

			header('Location: '.$current_url . $redirect_updated);
		}
	}
	/**
	 * get the plugin options from the features default value or DB.
	 * 
	 * @return array
	 * @version 2.0.0
	 * @since 3.1.0
	 * @author KM@INN STUDIO
	 * @date PM 9:52 2013/9/24
	 */
	public static function get_options($key = null){
		/** Default options hook */
		$defaults = apply_filters('plugin_options_default_' . self::$iden,null);
		$options = get_option('plugin_options_' . self::$iden);
		$options = wp_parse_args($options,$defaults);
		if(empty($key)){
			return $options;
		}else if(isset($options[$key])){
			return $options[$key];
		}
	}
	
	/**
	 * Save Options
	 * 
	 * 
	 * @return n/a
	 * @version 1.0.3
	 * @author KM@INN STUDIO
	 * @date AM 10:01 2013/11/30
	 */
	private static function save_options(){
		$options = null;
		/** Check the action and save options */
		if(isset($_POST['action']) && $_POST['action'] === 'save_options'){
			/** Add Hook */
			$options = apply_filters('plugin_options_save_' . self::$iden,$options);
			/** Reset the options? */
			if(isset($_POST['reset_options'])){
				/** Delete plugin options */
				delete_option('plugin_options_' . self::$iden);
			}else{
				/** Update plugin options */
				update_option('plugin_options_' . self::$iden,$options);
			}
		}
	}
}


?>