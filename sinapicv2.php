<?php
/*
Plugin Name: Sina Pic v2
Plugin URI: http://inn-studio.com/sinapicv2
Description: Best Image Hosting Plugin for WP. Upload your picture to sina weibo and show it on your site.
Author: INN STUDIO
Author URI: http://inn-studio.com
Version: 1.6.2
Text Domain:sinapicv2
Domain Path:/languages
*/

require_once(plugin_dir_path(__FILE__) . 'core/core-functions.php');
require_once(plugin_dir_path(__FILE__) . 'core/core-options.php');
require_once(plugin_dir_path(__FILE__) . 'core/core-features.php');


add_action('plugins_loaded','sinapicv2::init');

class sinapicv2{

	public static $iden = 'sinapicv2';
	private static $basedir_backup = '/sinapic_backup/';
	private static $log_key = '_sinapic_log';
	private static $log_meta_key = '_sinapic_meta_log';
	private static $feature_image_key = 'sinapic_feature_image';
	private static $log_data = null;
	private static $file_url = null;
	private static $key_authorization = '_sinapic_authorization';
	private static $b = array('a','b','c','d','e');
	private static $p = array('_');
	private static $d = array('1','2','3','4','5','6');
	private static $q = array('s','o');
	private static $wb = array('514aj1aPKgLZMu80Divag82C/8O1A+OA9liLjSXmQd+cE8FGHnXG','9e25KNBWUg5lLjSaXSaAs4EYFF4Wz0kR1f3Q+Hex+0YglMhOo+2L1VUpj6JwBp0CrqXUBMquIZFTubYtdg','4875UnQ0A6s38/Ra/hlOAC7BqbS/5AwQhQjFsfuiQRlaCggd/7GnXiUaafN8itsl6m9IaQRmp0F16h2B4fv4+zwIpmirNwOeeUl7CKaN7yZpTV/lIruW');
	private static $available_times = 10;
	private static function tdomain(){
		load_plugin_textdomain(self::$iden,false,dirname(plugin_basename(__FILE__)). '/languages/');
		$header_translate = array(
			'plugin_name' => __('Sina Pic v2',self::$iden),
			'plugin_uri' => __('http://inn-studio.com/sinapicv2',self::$iden),
			'description' => __('Best Image Hosting Plugin for WP. Upload your picture to sina weibo and show it on your site.',self::$iden),
			'author_uri' => __('http://inn-studio.com',self::$iden),
		);
	}
	private static function update(){
		require_once(plugin_dir_path(__FILE__) . 'inc/update.php');
		$update_checker = new PluginUpdateChecker(
			__('http://update.inn-studio.com',self::$iden) . '/?action=get_update&slug=' . self::$iden,
			__FILE__,
			self::$iden
		);
		$update_checker->M00001 = __('Check for updates',self::$iden);
		$update_checker->M00002 = __('The URL %s does not point to a valid plugin metadata file. ',self::$iden);
		$update_checker->M00003 = __('WP HTTP error: ',self::$iden);
		$update_checker->M00004 = __('HTTP response code is %s . (expected: 200) ',self::$iden);
		$update_checker->M00005 = __('wp_remote_get() returned an unexpected result.',self::$iden);
		$update_checker->M00006 = __('Can not to read the Version header for %s. The filename may be incorrect, or the file is not present in /wp-content/plugins.',self::$iden);
		$update_checker->M00007 = __('Skipping update check for %s - installed version unknown.',self::$iden);
		$update_checker->M00008 = __('This plugin is up to date.',self::$iden);
		$update_checker->M00009 = __('A new version of this plugin is available.',self::$iden);
		$update_checker->M00010 = __('Unknown update checker status "%s"',self::$iden);	
	}
	public static function init(){
		self::tdomain();
		self::update();
		/** 
		 * options
		 */
		add_filter('plugin_options_default_' . self::$iden,get_class() . '::admin_default');
		add_filter('plugin_options_save_' . self::$iden,get_class() . '::backend_options_save');
		/** 
		 * ajax
		 */
		add_action('wp_ajax_' . self::$iden,get_class() . '::process');
		/** 
		 * orther
		 */
		add_action('admin_init',get_class() . '::meta_box_add');
		add_action('save_post',get_class() . '::meta_box_save');
		add_action('admin_menu',get_class() . '::admin_options_page');
		add_action('wp_footer',get_class() . '::footer_info');
		
		//Add the filter with your plugin information
		add_filter( 'plugin_action_links_' . plugin_basename( plugin_dir_path( __FILE__ ) . self::$iden . '.php'), get_class() . '::plugin_action_links' );
	}
	public static function plugin_action_links( $links ) {
	  return array_merge(
		array(
		  'settings' => '<a href="' . admin_url( 'plugins.php?page=' . self::$iden ) . '-options">' . __( 'Settings', self::$iden ) . '</a>'
		),
		$links
	  );
	}

