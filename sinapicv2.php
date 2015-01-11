<?php
/*
Plugin Name: Sina Pic v2
Plugin URI: http://inn-studio.com/sinapicv2
Description: Best Image Hosting Plugin for WP. Upload your picture to sina weibo and show it on your site.
Author: INN STUDIO
Author URI: http://inn-studio.com
Version: 2.0.1
Text Domain:sinapicv2
Domain Path:/languages
*/

require_once(plugin_dir_path(__FILE__) . 'core/core-functions.php');
require_once(plugin_dir_path(__FILE__) . 'core/core-options.php');
require_once(plugin_dir_path(__FILE__) . 'core/core-features.php');


add_action('plugins_loaded','sinapicv2::init');

class sinapicv2{

	public static $iden = 'sinapicv2';
	
	private static $plugin_features = 'plugin_features_sinapicv2';
	private static $plugin_functions = 'plugin_functions_sinapicv2';
	private static $plugin_options = 'plugin_options_sinapicv2';
	
	private static $basedir_backup = '/sinapicv2-backup/';
	private static $log_key = '_sinapic_log';
	private static $log_meta_key = '_sinapic_meta_log';
	private static $feature_image_key = 'sinapic_feature_image';
	private static $allow_types = array('png','gif','jpg','jpeg');
	private static $log_data = null;
	private static $file_url = null;
	private static $key_authorization = '_sinapic_authorization';
	private static $b = array('a','b','c','d','e');
	private static $p = array('_');
	private static $d = array('1','2','3','4','5','6');
	private static $q = array('s','o');
	private static $wb = array('514aj1aPKgLZMu80Divag82C/8O1A+OA9liLjSXmQd+cE8FGHnXG','9e25KNBWUg5lLjSaXSaAs4EYFF4Wz0kR1f3Q+Hex+0YglMhOo+2L1VUpj6JwBp0CrqXUBMquIZFTubYtdg','4875UnQ0A6s38/Ra/hlOAC7BqbS/5AwQhQjFsfuiQRlaCggd/7GnXiUaafN8itsl6m9IaQRmp0F16h2B4fv4+zwIpmirNwOeeUl7CKaN7yZpTV/lIruW');
	private static $available_times = 10;
	private static $cache_backup_files = array();
	private static function tdomain(){
		load_plugin_textdomain(self::$iden,false,dirname(plugin_basename(__FILE__)). '/languages/');
		$header_translate = array(
			'plugin_name' => __('SinaPic v2',self::$iden),
			'plugin_uri' => __('http://inn-studio.com/sinapicv2',self::$iden),
			'description' => __('Best Image Hosting Plugin for WP. Upload your picture to sina weibo and show it on your site.',self::$iden),
			'author_uri' => __('http://inn-studio.com',self::$iden),
		);
	}
	private static function update(){
		if(!class_exists('PluginUpdateChecker')){
			include plugin_dir_path(__FILE__) . 'inc/update.php';
		}
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
		add_filter('plugin_options_default_' . self::$iden,get_class() . '::options_default');
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
		add_action('admin_menu',get_class() . '::add_backend_page');
		add_action('wp_footer',get_class() . '::footer_info');
		
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
		echo call_user_func(array(self::$plugin_features,'get_plugin_css'),'style','normal');
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
	 * @version 1.1.0
	 * @author KM@INN STUDIO
	 */
	private static function get_options($key = null){
		return call_user_func(array(self::$plugin_options,'get_options'),$key);
	}
	/**
	 * Get sina image pattern
	 *
	 * @return string Pattern
	 * @version 1.0.0
	 * @author KM@INN STUDIO
	 */
	private static function get_sinaimg_pattern(){
		return '/\w+:\/\/\w+\.sinaimg\.cn\/\w+\/\w+\.\w+/i';
	}
	private static function get_localimg_pattern($with_http = false){
		$upload_dir = wp_upload_dir();
		$prefix = $with_http ? addcslashes($upload_dir['baseurl'] . self::$basedir_backup,'/.') : null;
		return '/' . $prefix . '[0-9]+\-\w+\-\w+\-\w+\.\w+/i';
	}
	/**
	 * Get sina image url by local image url
	 *
	 * @param string $localimg_url eg.http://xx.com/wp-content/uploads...xx.jpg
	 * @return string The sina image url
	 * @version 1.0.0
	 * @author KM@INN STUDIO
	 */
	private static function get_sinaimg_url_by_localimg($localimg_url){
		$protocol  = self::get_options('is_ssl') ? 'https://' : 'http://';
		return $protocol . self::get_local_file_meta($localimg_url,'subdomain') . '.sinaimg.cn/' . self::get_local_file_meta($localimg_url,'size') . '/' . self::get_local_file_meta($localimg_url,'basename');
	}
	/**
	 * Get remote images url
	 *
	 * @param string $content Post content or post meta
	 * @return array Images url of array/An empty array
	 * @version 1.0.0
	 * @author KM@INN STUDIO
	 */
	private static function get_sinaimg_urls($content = null){
		if(!$content) return false;
		preg_match_all(self::get_sinaimg_pattern(),$content,$matches);
		return $matches[0];
	}
	/**
	 * Get local image path by sina image url
	 *
	 * @param string $sinaimg_url Sina image url
	 * @param int $postid The post id of image
	 * @return string Local image url
	 * @version 1.0.0
	 * @author KM@INN STUDIO
	 */
	private static function get_localimg_path_by_sinaimg($sinaimg_url,$postid = null){
		if(!$postid){
			global $post;
			$postid = $post->ID;
		}
		$upload_dir = wp_upload_dir();
		$backup_dir = $upload_dir['basedir'] . self::$basedir_backup;
		$basename = implode('-',array(
			$postid,
			self::get_file_url_meta('subdomain',$sinaimg_url),
			self::get_file_url_meta('size',$sinaimg_url),
			self::get_file_url_meta('basename',$sinaimg_url)
		));
		$file_path = $backup_dir . $basename;
		return $file_path;
	}
	/**
	 * Get local image url by sina image url
	 *
	 * @param string $sinaimg_url Sina image url
	 * @param int $postid The post id of image
	 * @return string Http image url
	 * @version 1.0.0
	 * @author KM@INN STUDIO
	 */
	private static function get_localimg_url_by_sinaimg($sinaimg_url,$postid = null){
		if(!$postid){
			global $post;
			$postid = $post->ID;
		}
		$upload_dir = wp_upload_dir();
		$backup_dir = $upload_dir['baseurl'] . self::$basedir_backup;
		$basename = implode('-',array(
			$postid,
			self::get_file_url_meta('subdomain',$sinaimg_url),
			self::get_file_url_meta('size',$sinaimg_url),
			self::get_file_url_meta('basename',$sinaimg_url)
		));
		$file_path = $backup_dir . $basename;
		return $file_path;
	}
	/**
	 * process
	 * 
	 * @params 
	 * @return 
	 * @version 1.0.1
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
		@set_time_limit(0);
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
						' . call_user_func(array(self::$plugin_functions,'status_tip'),'success',__('Congratulation, SinaPicV2 has been authorized!',self::$iden) . '<br/><a href="javascript:window.open(false,\'_self\',false);window.close();">' . __('Close this window and reload the plugin UI.',self::$iden) . '</a>') . '
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
					$output['msg'] = __('Authorized.',self::$iden);
				}else{
					$output['status'] = 'error';
					$output['msg'] = __('Unauthorize.',self::$iden);
				}
				break;
			/** 
			 * upload pic
			 */
			case 'upload':

				$file = isset($_FILES['file']) ? $_FILES['file'] : array();
				$file_name = isset($file['name']) ? $file['name'] : null;
				$file_type = isset($file['type']) ? explode('/',$file['type']) : array(); /** fuck you php 5.3 */
				$file_type = !empty($file_type) ? $file_type[1] : null;
				$tmp_name = isset($file['tmp_name']) ? $file['tmp_name'] : null;
				/** 
				 * check upload error
				 */
				if(!isset($file['error']) || $file['error'] != 0){
					$output['status'] = 'error';
					$output['msg'] = sprintf(__('Upload failed, file has an error code: %s',self::$iden),$file['code']);
					$output['code'] = 'file_has_error_code';
					self::die_json_format($output);
				}
				/** 
				 * check file params
				 */
				if(!$file_name || !$file_type || !$tmp_name){
					$output['status'] = 'error';
					$output['msg'] = __('Not enough params.',self::$iden);
					$output['code'] = 'not_enough_params';
					self::die_json_format($output);
				}
				/** 
				 * check file type
				 */
				if(!in_array($file_type,self::$allow_types)){
					$output['status'] = 'error';
					$output['msg'] = __('Invalid file type.',self::$iden);
					$output['code'] = 'invalid_file_type';
					self::die_json_format($output);
				}
				/** 
				 * check authorization
				 */
				if(!self::is_authorized()){
					$output['status'] = 'error';
					$output['code'] = 'no_authorize';
					$output['msg'] = __('Please use your Sina Weibo account to authorize the plugin.',self::$iden);
					self::die_json_format($output);
				}
				include dirname(__FILE__) . '/inc/saetv2.ex.class.php';
				$authorization = (array)get_user_meta(get_current_user_id(),self::$key_authorization,true);
				
				
				// $file_name = time() . rand(100,999) . '.' . $file_ext;
				// $tmpfile = tempnam(null,self::$iden);
				
				// $uploads = wp_upload_dir();
				// $upload_dir = $uploads['basedir'] . '/';
				// $upload_url = $uploads['baseurl'] . '/';
				// file_put_contents($tmpfile,$file_b64);
				// $file_url = $upload_url . $file_name;
				
				$c = new SaeTClientV2(self::get_config(0),self::get_config(1),$authorization['access_token']);
				$callback = $c->upload(current_time('Y-m-d H:i:s ' . rand(100,999)) . __('Upload by Sinapicv2',self::$iden),$tmp_name);
				
				unlink($tmp_name);

				/** 
				 * get callback
				 */
				if(is_array($callback) && isset($callback['bmiddle_pic'])){
					$output['status'] = 'success';
					$output['img_url'] = isset($options['is_ssl']) && $options['is_ssl'] == 1 ? str_ireplace('http://','https://',$callback['bmiddle_pic']) : $callback['bmiddle_pic'];
					/** 
					 * destroy after upload 
					 */
					if(isset($options['destroy_after_upload'])){
						sleep(1);
						$c->destroy($callback['id']);
					}
				/** 
				 * got callback error code
				 */
				}else if(is_array($callback) && isset($callback['error_code'])){
					$output['status'] = 'error';
					$output['msg'] = $callback['error'];
				/** 
				 * unknown error
				 */
				}else{
					ob_start();
					var_dump($callback);
					$detail = ob_get_contents();
					ob_end_clean();
					
					$output['status'] = 'error';
					$output['code'] = 'unknown';
					$output['detail'] = $detail;
					$output['msg'] = sprintf(__('Sorry, upload failed. Please try again later or contact the plugin author. The reasons for this situation maybe the Weibo server does not receive the file from your server. Weibo returns an error message: %s',self::$iden),json_encode($callback));
					// var_dump($callback);
					// die();
				}
			
				break;
			/** 
			 * get backup data
			 */
			case 'get_backup_data':
				if(!current_user_can('manage_options')){
					$output['status'] = 'error';
					$output['code'] = 'error_permission';
					$output['msg'] = __('Security permission was insufficient to operate, please make sure you are administrator.');
					self::die_json_format($output);
				}
				
				/** 
				 * get all post
				 */
				global $wp_query,$post;
				
				$posts = array();
				$wp_query = new WP_Query(array(
					'nopaging' => true,
					'post_type' => 'any',
					'ignore_sticky_posts' => true,
					
				));
				if(have_posts()){
					while(have_posts()){
						the_post();
						/** 
						 * match sina images from post content
						 */
						$urls = self::get_sinaimg_urls($post->post_content);
						if(!empty($urls)){
							$posts[] = array(
								'id' => $post->ID,
								'imgs' => array_unique($urls),
							);
						}
					}
				}else{
				}
				wp_reset_postdata();
				wp_reset_query();
				if(empty($posts)){
					$output['status'] = 'error';
					$output['code'] = 'no_post';
					$output['msg'] = __('Not post can be match to backup.');
				}else{
					$output['status'] = 'success';
					$output['posts'] = $posts;
				}
				
				break;
			/** 
			 * download
			 */
			case 'download':
				if(!current_user_can('manage_options')){
					$output['status'] = 'error';
					$output['code'] = 'error_permission';
					$output['msg'] = __('Security permission was insufficient to operate, please make sure you are administrator.');
					self::die_json_format($output);
				}
				$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : null;
				$file_url = isset($_GET['img_url']) ? $_GET['img_url'] : null;
				if(!$post_id){
					$output['status'] = 'error';
					$output['code'] = 'invalid_post_id';
					$output['msg'] = __('No found any post.');
					self::die_json_format($output);
				}
				if(!$file_url){
					$output['status'] = 'error';
					$output['code'] = 'no_img';
					$output['msg'] = __('No any image to download');
					self::die_json_format($output);
				}

				/** 
				 * $local_basename eg. 1-ww2-square-5dd1...0xck6d.jpg
				 */
				$file_path = self::get_localimg_path_by_sinaimg($file_url,$post_id);
				$sinaimg_basename = self::get_file_url_meta('basename',$file_url);
				/** 
				 * if file exists, skipped
				 */
				if(file_exists($file_path)){
					$output['msg'] = sprintf(__('The picture (%s) will be skipped because it already exists. ',self::$iden),'<a href="' . $file_url . '" target="_blank">' . $sinaimg_basename . '</a>');
					$output['skip'] = 1;
					$output['status'] = 'success';
					$output['code'] = 'go_next';
					self::die_json_format($output);
				}
				$result = self::httpcopy($file_url,$file_path);
				// var_dump($result);exit;
				if($result){
					$output['status'] = 'success';
					$output['code'] = 'go_next';
					$output['msg'] = sprintf(__('The picture (%s) downloaded, continue to download next picture... ',self::$iden),'<a href="' . $file_url . '" target="_blank">' . $sinaimg_basename . '</a>');
				}else{
					$output['status'] = 'error';
					$output['code'] = 'no_found';
					$output['msg'] = sprintf(__('No found the picture (%s) from server, it will be skipped and continue to download next picture... ',self::$iden),'<a href="' . $file_url . '" target="_blank">' . $sinaimg_basename . '</a>');
				}

				break;
			/** 
			 * restore sina to local
			 */
			case 'restore-sina-to-local':
				if(!current_user_can('manage_options')){
					$output['status'] = 'error';
					$output['code'] = 'error_permission';
					$output['msg'] = __('Security permission was insufficient to operate, please make sure you are administrator.');
					self::die_json_format($output);
				}
				/** 
				 * if have not any post
				 */
				if(!self::get_backup_files()){
					$output['status'] = 'error';
					$output['code'] = 'no_backup_data';
					$output['msg'] = __('Can not find any backup data from local. Perhaps you need to backup data first. Restoration has been canceled.',self::$iden);
					self::die_json_format($output);
				}
				global $wp_query,$post;
				$wp_query = new WP_Query(array(
					'nopaging' => true,
					'post_type' => 'any',
					'ignore_sticky_posts' => true,
					'post__in' => self::get_backup_files('post_id'),
				));
				if(have_posts()){
					while(have_posts()){
						the_post();
						$sinaimg_urls = self::get_sinaimg_urls($post->post_content);
						$localimg_urls = array_map('self::get_localimg_url_by_sinaimg',$sinaimg_urls);
						$new_post_content = str_ireplace($sinaimg_urls,$localimg_urls,$post->post_content);
						/** 
						 * update post
						 */
						wp_update_post(array(
							'ID' => $post->ID,
							'post_content' => $new_post_content
						));
					}
					$output['status'] = 'success';
					$output['msg'] = __('Congratulation, all images have been restored to your wordpress server.',self::$iden);
				}else{
					$output['status'] = 'error';
					$output['code'] = 'no_match_posts';
					$output['msg'] = __('Unable to match any post by backup data. Perhaps your backup data has expired, please redo backup operation.',self::$iden);
				}
				wp_reset_postdata();
				wp_reset_query();
				
				break;
			/** 
			 * restore-local-to-sina
			 */
			case 'restore-local-to-sina':
				if(!current_user_can('manage_options')){
					$output['status'] = 'error';
					$output['code'] = 'error_permission';
					$output['msg'] = __('Security permission was insufficient to operate, please make sure you are administrator.');
					self::die_json_format($output);
				}
				if(!self::get_backup_files('post_id')){
					$output['status'] = 'error';
					$output['code'] = 'no_backup_data';
					$output['msg'] = __('Can not find any backup data from local. Perhaps you need to backup data first. Restoration has been canceled.',self::$iden);
					self::die_json_format($output);
				}
				/** 
				 * get all post
				 */
				global $wp_query,$post;
				$posts = array();
				$wp_query = new WP_Query(array(
					'nopaging' => true,
					'post_type' => 'any',
					'ignore_sticky_posts' => true,
					'post__in' => self::get_backup_files('post_id'),
					
				));
				if(have_posts()){
					while(have_posts()){
						the_post();
						/** 
						 * match sina images from post content
						 */
						$localimg_urls = self::get_localimg_urls_by_content($post->post_content);
												
						$sinaimg_urls = array_map('self::get_sinaimg_url_by_localimg',$localimg_urls);
						
						if(!empty($localimg_urls)){
							$new_post_content = str_ireplace(
								$localimg_urls,
								$sinaimg_urls,
								$post->post_content
							);
							wp_update_post(array(
								'ID' => $post->ID,
								'post_content' => $new_post_content
							));
						}
					}
					$output['status'] = 'success';
					$output['msg'] = __('Congratulation, all images have been restored to weibo server.',self::$iden);
				}else{
					$output['status'] = 'error';
					$output['code'] = 'no_match_posts';
					$output['msg'] = __('Unable to match any post by backup data. Perhaps your backup data has expired, please redo backup operation.',self::$iden);
				}
				wp_reset_postdata();
				wp_reset_query();
				
				break;
			default:
				$output['status'] = 'error';
				$output['code'] = 'invalid_param';
				$output['msg'] = __('Invalid param.',self::$iden);
		}
		self::die_json_format($output);
	}
	private static function die_json_format($output){
		die(call_user_func(array(self::$plugin_functions,'json_format'),$output));
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
		return call_user_func(array(self::$plugin_functions,'authcode'),self::$wb[$key]);
	}
	private static function httpcopy($url,$file, $timeout=60) {
		$dir = pathinfo($file,PATHINFO_DIRNAME);
		!is_dir($dir) && wp_mkdir_p($dir);
		$url = str_replace(" ","%20",$url);

		if(function_exists('curl_init')) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$temp = curl_exec($ch);
			if(!curl_error($ch) && file_put_contents($file, $temp)){
				return $file;
			} else {
				return false;
			}
		} else {
			$opts = array(
				"http"=>array(
				"method"=>"GET",
				"header"=>"",
				"timeout"=>$timeout)
			);
			$context = stream_context_create($opts);
			if(@copy($url, $file, $context)) {
				//$http_response_header
				return $file;
			} else {
				return false;
			}
		}
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
		$file_obj = explode('/',$file_url);
		$len = count($file_obj);
		/** 
		 * file eg. http://ww2.sinaimg.cn/square/5dd1e978jw1eo083cx0sgj218g0xck6d.jpg
		 */
		switch($key){
			/** 
			 * basename eg. 5dd1e978jw1eo083cx0sgj218g0xck6d.jpg
			 */
			case 'basename':
				$return = $file_obj[$len - 1];
				break;
			/**
			 * size eg. square
			 */
			case 'size':
				$return = $file_obj[$len - 2];
				break;
			/**
			 * id/filename eg. 5dd1e978jw1eo083cx0sgj218g0xck6d
			 */
			case 'id':
			case 'filename':
				$id = explode('.',$file_obj[$len - 1]);/** fuckyou php53 */
				$return = $id[0];
				break;
			/** 
			 * ext eg. jpg
			 */
			case 'ext':
				$ext = explode('.',$file_obj[$len - 1]);/** fuckyou php53 */
				$return = $ext[1];
				break;
			/** 
			 * domain eg. ww2
			 */
			case 'subdomain':
				$return = $file_obj[$len - 3];
				$return = explode('.',$return);/** fucku php53 */
				$return = $return[0];
				break;
			default:

		}
		return $return;
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
	 * Get local file meta
	 *
	 * @param string $basename The local file basename. eg. 1-ww2-square-xxx.jpg
	 * @return string
	 * @version 1.0.0
	 * @author KM@INN STUDIO
	 */
	private static function get_local_file_meta($filename,$type){
		$basename = basename($filename);
		$basename_obj = explode('-',$basename);
		switch($type){
			/** 
			 * post_id eg. 1
			 */
			case 'post_id':
			case 'postid':
				return isset($basename_obj[0]) ? $basename_obj[0] : null;
			/** 
			 * domain
			 */
			case 'subdomain':
				return isset($basename_obj[1]) ? $basename_obj[1] : null;
			/** 
			 * size
			 */
			case 'size':
				return isset($basename_obj[2]) ? $basename_obj[2] : null;
			/** 
			 * basename eg. xxx.jpg
			 */
			case 'basename':
				return isset($basename_obj[3]) ? $basename_obj[3] : null;
			/** 
			 * id/filename eg. xxx
			 */
			case 'id':
				$return = explode('.',$basename_obj[3]);
				return isset($return[0]) ? $return[0] : null;
			/** 
			 * ext
			 */
			case 'ext':
				$return = explode('.',$ar[3]);
				return isset($return[1]) ? $return[1] : null;
		
		}
	}
	/**
	 * Get has been backed up files
	 *
	 * @param string $type File meta type
	 * @return array 
	 * @version 1.0.0
	 * @author KM@INN STUDIO
	 */
	private static function get_backup_files($type = null){
		/** 
		 * @see https://codex.wordpress.org/Function_Reference/wp_upload_dir
		 */
		$upload_dir = wp_upload_dir();
		$backup_dir = $upload_dir['basedir'] .  self::$basedir_backup;
		if(empty(self::$cache_backup_files)){
			self::$cache_backup_files = glob($backup_dir . '*');
		}
		if(empty(self::$cache_backup_files)){
			return false;
		}else{
			self::$cache_backup_files = array_unique(self::$cache_backup_files);
		}
		
		$returns = array();
		foreach(self::$cache_backup_files as $img_path){
			if(is_file($img_path)){
				$returns[] = $type ? self::get_local_file_meta($img_path,$type) : $img_path;
			}
		}
		return $returns;

	}
	private static function get_localimg_urls_by_content($content){
		/** 
		 * with http
		 */
		preg_match_all(self::get_localimg_pattern(true),$content,$matches);
		return $matches[0];
	}
	/**
	 * add_backend_page
	 * 
	 * @return 
	 * @version 1.0.1
	 * @author KM@INN STUDIO
	 */
	public static function add_backend_page(){
		/* Add to theme setting menu */
		add_plugins_page(
			__('SinaPic v2 options',self::$iden),
			__('SinaPic v2 options',self::$iden), 
			'manage_options', 
			self::$iden . '-options',
			get_class() . '::display_backend'
		);
	}
	public static function options_default($options){
		$options['feature_meta'] = 'sinapic_feature_meta';
		$options['is_ssl'] = 1;
		return $options;
	}
	/**
	 * backend_options_save
	 * 
	 * @params array options
	 * @return array options
	 * @version 1.0.1
	 * @author KM@INN STUDIO
	 */
	public static function backend_options_save($options){
		if(!isset($_POST[self::$iden])) return $options;
		
		$options = isset($_POST[self::$iden]) ? $_POST[self::$iden] : null;
		/** 
		 * is ssl
		 */
		$options['is_ssl'] = isset($options['is_ssl']) ? $options['is_ssl'] : -1;
		
		$options['feature_meta'] = isset($_POST[self::$iden]['feature_meta']) && !empty($_POST[self::$iden]['feature_meta']) ? trim($_POST[self::$iden]['feature_meta']) : 'sinapic_feature_meta';
		$new_meta = $options['feature_meta'];
		/**
		 * check the new and old meta key from $_POST
		 */
		$old_meta = $options['old_meta'];
		if($old_meta !== $new_meta){
			global $wpdb,$wp_query,$post;
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

		}
		return $options;
	}
	/**
	 * display_backend
	 * 
	 * @return string
	 * @version 2.0.0
	 * @author KM@INN STUDIO
	 */
	public static function display_backend(){
		$plugin_data = get_plugin_data(__FILE__);
		$options = self::get_options();
		/** 
		 * custom options start
		 */
		$feature_meta = $options['feature_meta']; 
		$auto_backup = isset($options['auto_backup']) ? ' checked="checked" ' : null;
		$checked_is_ssl = isset($options['is_ssl']) && $options['is_ssl'] == 1 ? ' checked ' : null;
		$posts_count = wp_count_posts();
		$backup_dir = wp_upload_dir();
		$backup_dir = $backup_dir['basedir'] . self::$basedir_backup;
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
		echo call_user_func(array(self::$plugin_features,'get_plugin_css'),'admin','normal');
		echo call_user_func(array(self::$plugin_features,'get_plugin_js'),'jquery.kandytabs',false);
		echo call_user_func(array(self::$plugin_features,'get_plugin_js'),'admin',false);
		?>
		<script>
		(function(){
			var sinapicv2 = new sinapicv2_admin();
			sinapicv2.config.lang.E00001 = '<?php _e('Error code: ',self::$iden);?>';
			sinapicv2.config.lang.E00002 = '<?php _e('Program error, can not continue to operate. Please try again or contact author. ',self::$iden);?>';
			sinapicv2.config.lang.E00003 = '<?php _e('Program error, can not continue to operate. Please try again or contact author. ',self::$iden);?>';
			sinapicv2.config.lang.M00001 = '<?php _e('Getting backup config data, please wait... ',self::$iden);?>';
			sinapicv2.config.lang.M00002 = '<?php _e('Current processing: ',self::$iden);?>';
			sinapicv2.config.lang.M00003 = '<?php _e('Downloading, you can restore the pictures to post after the download is complete. ',self::$iden);?>';
			sinapicv2.config.lang.M00005 = '<?php _e('Download completed, you can perform a restore operation.',self::$iden);?>';
			sinapicv2.config.lang.M00006 = '<?php _e('Current file has been downloaded, skipping it.',self::$iden);?>';
			sinapicv2.config.lang.M00010 = '<?php _e('The data is being restored , please wait...  ',self::$iden);?>';
			sinapicv2.config.process_url = '<?php echo call_user_func(array(self::$plugin_features,'get_process_url'),array('action' => self::$iden));?>';
			sinapicv2.init();
		})();
		</script>
		<div class="wrap">
			<h2><?php echo esc_html($plugin_data['Name']);?> <small>- <?php echo $plugin_data['Version'];?></small> <?php echo esc_html(__('plugin settings',self::$iden));?></h2>
			<?php if(isset($_GET['updated'])){?>
				<div id="settings-updated">
					<?php echo call_user_func(array(self::$plugin_functions,'status_tip'),'success',__('Settings have been saved.',self::$iden));?>
				</div>
			<?php } ?>
<form id="backend-options-frm" method="post">
	<?php
	/**
	 * loading
	 */
	echo '<div class="backend-tab-loading">' . call_user_func(array(self::$plugin_functions,'status_tip'),'loading',__('Loading, please wait...',self::$iden)) . '</div>';
	?>
	<dl id="backend-tab" class="backend-tab">
		<dt title="<?php echo esc_attr(__('Plugin common settings.',self::$iden));?>"><span class="dashicons dashicons-admin-generic"></span><?php echo esc_html(__('Basic settings',self::$iden));?></dt>
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
					<?php _e('If your theme supports feature thumbnail form post meta and you want to use it, please tell the Sinapicv2 what is the post meta name(key). Fill in the text area.',self::$iden);?>
				</p>
				<table class="form-table">
					<tbody>
						<tr>
							<th><label for="<?php echo self::$iden;?>-is-ssl"><?php echo _e('Enable SSL (https://) image address',self::$iden);?></label></th>
							<td>
								<label for="<?php echo self::$iden;?>-is-ssl"><input type="checkbox" name="<?php echo self::$iden;?>[is_ssl]" id="<?php echo self::$iden;?>-is-ssl" value="1" <?php echo $checked_is_ssl;?>/> <?php echo _e('Enabled',self::$iden);?></label>
							</td>
						</tr>
						<tr>
							<th><label for="sinapic_feature_meta"><?php _e('Feature thumbnail meta name: ',self::$iden);?></label></th>
							<td>
								<input id="sinapic_feature_meta" name="<?php echo self::$iden;?>[feature_meta]" type="text" class="regular-text" value="<?php echo $feature_meta;?>"/>
								<input type="hidden" name="<?php echo self::$iden;?>[old_meta]" value="<?php echo $feature_meta;?>"/>
								<span class="description"><?php _e('Default: ',self::$iden);?>sinapic_feature_meta</span>
							</td>
						</tr>
						<tr>
							<th><label for="<?php echo self::$iden;?>-img-title-enabled"><?php _e('Image title attribute',self::$iden);?></label></th>
							<td>
								
								<?php
								$checked_img_title_enabled = isset($options['img-title-enabled']) ? ' checked ' : null;
								?>
								<label for="<?php echo self::$iden;?>-img-title-enabled">
									<input type="checkbox" name="<?php echo self::$iden;?>[img-title-enabled]" id="<?php echo self::$iden;?>-img-title-enabled" <?php echo $checked_img_title_enabled;?>/>
									<?php _e('Display image title attribute as same as alt attribute.',self::$iden);?>
								</label>
							</td>
						</tr>
						<tr>
							<th><label for="<?php echo self::$iden;?>-destroy-after-upload"><?php _e('Delete after upload',self::$iden);?></label></th>
							<td>
								
								<?php
								$destroy_after_upload_checkbox = isset($options['destroy_after_upload']) ? ' checked ' : null;
								?>
								<label for="<?php echo self::$iden;?>-destroy-after-upload">
									<input type="checkbox" name="<?php echo self::$iden;?>[destroy_after_upload]" id="<?php echo self::$iden;?>-destroy-after-upload" <?php echo $destroy_after_upload_checkbox;?>/>
									<?php _e('After upload a message and it will be destroy if enable',self::$iden);?>
								</label>
							</td>
						</tr>
					</tbody>
				</table>						
			</fieldset>					
			<fieldset>
				<legend><?php _e('Backup & bestore',self::$iden);?></legend>
				<p class="description"><?php _e('With version 2.0.0 or higher, sinapicv2 supports to backup sina image form all posts.',self::$iden);?></p>
				<p class="description"><?php echo sprintf(__('The backup pictures will be saved to %s in your host.',self::$iden),'<strong  style="cursor:pointer;" onclick="$(this).text($(this).data(\'tx\'));" data-tx="' . $backup_dir . '">' . __('Click to view',self::$iden) . '</strong>');?></p>
				<table class="form-table">
					<tbody>
						<tr>
							<th>
								<p><?php _e('Backup pictures: ',self::$iden);?></p>
								<p><?php _e('Pictures server &rarr; my space',self::$iden);?></p>
							</th>
							<td>
								<div id="sinapicv2-backup-area">
									
									<div id="sinapicv2-backup-progress" class="sinapicv2-progress">
										<div id="sinapicv2-backup-tip" class="hide"></div>
										<div id="sinapicv2-backup-progress-bar" class="sinapicv2-progress-bar"></div>
									</div>
									
									<p id="sinapicv2-backup-btns"><a href="javascript:void(0);" id="sinapicv2-backup-btn" class="button-primary"><span class="dashicons dashicons-download"></span><?php _e('click to start BACKUP',self::$iden);?></a></p>
								</div>
								<p>
									<?php echo sprintf(__('Backup operation will search all sina images and downloads them to backup folder from your publish about %s posts.',self::$iden),'<strong>' . $posts_count->publish . '</strong>');?>
								</p>
								<p>
									<span class="dashicons dashicons-info"></span><?php echo __('Attention: you need DO THIS backup operation in first time.',self::$iden);?>
								</p>
							</td>
						</tr>
						
						<tr>
							<th>
								<p><?php _e('Restore pictures:',self::$iden);?></p>
								<p><?php echo sprintf(__('Pictures server %s my space: ',self::$iden),'<span class="dashicons dashicons-controls-repeat"></span>');?></p>
							</th>
							<td>
								<div id="sinapic_restore_area">
									<div id="sinapicv2-restore-progress" class="sinapicv2-progress">
										<div id="sinapicv2-restore-tip" class="hide"></div>
										<div id="sinapicv2-restore-progress-bar" class="sinapicv2-progress-bar"></div>
									</div>
									<p id="sinapicv2-restore-btns">
										<a href="javascript:void(0);" id="sinapicv2-restore-server-to-host-btn"  class="button">
											<?php echo sprintf(__('%s Server to %s %sMy space',self::$iden),'<span class="dashicons dashicons-cloud"></span>','<span class="dashicons dashicons-arrow-right-alt"></span>','<span class="dashicons dashicons-wordpress"></span>');?>
										</a>
										
										<a href="javascript:void(0);" id="sinapicv2-restore-host-to-server-btn"  class="button">
											<?php echo sprintf(__('%sMy space to %s %sServer',self::$iden),'<span class="dashicons dashicons-wordpress"></span>','<span class="dashicons dashicons-arrow-right-alt"></span>','<span class="dashicons dashicons-cloud"></span>');?>
										</a>
										
									</p>
								</div>
								<p>
									<?php _e('Server to my space: all weibo server picture addresses replace to your space pictur addresses.',self::$iden);?>
								</p>
								<p>
									<?php _e('My space to server: all your space picture addresses replace to weibo server picture addresses.',self::$iden);?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>
			</fieldset>
		</dd>
		
		<dt><span class="dashicons dashicons-editor-help"></span><?php echo esc_html(__('About &amp; Help',self::$iden));?></dt>
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
									<a id="alipay_donate" target="_blank" href="https://ww3.sinaimg.cn/large/686ee05djw1eihtkzlg6mj216y16ydll.jpg" title="<?php echo __('Donation by Alipay',self::$iden);?>">
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
		$des_array = array(
			__('The best image host plugin for WP, do you agree?',self::$iden),
			__('This is an artwork, no only plugin',self::$iden),
			__('Powered by INN STUDIO',self::$iden),
			__('Do you like me? Expression with real action: Alipay',self::$iden),
			__('Cabbage and salted fish is plugin author\'s lunch',self::$iden),
			__('Cabbage and salted fish is plugin author\'s dinner',self::$iden),
			__('Beskfast is not a part of plugin author',self::$iden),
			__('Join into QQ group 170306005 is one of feedback way',self::$iden),
			__('People eat a lot of meals, but also to express gratitude repeatedly: Alipay, you know me',self::$iden),
			__('Today the weather is good',self::$iden),
			__('This artwork is part of the world',self::$iden),
			
		);
		$rand_des = $des_array[rand(0,count($des_array) - 1)];
		foreach($screens as $screen){
			add_meta_box(
				self::$iden,
				__('Sinapic v2',self::$iden) . '<span style="font-weight:normal;"> - ' . $rand_des . '</span>',
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
		// if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
		// if(!current_user_can('edit_post',$post_id)) return;

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
			'uri' => call_user_func(array(self::$plugin_features,'get_process_url'),array(
				'action' => self::$iden,
				'type' => 'set_authorize'
			))
		);
		$authorize_uri = self::get_config(2) . http_build_query($authorize_uri_obj);	
		return $authorize_uri;
	}
	public static function get_max_upload_size(){
		$max_mb = ini_get('upload_max_filesize');
		if(stripos($max_mb,'m') === false){
			/** 
			 * 1024*2048
			 */
			return 2097152;
		}else{
			return 1048576 * (int)$max_mb;
		}
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
		$options = self::get_options();
		/** 
		 * authorize_uri
		 */
		$authorize_uri = self::get_authorize_uri();
		$authorized_js = self::is_authorized() ? 'true' : 'false';
		
		self::css();
		?>
		<script src="<?php echo call_user_func(array(self::$plugin_features,'get_plugin_js'),'init');?>"></script>
		<script>
		var oo = new sinapicv2();
		oo.config.process_url = '<?php echo call_user_func(array(self::$plugin_features,'get_process_url'),array(
			'action' => self::$iden,
			'post_id' => $post->ID
		));?>';
		oo.config.post_id = <?php echo $post->ID;?>;
		oo.config.lang.E00001 = '<?php _e('Error: ',self::$iden);?>';
		oo.config.lang.E00002 = '<?php _e('Upload failed, please try again. If you still failed, please contact the plugin author.',self::$iden);?>';
		oo.config.lang.E00003 = '<?php _e('Sorry, plugin can not get authorized data, please try again later or contact plugin author.',self::$iden);?>';
		oo.config.lang.M00001 = '<?php _e('Uploading {0}/{1}, please wait...',self::$iden);?>';
		oo.config.lang.M00002 = '<?php _e('{0} files have been uploaded, enjoy it.',self::$iden);?>';
		oo.config.lang.M00003 = '<?php _e('Image URL: ',self::$iden);?>';
		oo.config.lang.M00004 = '<?php _e('ALT attribute: ',self::$iden);?>';
		oo.config.lang.M00005 = '<?php _e('Set ALT attribute text',self::$iden);?>';
		oo.config.lang.M00006 = '<?php _e('Control: ',self::$iden);?>';
		oo.config.lang.M00007 = '<?php _e('Insert to post with link',self::$iden);?>';
		oo.config.lang.M00008 = '<?php _e('Insert to post image only',self::$iden);?>';
		oo.config.lang.M00009 = '<?php _e('As custom meta feature image',self::$iden);?>';
		oo.config.authorized = <?php echo $authorized_js;?>;
		oo.config.show_title = <?php echo isset($options['img-title-enabled']) ? 'true' : 'false';?>;
		oo.config.max_upload_size = <?php echo self::get_max_upload_size();?>;
		oo.config.sizes = {
			thumb150 	: '<?php _e('max 150x150, crop',self::$iden);?>',
			mw600 		: '<?php _e('max-width:600',self::$iden);?>',
			large 		: '<?php _e('original size',self::$iden);?>',
			square 		: '<?php _e('max-width:80 or max-height:80',self::$iden);?>',
			thumbnail 	: '<?php _e('max-width:120 or max-height:120',self::$iden);?>',
			bmiddle 	: '<?php _e('max-width:440',self::$iden);?>'
		};
		oo.init();
		</script>
		<div id="sinapicv2-container">
			<div id="sinapicv2-area-upload">
				<div id="sinapicv2-loading-tip">
					<?php echo call_user_func(array(self::$plugin_functions,'status_tip'),'loading','middle',__('Loading, please wait...',self::$iden));?>
				</div>
				<div id="sinapicv2-unauthorize">
					<?php echo call_user_func(array(self::$plugin_functions,'status_tip'),'info',sprintf(__('Sorry, Sinapicv2 needs to authorize from your Weibo account, <a href="%s"  id="sinapicv2-go-authorize" target="_blank"><strong>please click here to authorize</strong></a>.<br/>If you has authorized just now, <a href="javascript:void(0);" id="sinapicv2-reloadme"><strong>please click here to reload me</strong></a>.',self::$iden),$authorize_uri));?>
				</div>
				<div id="sinapicv2-progress"><div id="sinapicv2-progress-tx"></div><div id="sinapicv2-progress-bar"></div></div>
				<div class="button-primary" id="sinapicv2-add">
					<?php _e('Select or Drag picture(s) to upload',self::$iden);?>
					<input type="file" id="sinapicv2-file" accept="image/gif,image/jpeg,image/png" multiple="true" />
				</div>
				<div id="sinapicv2-completion-tip"></div>
				<div id="sinapicv2-error-file-tip">
					<span class="des"><?php echo esc_html(__('Detects that files can not be uploaded:'));?></span>
					<span id="sinapicv2-error-files"></span>
				</div>
				<div id="sinapicv2-tools">
					<a id="sinapicv2-insert-list-with-link" href="javascript:void(0);" class="button button-primary"><?php _e('Insert to post from list with link',self::$iden);?></a>
					<a id="sinapicv2-insert-list-without-link" href="javascript:void(0);" class="button"><?php _e('Insert to post from list without link',self::$iden);?></a>
					
					<select id="sinapicv2-split">
						<option value="0"><?php echo __('Do not use separate',self::$iden);?></option>
						<option value="nextpage"><?php echo __('Use "Next page" tag',self::$iden);?></option>
					</select>
					
					<a href="javascript:void(0);" id="sinapicv2-clear-list"><?php _e('Clear list',self::$iden);?></a>
				</div>
			</div>
			<div id="sinapicv2-tpl-container"></div>
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
		return call_user_func(array(self::$plugin_functions,'status_tip'),func_get_args());
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