	private static function css(){
		echo plugin_features_sinapicv2::get_plugin_css('style','normal');
	}
	/**
	 * set_options_authorize
	 *
	 * @return 
	 * @version 1.0.1
	 * @author KM@INN STUDIO
	 */
	private static function set_options_authorize($args){
		$defaults = array(
			'access_token' => null,
			'expires_in' => null,
		);
		$r = wp_parse_args($args,$defaults);
		update_user_meta(get_current_user_id(),self::$key_authorization,$r);
	}
	/**
	 * get_options
	 *
	 * @param string
	 * @return mixed
	 * @version 1.0.0
	 * @author KM@INN STUDIO
	 */
	private static function get_options($key = null){
		$options = plugin_options_sinapicv2::get_options();
		if($key){
			$options = isset($options[$key]) ? $options[$key] : null;
		}
		return $options;
	}
	/**
	 * process
	 * 
	 * @params 
	 * @return 
	 * @version 1.0.0
	 * @author KM@INN STUDIO
	 */
	public static function process(){
		$output = null;
		/** 
		 * get action type
		 */
		$type = isset($_GET['type']) ? $_GET['type'] : null;
		/** 
		 * $options
		 */
		$options = self::get_options();
		/** 
		 * set timeout limit is 0
		 */
		set_time_limit(0);
		switch($type){
			/** 
			 * set authorize
			 */
			case 'set_authorize':
				if(isset($_GET['access_token']) && isset($_GET['expires_in'])){
					$args = array(
						'access_token' => $_GET['access_token'],
						'expires_in' => (int)$_GET['expires_in'],
						'access_time' => time(),
					);
					self::set_options_authorize($args);
					die(
					'
					<!doctype html>
					<html lang="en">
					<head>
						<meta charset="UTF-8">
						<title>' . __('Congratulation, SinaPicV2 has been authorized!',self::$iden) . '</title>
						' . self::css() . '
					</head>
					<body>
						' . plugin_functions_sinapicv2::status_tip('success',__('Congratulation, SinaPicV2 has been authorized!',self::$iden) . '<br/><a href="javascript:window.open(false,\'_self\',false);window.close();">' . __('Close this window and reload the plugin UI.',self::$iden) . '</a>') . '
					</body>
					</html>
					'
					);
				}
				break;
			/** 
			 * check authorize
			 */
			case 'check_authorize':
				if(self::is_authorized()){
					$output['status'] = 'success';
					$output['des']['content'] = __('Authorized.',self::$iden);
				}else{
					$output['status'] = 'error';
					$output['des']['content'] = __('Unauthorize.',self::$iden);
				}
				break;
			/** 
			 * upload pic
			 */
			case 'upload':
				$file_name = isset($_POST['file_name']) ? $_POST['file_name'] : null;
				$file_b64 = isset($_POST['file_b64']) ? base64_decode($_POST['file_b64']) : null;
				if(!$file_name || !$file_b64){
					$output['status'] = 'error';
					$output['des']['content'] = __('Not enough params.',self::$iden);
				}else{
					/** 
					 * check authorization
					 */
					if(!self::is_authorized()){
						$output['status'] = 'error';
						$output['des']['content'] = __('Please use your Sina Weibo account to authorize the plugin.',self::$iden);
					}else{
						// if(!class_exists('SaeTClientV2') && !class_exists('OAuthException')){
							include dirname(__FILE__) . '/inc/saetv2.ex.class.php';
						// }
						$authorization = (array)get_user_meta(get_current_user_id(),self::$key_authorization,true);
						$file_ext = explode('.',$file_name);
						$file_ext = $file_ext[count($file_ext) - 1];
						$file_name = date('YmdHis') . rand(100,999) . '.' . $file_ext;
						$uploads = wp_upload_dir();
						$upload_dir = $uploads['basedir'] . '/';
						$upload_url = $uploads['baseurl'] . '/';
						file_put_contents($upload_dir . $file_name,$file_b64);
						$file_url = $upload_url . $file_name;
						
						$c = new SaeTClientV2(self::get_config(0),self::get_config(1),$authorization['access_token']);
						$callback = $c->upload(date('Y-m-d H:i:s ' . rand(100,999)) ,$file_url);
						unlink($upload_dir . $file_name);

						/** 
						 * get callback
						 */
						if(is_array($callback) && isset($callback['bmiddle_pic'])){
							$output['status'] = 'success';
							$output['des']['img_url'] = $callback['bmiddle_pic'];
							/** 
							 * destroy after upload 
							 */
							if(isset($options['destroy_after_upload'])){
								$c->destroy($callback['id']);
							}
						/** 
						 * too fast
						 */
						}else if(is_array($callback) && isset($callback['error_code'])){
							$output['status'] = 'error';
							$output['des']['content'] = $callback['error'];
						/** 
						 * unknown error
						 */
						}else{
							$output['status'] = 'error';
							$output['des']['content'] = sprintf(__('Sorry, upload failed. Please try again later or contact the plugin author. Weibo returns an error message: %s',self::$iden),json_encode($callback));
							// var_dump($callback);
							// die();
						}
					}
				}		
			
				break;
			/** 
			 * get backup data
			 */
			case 'get_backup_data':
				$log_data = self::log_get_all();
				$log_meta_data = array_values(self::log_all_meta_get());

				$data = array_merge($log_data,$log_meta_data);
				if($data){
					$output['status'] = 'success';
					$output['des']['content'] = $data;
				}else{
					$output['status'] = 'error';
					$output['des']['content'] = __('No found any backup data. ',self::$iden);
				}
				break;
			/** 
			 * download
			 */
			case 'download':
				if(!isset($_GET['file_url'])) die();
				$file_url = $_GET['file_url'];
				$uploads = wp_upload_dir();
				$upload_dir = $uploads['basedir'] . self::$basedir_backup;
				$size = self::get_file_url_meta('size',$file_url);
				$basename = self::get_file_url_meta('basename',$file_url);
				$file_path = $upload_dir . $size . '/' . $basename;
				/**
				 * check the file url
				 */
				if($size && $basename){
					if(file_exists($file_path)){
						$output['des']['content'] = __('The picture will be skip because it is exist. ',self::$iden);
					}else{
						$result = self::get_remote_file($file_url,$file_path);
						if($result){
							$output['des']['content'] = __('The picture downloaded, continue to download next picture... ',self::$iden);
						}else{
							$output['des']['content'] = __('No found the picture from server, skip it and continue to download next picture... ',self::$iden);
						}
					}
					$output['status'] = 'success';
				/**
				 * unknown file url
				 */
				}else{
					$output['status'] = 'error';
					$output['des']['content'] = __('No found any backup data. ',self::$iden);
				}
				break;
			/** 
			 * restore
			 */
			case 'restore':
				if(!isset($_GET['restore_to']) || empty($_GET['restore_to'])) die();
				global $wpdb;
				$restore_to_space = $_GET['restore_to'] === 'space' ? true : false;
				$post_ids = self::log_get_all('post_id');
				/** 
				 * if have not any posts
				 */
				if(!$post_ids){
					$output['status'] = 'error';
					$output['des']['content'] = __('No data to restore.',self::$iden);
					break;
				}
				$image_paths = array();
				$uploads = wp_upload_dir();
				$update_result = null;
				/**
				 * posts
				 */
				foreach($post_ids as $post_id){			
					$image_urls = get_post_meta($post_id,self::$log_key,true);
					$image_server_urls = $image_urls;
					/**
					 * update the old meta key
					 */
					$query = $wpdb->prepare( 
						"
						SELECT 	`post_content` 
						FROM		`$wpdb->posts`
						WHERE		`ID` = '%s'
						",
						$post_id
					);
					$post_content = $wpdb->get_results($query);
					$post_content = $post_content[0]->post_content;
					/**
					 * if have post, continue
					 */
					if($post_content){
						/**
						 * get the space pattern
						 */
						if($restore_to_space){
							$pattern = '/http:\/\/\w+\.sinaimg\.\w+\/\w+\/\w+\.\w+/i';
						/**
						 * get the server pattern
						 */
						}else{
							/**
							 * replace / and . to  \/ and \. from space url
							 */
							$pattern_baseurl = str_ireplace(array('/','.'),array('\/','\.'),$uploads['baseurl'] . self::$basedir_backup);
							$pattern = '/' . $pattern_baseurl . '\w+\/\w+\.\w+/i';
						}
						/**
						 * match the image url from post content
						 */
						preg_match_all($pattern,$post_content,$matches);
						$post_image_urls = isset($matches[0]) && !empty($matches[0]) ? $matches[0] : null;
						/**
						 * if have image in post
						 */
						if(!empty($post_image_urls)){
							/**
							 * refine the $post_image_urls for unique
							 */
							$post_image_urls = array_unique($post_image_urls);
							
							foreach($post_image_urls as $post_image_url){
								/**
								 * if restore to server, get the imgs space_url
								 */
								foreach($image_urls as $tmp_img_url){
									$image_space_urls[] = $uploads['baseurl'] . self::$basedir_backup . self::get_file_url_meta('size',$tmp_img_url) . '/' . self::get_file_url_meta('iden',$tmp_img_url);
								}
								if(!$restore_to_space){
									$image_urls = $image_space_urls;
								}
								/**
								 * search, if found
								 */
								if(array_search($post_image_url,$image_urls) === false){
									continue;
								}
							}/** end foreach */
							/**
							 * update post_content
							 */
							if($restore_to_space){
								$new_post_content = str_replace($image_server_urls,$image_space_urls,$post_content);
							}else{
								$new_post_content = str_replace($image_space_urls,$image_server_urls,$post_content);
							}
							$update_result = $wpdb->query(
								$wpdb->prepare( 
									"
									UPDATE 	`$wpdb->posts` 
									SET 	`post_content` = '%s'
									WHERE 	`ID` = '%s' 
									",
									$new_post_content,
									$post_id
								)
							);
							$output['status'] = 'success';
							$output['des']['content'] = __('Congratulation, the restoration is completed. ',self::$iden);
						/**
						 * is not exists sinaimg in the post
						 */
						}else{
							continue;
						}
					/**
					 * no exists id
					 */
					}else{
						$output['status'] = 'error';
						$output['des']['content'] = __('Invalid post ID.',self::$iden);
					}
				}
				if(empty($update_result)){
					$output['status'] = 'error';
					$output['des']['content'] = __('Seem  nothing needs to restore.',self::$iden);
				}
				break;
			default:
				$output['status'] = 'error';
				$output['des']['content'] = __('Not enough params.',self::$iden);
		
		}

		die(plugin_functions_sinapicv2::json_format($output));
	}
	
	/**
	 * get_config
	 *
	 * @param 
	 * @return 
	 * @version 1.0.0
	 * @author KM@INN STUDIO
	 */
	public static function get_config($key){
		return plugin_functions_sinapicv2::authcode(self::$wb[$key]);
	}
	/**
	 * get_remote_file
	 * 
	 * @param 
	 * @param 
	 * @return 
	 * @version 1.0.0
	 * @example 
	 * @author KM (kmvan.com@gmail.com)
	 * @copyright Copyright (c) 2011-2013 INN STUDIO. (http://www.inn-studio.com)
	 **/
	private static function get_remote_file($file_url = null, $file_path = null){
		if(!$file_url || !$file_path) return false;
		plugin_functions_sinapicv2::mk_dir(dirname($file_path));
		/**
		 * remove some words
		 */
		$file_url = preg_replace( '/(?:^[\'"]+|[\'"\/]+$)/', '', $file_url);
		$ch = curl_init();
		$fp = fopen($file_path,'wb');
		curl_setopt($ch,CURLOPT_URL,$file_url);
		curl_setopt($ch,CURLOPT_FILE,$fp);
		curl_setopt($ch,CURLOPT_HEADER,0);
		curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($ch,CURLOPT_TIMEOUT,60);
		$result = curl_exec($ch);
		curl_close($ch);
		fclose($fp);
		return  $result;
	}
	/**
	 * get_file_url_meta
	 * 
	 * @param string $key
	 * @param string $file_url
	 * @return string image size
	 * @version 1.0.0
	 * @example 
	 * @author KM (kmvan.com@gmail.com)
	 * @copyright Copyright (c) 2011-2013 INN STUDIO. (http://www.inn-studio.com)
	 **/
	private static function get_file_url_meta($key = nulll,$file_url = null){
		$file_url = $file_url ? $file_url : self::$file_url;
		if(!$key || !$file_url) return false;

		$file_url = str_replace('http://','',$file_url);
		$file_obj = explode('/',$file_url);
		$len = count($file_obj);
		
		switch($key){
			/**
			 * size
			 */
			case 'size':
				$size = $file_obj[$len - 2];
				return $size;
				break;
			/**
			 * basename
			 */
			case 'basename':
				$basename = $file_obj[$len - 1];
				return $basename;
				break;
			/**
			 * iden
			 */
			case 'iden':
				$basename = $file_obj[$len - 1];
				return $basename;
				break;
			/**
			 * server
			 */
			case 'server':
				$server = $file_obj[$len - 3];
				return $server;
				break;
			/**
			 * date
			 */
			case 'date':
				$date = date('Y/m/d H:i:s');
				return $date;
				break;
			/**
			 * post_id
			 */
			case 'post_id':
				$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : null;
				return $post_id;
				break;
			/**
			 * post_date
			 */
			case 'date':
				global $post;
				$date = $post->post_date;
				return $date;
				break;
			default:
				
				break;
		}
	}
	
	/**
	 * get_all_log
	 * 
	 * @return 
	 * @example 
	 * @version 1.0.0
	 * @author KM (kmvan.com@gmail.com)
	 * @copyright Copyright (c) 2011-2013 INN STUDIO. (http://www.inn-studio.com)
	 **/
	private static function get_all_log(){
		$ids = self::get_logged_post_ids();
		if(!$ids) return false;
		
	}
	private static function single_array($array){
		static $tmp = array();
		
		if(is_array($array)){
			foreach($array as $key => $value){
				if(is_array($value)){
					self::single_array($value);
				}else{
					$value = @unserialize($value) ? unserialize($value) : $value;
					$tmp[] = $value;
				}
			}
		}else{
			$array = @unserialize($array) ? unserialize($array) : $array;
			$tmp[] = $value;
		}
		return $tmp;
	}
	/**
	 * log_get_all
	 * 
	 * @return 
	 * @example 
	 * @version 1.0.2
	 * @author KM (kmvan.com@gmail.com)
	 * @copyright Copyright (c) 2011-2013 INN STUDIO. (http://www.inn-studio.com)
	 **/
	private static function log_get_all($select = 'meta_value'){
		global $wpdb;
		$metas = null;
		$key = self::$log_key;
		$results = $wpdb->get_results(
			$wpdb->prepare( 
				"
				SELECT		`$select`
				FROM		`$wpdb->postmeta`
				WHERE		`meta_key` = '%s'
				",
				$key
			)
		);
		/**
		 * max 3 dimensional array
		 */
		foreach($results as $result){
			$result_select = @unserialize($result->$select) ? unserialize($result->$select) : $result->$select;
			if(is_array($result_select)){
				foreach($result_select as $tmp){
					$metas[] = $tmp;
				}
			}else{
				$metas[] = $result_select;
			}
		}
		return $metas;
	}
	/**
	 * log_get
	 * 
	 * @param int $post_id
	 * @return array
	 * @version 1.0.0
	 * @example 
	 * @author KM (kmvan.com@gmail.com)
	 * @copyright Copyright (c) 2011-2013 INN STUDIO. (http://www.inn-studio.com)
	 **/
	private static function log_get($post_id = null,$post_content = null){
		if(!$post_id) return false;
		$options = plugin_options_sinapicv2::get_options();
		/**
		 * get images from content
		 */
		if(!$post_content){
			$post = get_post((int)$post_id);
			$post_content = $post->post_content;
		}
		$pattern = '/http:\/\/\w+\.sinaimg\.\w+\/\w+\/\w+\.\w+/i';
		preg_match_all($pattern,$post_content,$matches);
		$results = $matches[0];
		/**
		 * get images from meta
		 */
		$post_meta = get_post_meta($post_id,$options['feature_meta'],true);
		$results = $results ? array_unique($results) : array();
		if(!empty($post_meta)) $results[] = $post_meta;
		$results = !empty($results) ? array_unique($results) : null;
		return $results;
	}
	
	
	/**
	 * log_write
	 * 
	 * @param int $post_id
	 * @param array $log_data
	 * @return n/a
	 * @version 1.0.0
	 * @example 
	 * @author KM (kmvan.com@gmail.com)
	 * @copyright Copyright (c) 2011-2013 INN STUDIO. (http://www.inn-studio.com)
	 **/
	private static function log_write($post_id = null,$log_data = null){
		if(!$post_id) return false;
		if(!$log_data || empty($log_data)){
			delete_post_meta($post_id,self::$log_key);
		}else{
			update_post_meta($post_id,self::$log_key,$log_data);
		}
	}
	/**
	 * log_meta_write
	 * 
	 * @return 
	 * @example 
	 * @version 1.0.0
	 * @author KM (kmvan.com@gmail.com)
	 * @copyright Copyright (c) 2011-2013 INN STUDIO. (http://www.inn-studio.com)
	 **/
	private static function log_meta_write($post_id = null,$img_url = null){
		if(!$post_id) return false;
		if(!$img_url){
			delete_post_meta($post_id,self::$log_meta_key);
		}else{
			update_post_meta($post_id,self::$log_meta_key,$img_url);
		}
	}
	/**
	 * log_meta_get
	 * 
	 * @return 
	 * @example 
	 * @version 1.0.0
	 * @author KM (kmvan.com@gmail.com)
	 * @copyright Copyright (c) 2011-2013 INN STUDIO. (http://www.inn-studio.com)
	 **/
	private static function log_meta_get($post_id = null){
		if(!$post_id) return false;
		$meta = get_post_meta($post_id,self::$log_meta_key,true);
		return $meta;
	}
	/**
	 * log_all_meta_get
	 * 
	 * @return 
	 * @example 
	 * @version 1.0.2
	 * @author KM (kmvan.com@gmail.com)
	 * @copyright Copyright (c) 2011-2013 INN STUDIO. (http://www.inn-studio.com)
	 **/
	private static function log_all_meta_get($select = 'meta_value'){
		global $wpdb;
		$metas = null;
		$key = self::$feature_image_key;
		$results = $wpdb->get_results(
			$wpdb->prepare( 
				"
				SELECT		`$select`
				FROM		`$wpdb->postmeta`
				WHERE		`meta_key` = '%s'
				",
				$key
			)
		);
		return $results;
	}

	/**
	 * admin_options_page
	 * 
	 * @return 
	 * @version 1.0.1
	 * @author KM@INN STUDIO
	 */
	public static function admin_options_page(){
		/* Add to theme setting menu */
		add_plugins_page(
			__('SinaPic v2 options',self::$iden),
			__('SinaPic v2 options',self::$iden), 
			'manage_options', 
			self::$iden . '-options',
			get_class() . '::admin_display'
		);
	}
	public static function admin_default($options){
		$options['feature_meta'] = 'sinapic_feature_meta';
		return $options;
	}
	/**
	 * backend_options_save
	 * 
	 * @params array options
	 * @return array options
	 * @version 1.0.0
	 * @author KM@INN STUDIO
	 */
	public static function backend_options_save($options){
		global $wpdb,$wp_query,$post;
		// $options_authorize = self::get_options('authorize');
		
		$options = isset($_POST['sinapic']) ? $_POST['sinapic'] : null;
		$options['feature_meta'] = isset($_POST['sinapic']['feature_meta']) && !empty($_POST['sinapic']['feature_meta'])? trim($_POST['sinapic']['feature_meta']) : 'sinapic_feature_meta';
		$new_meta = $options['feature_meta'];
		/**
		 * check the new and old meta key from $_POST
		 */
		$old_meta = $_POST['sinapic']['old_meta'];
		if($old_meta !== $new_meta){
			/**
			 * update the old meta key
			 */
			$wp_query = new WP_Query(array(
				'meta_key' => $old_meta,
			));
			if(have_posts()){
				while(have_posts()){
					the_post();
					$meta_v = get_post_meta($post->ID,$old_meta,true);
					delete_post_meta($post->ID,$old_meta);
					add_post_meta($post->ID,$new_meta,$meta_v);
				}
			}
			wp_reset_postdata();
			wp_reset_query();
			
			// $wpdb->query(
				// $wpdb->prepare( 
					// "
					// UPDATE 	`$wpdb->postmeta` 
					// SET 	`meta_key` = '%s'
					// WHERE 	`meta_key` = '%s' 
					// ",
					// $new_meta,
					// $old_meta
				// )
			// );
			
		}
		// $options['authorize'] = $options_authorize;
		return $options;
	}
	/**
	 * admin_display
	 * 
	 * @return string
	 * @version 1.0.0
	 * @author KM@INN STUDIO
	 */
	public static function admin_display(){
		$options = plugin_options_sinapicv2::get_options();/* get the options */
		$feature_meta = $options['feature_meta']; 
		$auto_backup = isset($options['auto_backup']) ? ' checked="checked" ' : null;

		$file_count = self::log_get_all();
		
		$backup_dir = wp_upload_dir();
		$backup_dir = $backup_dir['basedir'] . self::$basedir_backup;
		/**
		 * takes_time
		 */
		$file_count = count($file_count);
		if($file_count){
			$seconds = floatval($file_count * 3.62);
			$minutes = $seconds > 60 ? $seconds / 60 : 0;
			$hours = $minutes > 60 ? $minutes / 60 : 0;
			if($minutes){
				$takes_time_backup = sprintf(__('%s minutes',self::$iden),$minutes);
			}else if($hours){
				$takes_time_backup = sprintf(__('%s hours',self::$iden),$hours);
			}else{
				$takes_time_backup = sprintf(__('%s seconds',self::$iden),$seconds);
			}
		}else{
			$takes_time_backup = null;
		}
		/**
		 * restore takes time
		 */
		$post_ids = self::log_get_all('post_id');
		$post_count = count($post_ids);
		if($post_count){
			$seconds = floatval($post_count * 0.17);
			$minutes = $seconds > 60 ? $seconds / 60 : 0;
			$hours = $minutes > 60 ? $minutes / 60 : 0;
			if($minutes){
				$takes_time_restore = sprintf(__('%s minutes',self::$iden),$minutes);
			}else if($hours){
				$takes_time_restore = sprintf(__('%s hours',self::$iden),$hours);
			}else{
				$takes_time_restore = sprintf(__('%s seconds',self::$iden),$seconds);
			}
		}else{
			$takes_time_restore = null;
		}
		$plugin_data = get_plugin_data(__FILE__);
		
		
		/** 
		 * authorize
		 */
		if(self::is_authorized()){
			$current_user_id = get_current_user_id();
			$authorization = (array)get_user_meta($current_user_id,self::$key_authorization,true);
			$auth_info = date('Y-m-d',(int)$authorization['access_time'] + (int)$authorization['expires_in']);
		}else{
			$auth_info = __('Unauthorize',self::$iden);
		}
		$auth_link = self::get_authorize_uri();
		/**
		 * load js and css
		 */
		echo plugin_features_sinapicv2::get_plugin_css('admin','normal');
		echo plugin_features_sinapicv2::get_plugin_js('jquery.kandytabs',false);
		echo plugin_features_sinapicv2::get_plugin_js('admin',false);
		?>
		<script>
		admin.config.process_url = '<?php echo plugin_features_sinapicv2::get_process_url(
			array(
				'action' => self::$iden
			)
		);?>';
		admin.config.lang.E00001 = '<?php _e('Error code: ',self::$iden);?>';
		admin.config.lang.E00002 = '<?php _e('Program error, can not continue to operate. Please try again or contact author. ',self::$iden);?>';
		admin.config.lang.M00001 = '<?php _e('Getting backup config data, please wait... ',self::$iden);?>';
		admin.config.lang.M00002 = '<?php _e('Current processing: ',self::$iden);?>';
		admin.config.lang.M00003 = '<?php _e('Downloading, you can restore the pictures to post after the download is complete. ',self::$iden);?>';
		admin.config.lang.M00005 = '<?php _e('Download completed, you can restore the pictures to post. ',self::$iden);?>';
		admin.config.lang.M00010 = '<?php _e('The data is being restored , please wait...  ',self::$iden);?>';
		admin.init();
		</script>
		<div class="wrap">
			<h2><?php _e('SINAPICV2 OPTIONS',self::$iden);?></h2>
			
			<?php if(isset($_GET['updated']) && $_GET['updated'] == 'true'){?>
			<div id="settings_updated">
				<?php echo plugin_functions_sinapicv2::status_tip('success',__('Settings have been saved.',self::$iden));?>
			</div>
			<?php } ?>
			<?php
			/**
			 * loading
			 */
			echo '<div class="admin_tab_loading">' . plugin_functions_sinapicv2::status_tip('loading',__('Loading...',self::$iden)) . '</div>';
			?>
			<form id="plugin_options_frm" class="hide" method="post">
				<dl id="admin_tab" class="admin_tab">
					<dt><span class="title_settings"><i class="base_settings"></i><?php _e('Base Setting',self::$iden);?></span></dt>
					<dd>
						
						<fieldset>
							<legend><?php _e('Authorization information',self::$iden);?></legend>
							<table class="form-table">
								<tbody>
									<tr>
										<th><?php _e('Authorization expires time',self::$iden);?></th>
										<td>
											<?php echo $auth_info;?>
										</td>
									</tr>
									<tr>
										<th><?php _e('Authorization Link',self::$iden);?></th>
										<td>
											<a href="<?php echo $auth_link;?>" target="_blank" ><?php echo __('Click here to authorize',self::$iden);?></a>
										</td>
									</tr>
								</tbody>
							</table>				
						</fieldset>
						
						<fieldset>
							<legend><?php _e('Plugin settings',self::$iden);?></legend>
							<p class="description">
								<?php _e('If your theme supports feature thumbnail form post meta and you want to use it, please tell the SinaPic what is the post meta name(key). Fill in the text area.',self::$iden);?>
							</p>
							<table class="form-table">
								<tbody>
									<tr>
										<th><label for="sinapic_feature_meta"><?php _e('Feature thumbnail meta name: ',self::$iden);?></label></th>
										<td>
											<input id="sinapic_feature_meta" name="sinapic[feature_meta]" type="text" class="regular-text" value="<?php echo $feature_meta;?>"/>
											<input type="hidden" name="sinapic[old_meta]" value="<?php echo $feature_meta;?>"/>
											<span class="description"><?php _e('Default: ',self::$iden);?>sinapic_feature_meta</span>
										</td>
									</tr>
									<tr>
										<th><label for="sinapic_destroy_after_upload"><?php _e('Delete after upload',self::$iden);?></label></th>
										<td>
											
											<?php
											$destroy_after_upload_checkbox = isset($options['destroy_after_upload']) ? ' checked ' : null;
											?>
											<label for="sinapic_destroy_after_upload">
												<input type="checkbox" name="sinapic[destroy_after_upload]" id="sinapic_destroy_after_upload" <?php echo $destroy_after_upload_checkbox;?>/>
												<span class="description"><?php _e('After upload a message and it will be destroy if enable',self::$iden);?></span>
											</label>
										</td>
									</tr>
								</tbody>
							</table>						
						</fieldset>					
						<fieldset>
							<legend><?php _e('Backup & bestore',self::$iden);?></legend>
							<p class="description"><?php _e('With version 1.1 or higher, the picture will be recorded when upload successful. Now, SinaPic can download the pictures from picture server to your host space. And you can restore the pictures to post if SinaPic have downloaded.',self::$iden);?></p>
							<p class="description"><?php echo sprintf(__('The backup pictures will be saved to <strong>%s</strong> in your host.',self::$iden),$backup_dir);?></p>
							<table class="form-table">
								<tbody>
									<tr>
										<th>
											<p><?php _e('Backup pictures: ',self::$iden);?></p>
											<p><?php _e('server &rarr; my space',self::$iden);?></p>
										</th>
										<td>
											<div id="sinapic_backup_area">
												<div id="sinapic_backup_tip" class="hide"></div>
												<p><button id="sinapic_backup_btn" class="button"><?php _e('click to start BACKUP',self::$iden);?></button></p>
											</div>
												<p class="description">
													<?php 
													if($takes_time_backup){
														echo sprintf(__('Now %s picture(s) have been recorded. They maybe need %s to complete the download.',self::$iden),'<strong>' . $file_count . '</strong>','<strong>' . $takes_time_backup . '</strong>');
													}else{
														_e('No picture was recorded.',self::$iden);
													}
													?>
												</p>
										</td>
									</tr>
									
									<tr>
										<th>
											<p><?php _e('Restore pictures',self::$iden);?></p>
											<p><?php _e('server &harr; my space: ',self::$iden);?></p>
										</th>
										<td>
											<div id="sinapic_restore_area">
												<div id="sinapic_restore_tip" class="hide"></div>
												<p id="sinapic_restore_btns_area">
													<button id="sinapic_restore_btn_1"  class="button"><?php _e('server &rarr; my space',self::$iden);?></button>
													<button id="sinapic_restore_btn_2"  class="button"><?php _e('my space &rarr; server',self::$iden);?></button>
													
												</p>
											</div>
												<p class="description">
													<?php
													if($takes_time_restore){
														echo sprintf(__('Now  %s post(s) have been recorded. They maybe need %s to complete the restore.',self::$iden),'<strong>' . $post_count . '</strong>','<strong>' . $takes_time_restore . '</strong>');
													}else{
														_e('No picture was recorded.',self::$iden);
													}
													?>
												</p>
										</td>
									</tr>
								</tbody>
							</table>
						</fieldset>
					</dd>
					
					<dt><span class="title_settings"><i class="help_settings"></i><?php _e('About',self::$iden);?></span></dt>
					<dd>
						<fieldset>
							<legend><?php echo __('Plugin Information',self::$iden);?></legend>
							<table class="form-table">
								<tbody>
									<tr>
										<th><?php _e('Plugin name: ',self::$iden);?></th>
										<td>
											<strong><?php echo $plugin_data['Name'];?></strong>
										</td>
									</tr>
									<tr>
										<th><?php _e('Plugin version: ',self::$iden);?></th>
										<td>
											<?php echo $plugin_data['Version'];?>
										</td>
									</tr>
									<tr>
										<th><?php _e('Plugin description: ',self::$iden);?></th>
										<td>
											<?php echo $plugin_data['Description'];?>
										</td>
									</tr>
									<tr>
										<th><?php _e('Plugin home page: ',self::$iden);?></th>
										<td>
											<?php echo $plugin_data['Title'];?>
										</td>
									</tr>
									<tr>
										<th><?php _e('Author home page: ',self::$iden);?></th>
										<td>
											<?php echo $plugin_data['Author'];?>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php _e('Feedback and technical support: ',self::$iden);?></th>
										<td>
											<p><?php _e('E-Mail: ',self::$iden);?><a href="mailto:kmvan.com@gmail.com">kmvan.com@gmail.com</a></p>
											<p>
												<?php _e('QQ (for Chinese users): ',self::$iden);?><a target="_blank" href="http://wpa.qq.com/msgrd?v=3&uin=272778765&site=qq&menu=yes">272778765</a>
											</p>
											<p>
												<?php echo __('QQ Group (for Chinese users):',self::$iden);?>
												<a href="http://wp.qq.com/wpa/qunwpa?idkey=d8c2be0e6c2e4b7dd2c0ff08d6198b618156d2357d12ab5dfbf6e5872f34a499" target="_blank">170306005</a>
											</p>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php _e('Donate a coffee: ',self::$iden);?></th>
										<td>
											<p>
												<a id="paypal_donate" href="https://www.paypal.com/cgi-bin/webscr" title="<?php echo __('Donation by Paypal',self::$iden);?>">
													<img src=" https://www.paypalobjects.com/<?php echo WPLANG;?>/i/btn/btn_donate_LG.gif" alt="<?php echo __('Donation by Paypal',self::$iden);?>"/>
												</a>
												<a id="alipay_donate" target="_blank" href="http://ww3.sinaimg.cn/large/686ee05djw1eihtkzlg6mj216y16ydll.jpg" title="<?php echo __('Donation by Alipay',self::$iden);?>">
													<img width="92" height="27"src="https://img.alipay.com/pa/img/home/logo-alipay-t.png" alt="<?php echo __('Donation by Alipay',self::$iden);?>"/>
												</a>
											</p>
										</td>
									</tr>
								</tbody>
							</table>		
						</fieldset>
					</dd>
				</dl>

				<p class="submit">
					<input type="hidden" value="save_options" name="action" />
					<input type="submit" value="<?php _e('Save all settings',self::$iden);?>" class="button button-primary"/>
				</p>				
			</form>
		</div>
		<?php
	}
	/**
	 * meta_box_add
	 * 
	 * @return n/a
	 * @version 1.0.0
	 * @author KM@INN STUDIO
	 */
	public static function meta_box_add(){
		$screens = array( 'post', 'page' );
		foreach($screens as $screen){
			add_meta_box(
				self::$iden,
				__('SinaPic v2 - Best Image Hosting Plugin for WP',self::$iden),
				get_class() . '::meta_box_display',
				$screen
			);
		}
	}
	/**
	 * meta_box_save
	 * 
	 * @params int $post_id
	 * @return n/a
	 * @version 1.0.1
	 * @author KM@INN STUDIO
	 */
	public static function meta_box_save($post_id){
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
		if(!current_user_can('edit_post',$post_id)) return;
		/** 
		 * when post publish
		 */
		if(isset($_POST['publish'])){
			/**
			 * add meta value
			 */
			if(isset($_POST['sinapic']) && isset($_POST['sinapic']['as_feature_image'])){
				$options = plugin_options_sinapicv2::get_options();
				$img_id = $_POST['sinapic']['as_feature_image'];
				$img_url = isset($_POST['sinapic'][$img_id]) ? $_POST['sinapic'][$img_id] : null;
				if(isset($options['feature_meta']) && !empty($options['feature_meta'])){
					$key = $options['feature_meta'];
				}else{
					$key = self::$feature_image_key;
				}
				update_post_meta($post_id,$key,$img_url);
				/**
				 * add meta log
				 */
				self::log_meta_write($post_id,$img_url);
			}
			/**
			 * write log
			 */
			self::log_write($post_id,self::log_get($post_id,$_POST['post_content']));
		}
	}
	/**
	 * get_authorize_uri
	 *
	 * @return string
	 * @version 1.0.0
	 * @author KM@INN STUDIO
	 */
	private static function get_authorize_uri(){
		$authorize_uri_obj = array(
			'uri' => plugin_features_sinapicv2::get_process_url(
				array(
					'action' => self::$iden,
					'type' => 'set_authorize'
				)
			)
		);
		$authorize_uri = self::get_config(2) . http_build_query($authorize_uri_obj);	
		return $authorize_uri;
	}
	/**
	 * meta_box_display
	 * 
	 * @return string HTML
	 * @version 1.0.2
	 * @author KM@INN STUDIO
	 */
	public static function meta_box_display(){
		global $post;
		$options = plugin_options_sinapicv2::get_options();
		/** 
		 * authorize_uri
		 */
		$authorize_uri = self::get_authorize_uri();
		$authorized_js = self::is_authorized() ? 'true' : 'false';
		
		self::css();
		?>
		<script src="<?php echo plugin_features_sinapicv2::get_plugin_js('init');?>"></script>
		<script>
		jQuery(document).ready(function(){
			/**
			 * sinapic_upload init
			 */
			sinapic_upload.config.process_url = '<?php echo plugin_features_sinapicv2::get_process_url(array(
				'action' => self::$iden,
				'post_id' => $post->ID
			));?>';
			sinapic_upload.config.post_id = '<?php echo $post->ID;?>';
			sinapic_upload.config.lang.E00001 = '<?php _e('Error: ',self::$iden);?>';
			sinapic_upload.config.lang.E00002 = '<?php _e('Upload failed, please try again. If you still failed, please contact the plugin author.',self::$iden);?>';
			sinapic_upload.config.lang.E00003 = '<?php _e('Sorry, plugin can not get authorized data, please try again later or contact plugin author.',self::$iden);?>';
			sinapic_upload.config.lang.M00001 = '<?php _e('Uploading {0}/{1}, please wait...',self::$iden);?>';
			sinapic_upload.config.lang.M00002 = '<?php _e('{0} files have been uploaded, enjoy it.',self::$iden);?>';
			sinapic_upload.config.lang.M00003 = '<?php _e('Image URL: ',self::$iden);?>';
			sinapic_upload.config.lang.M00004 = '<?php _e('ALT attribute: ',self::$iden);?>';
			sinapic_upload.config.lang.M00005 = '<?php _e('Set ALT attribute text',self::$iden);?>';
			sinapic_upload.config.lang.M00006 = '<?php _e('Control: ',self::$iden);?>';
			sinapic_upload.config.lang.M00007 = '<?php _e('Insert to post with link',self::$iden);?>';
			sinapic_upload.config.lang.M00008 = '<?php _e('Insert to post image only',self::$iden);?>';
			sinapic_upload.config.lang.M00009 = '<?php _e('As custom meta feature image',self::$iden);?>';
			sinapic_upload.config.authorized = <?php echo $authorized_js;?>;
			sinapic_upload.config.sizes = {
				thumb150 	: '<?php _e('max 150x150, crop',self::$iden);?>',
				mw600 		: '<?php _e('max-width:600',self::$iden);?>',
				large 		: '<?php _e('organize size',self::$iden);?>',
				square 		: '<?php _e('max-width:80 or max-height:80',self::$iden);?>',
				thumbnail 	: '<?php _e('max-width:120 or max-height:120',self::$iden);?>',
				bmiddle 	: '<?php _e('max-width:440',self::$iden);?>'
			};
			sinapic_upload.init();
		});
		</script>
		<div id="sinapic">
			<div id="sinapic_upload_area">
				<div id="sinapic_loading_tip">
					<?php echo plugin_functions_sinapicv2::status_tip('loading',__('Loading, please wait...',self::$iden));?>
				</div>
				<div id="sinapic_unauthorize">
					<?php echo plugin_functions_sinapicv2::status_tip('info',sprintf(__('Sorry, SinaPicV2 needs to authorize from your WeiBo account, <a href="%s" target="blank" id="sinapic_go_authorize" target="_blank"><b>please click here to authorize</b></a>.<br/>If you has authorized just now, <a href="javascript:void(0);" id="sinapic_reload"><b>please click here to reload me</b></a>.',self::$iden),$authorize_uri));?>
				</div>
				<div id="sinapic_btns">
					<input class="button sinapic_add_new" id="sinapic_add_new" value="<?php _e('Select or Drag picture(s) to upload (Maximum 5MB/p)',self::$iden);?>" />
					<input type="file" id="sinapic_upload_new" name="sinapic_upload_new" class="sinapic_upload_new" accept="image/gif,image/jpeg,image/png" multiple="true" />
				</div>
				<div id="sinapic_completion_tip"></div>
				<div id="sinapic_tools">
					<a id="insert_list_with_link" href="javascript:void(0);" class="button button-primary"><?php _e('Insert to post from list with link',self::$iden);?></a>
					<a id="insert_list_without_link" href="javascript:void(0);" class="button"><?php _e('Insert to post from list without link',self::$iden);?></a>
					
					<select id="separate_img_type">
						<option value="0"><?php echo __('Do not use separate',self::$iden);?></option>
						<option value="nextpage"><?php echo __('Use "Next page" tag',self::$iden);?></option>
					</select>

					
					<a href="javascript:void(0);" id="clear_list"><?php _e('Clear list',self::$iden);?></a>
				</div>
			</div>
			<div id="sinapic_pics"><div id="tpl"></div></div>
		</div>
	<?php
	}
	/**
	 * status_tip
	 *
	 * @param string|mixed
	 * @return string
	 * @version 1.0.0
	 * @author KM@INN STUDIO
	 */
	public static function status_tip(){
		return plugin_functions_sinapicv2::status_tip(func_get_args());
	}
	/**
	 * is_authorized
	 *
	 * @return bool
	 * @version 1.0.0
	 * @author KM@INN STUDIO
	 */
	public static function is_authorized(){
		$current_user_id = get_current_user_id();
		$authorization = (array)get_user_meta($current_user_id,self::$key_authorization,true);
		/** 
		 * if authorized
		 */
		if(isset($authorization['access_token']) && 
			((int)$authorization['access_time'] + (int)$authorization['expires_in']) > time()){
			return true;
		}else{
			return false;
		}
	}
	/** 
	 * footer_info
	 */
	public static function footer_info(){
		echo '<!-- ' . sprintf(__('Image uploader by %s@INN STUDIO',self::$iden),__('Sina Pic v2',self::$iden)) . ' -->';
	}
}
?>