/**
 * admin
 * 
 * @return 
 * @example 
 * @version 1.0.1
 * @author KM (kmvan.com@gmail.com)
 * @copyright Copyright (c) 2011-2013 INN STUDIO. (http://www.inn-studio.com)
 **/
var admin = {
	
	config : {
		
		backup_tip_id : '#sinapic_backup_tip',
		backup_btn_id : '#sinapic_backup_btn',
		
		restore_tip_id : '#sinapic_restore_tip',
		restore_btn_1_id : '#sinapic_restore_btn_1',
		restore_btn_2_id : '#sinapic_restore_btn_2',
		restore_btns_area_id : '#sinapic_restore_btns_area',
		
		process_url : '',
		
		lang : {
			E00001 : 'Error code: ',
			E00002 : 'Program error, can not continue to operate. Please try again or contact author. ',
			
			M00001 : 'Getting backup config data, please wait... ',
			M00002 : 'Current processing: ',
			M00003 : 'Downloading, you can restore the pictures to post after the download is complete. ',
			M00005 : 'Download completed, you can restore the pictures to post. ',

			M00010 : 'The data is being restored , please wait... ',
			
		}
		
	},
	init : function(){
		var _this = admin;
		jQuery(document).ready(function(){
			_this.tab.init();
			_this.backup.bind();
			_this.restore.bind();
		});
	},
	/**
	 * backup
	 */
	backup : {
		bind : function(){
			var _this = admin;
			
			jQuery(_this.config.backup_btn_id).on('click',function(){
				_this.backup.hook.tip(_this.hook.status_tip('loading',_this.config.lang.M00001));
				_this.backup.hook.tip('show');
				_this.backup.hook.get_xml_data();
				return false;
			});
		},
		/**
		 * hook
		 */
		hook : {
			/**
			 * get_xml_data
			 * 
			 * @return n/a
			 * @example _this.backup.hook.get_xml_data()
			 * @version 1.0.0
			 * @author KM (kmvan.com@gmail.com)
			 * @copyright Copyright (c) 2011-2013 INN STUDIO. (http://www.inn-studio.com)
			 **/
			get_xml_data : function(){
				var _this = admin,
					ajax_data = {
						'type' : 'get_backup_data'
					};
				jQuery.ajax({
					url : _this.config.process_url,
					data : ajax_data,
					dataType : 'json',
					beforeSend : function(){
						_this.backup.hook.tip('disabled');
					},success : function(data){
						/**
						 * success
						 */
						if(data && data.status === 'success'){
							var file_url_arr = data.des.content,
								len = file_url_arr.length,
								i = 0;
							/**
							 * loop
							 */
							function loop(file_url){
								if(i < len){
									var ajax_data_loop = {
										'type' : 'download',
										'file_url' : file_url == undefined ? file_url_arr[0] : file_url
									};
									jQuery.ajax({
										url : _this.config.process_url,
										data : ajax_data_loop,
										dataType : 'json',
										success : function(data){
											/**
											 * success
											 */
											if(data && data.status === 'success'){
												_this.backup.hook.tip(_this.hook.status_tip('loading',data.des.content + _this.config.lang.M00002 + (i + 1) + ' / ' + len));
												i++;
												loop(file_url_arr[i]);
											/**
											 * error
											 */
											}else if(data && data.status === 'error'){
												_this.backup.hook.tip(_this.hook.status_tip('loading',data.des.content));
												_this.backup.hook.tip('enable');
											/**
											 * unkown
											 */
											}else{
												_this.backup.hook.tip(_this.hook.status_tip('error',_this.config.lang.E00002 + _this.config.lang.E00001 + '002'));
												_this.backup.hook.tip('enable');
											}
										},error : function(){
											_this.backup.hook.tip(_this.hook.status_tip('error',_this.config.lang.E00002 + _this.config.lang.E00001 + '003'));
											_this.backup.hook.tip('enable');
										}
									});
								/**
								 * complete
								 */
								}else{
									_this.backup.hook.tip(_this.hook.status_tip('success',_this.config.lang.M00005));
									_this.backup.hook.tip('enable');
								}
							}
							/**
							 * start loop() first
							 */
							loop();
						/**
						 * end success,found error
						 */	
						}else if(data && data.status === 'error'){
							_this.backup.hook.tip(_this.hook.status_tip('error',data.des.content));
							_this.backup.hook.tip('enable');
						}else{
							_this.backup.hook.tip(_this.hook.status_tip('error',_this.config.lang.E00002 + _this.config.lang.E00001 + '001'));
							_this.backup.hook.tip('enable');
						}
					},error : function(){
						_this.backup.hook.tip(_this.hook.status_tip('error',_this.config.lang.E00002 + _this.config.lang.E00001 + '001'));
						_this.backup.hook.tip('enable');
					}
				
				});
				
			},

			/**
			 * tip
			 * 
			 * @param string content
			 * @return n/a
			 * @version 1.0.0
			 * @example admin.backup.hook.tip('show')
			 * @author KM (kmvan.com@gmail.com)
			 * @copyright Copyright (c) 2011-2013 INN STUDIO. (http://www.inn-studio.com)
			 **/
			tip : function(content){
				var _this = admin,
					$tip = jQuery(_this.config.backup_tip_id),
					$btn = jQuery(_this.config.backup_btn_id);
				switch(content){
					case 'show':
						$tip.show();
						$btn.attr('disabled','disabled');
						break;
					case 'hide':
						$tip.hide();
						$btn.removeAttr('disabled');
						break;
					case 'enable':
						$btn.show();
						$btn.removeAttr('disabled');
						break;
					case 'disabled':
						$btn.hide();
						$btn.attr('disabled','disabled');
						break;
					default:
						$tip.html(content);
				}
			}
		
		}
	},
	/**
	 * restore
	 */
	restore : {
		
		bind : function(){
			var _this = admin,
				ajax_data;
			jQuery(_this.config.restore_btn_1_id).on('click',function(){
				ajax_data = {
					'type' : 'restore',
					'restore_to' : 'space'
				};
				_this.restore.hook.send(ajax_data);
				return false;
			});
			jQuery(_this.config.restore_btn_2_id).on('click',function(){
				ajax_data = {
					'type' : 'restore',
					'restore_to' : 'server'
				};
				_this.restore.hook.send(ajax_data);
				return false;
			});
			
		},
		
		hook : {
			ajax_data : {},
			
			send : function(ajax_data){
				var _this = admin;
				jQuery.ajax({
					url : admin.config.process_url,
					data : ajax_data,
					dataType : 'json',
					beforeSend : function(){
						_this.restore.hook.tip(_this.hook.status_tip('loading',_this.config.lang.M00010));
						_this.restore.hook.tip('show');
					},success : function(data){
						if(data && data.status === 'success'){
							_this.restore.hook.tip(_this.hook.status_tip('success',data.des.content));
							_this.restore.hook.tip('enable');
						}else if(data && data.status === 'error'){
							_this.restore.hook.tip(_this.hook.status_tip('error',data.des.content));
							_this.restore.hook.tip('enable');
						}else{
							_this.restore.hook.tip(_this.hook.status_tip('error',_this.config.lang.E00002));
							_this.restore.hook.tip('enable');
						}
					},error : function(){
							_this.restore.hook.tip(_this.hook.status_tip('error',_this.config.lang.E00002));
							_this.restore.hook.tip('enable');
					}
				});
			},
			/**
			 * tip
			 * 
			 * @param string content
			 * @return n/a
			 * @version 1.0.0
			 * @example admin.backup.hook.tip('show')
			 * @author KM (kmvan.com@gmail.com)
			 * @copyright Copyright (c) 2011-2013 INN STUDIO. (http://www.inn-studio.com)
			 **/
			tip : function(content){
				var _this = admin,
					$tip = jQuery(_this.config.restore_tip_id),
					$btns = jQuery(_this.config.restore_btns_area_id);
				switch(content){
					case 'show':
						$tip.show();
						$btns.hide();
						break;
					case 'hide':
						$tip.hide();
						$btns.show();
						break;
					case 'enable':
						$btns.show();
						break;
					case 'disabled':
						$btns.hide();
						break;
					default:
						$tip.html(content);
				}
			}
		
		}
	},
	
	
	tab : {
		
		init : function(){
			jQuery('.admin_tab').KandyTabs({
				delay : 100,
				done : function(){
					jQuery('.admin_tab_loading').slideUp('fast');
					jQuery('#plugin_options_frm').slideDown('fast');
				}
			});
		}
	
	},
	
	hook : {
		
		/**
		 * status_tip
		 *
		 * @param mixed
		 * @return string
		 * @version 1.1.0
		 * @author KM@INN STUDIO
		 */
		status_tip : function(){
			var defaults = ['type','size','content','wrapper'],
				types = ['loading','success','error','question','info','ban','warning'],
				sizes = ['small','middle','large'],
				wrappers = ['div','span'],
				type = null,
				size = null,
				wrapper = null,
				content = null,	
				args = arguments;
				switch(args.length){
					case 0:
						return false;
					/** 
					 * only content
					 */
					case 1:
						content = args[0];
						break;
					/** 
					 * only type & content
					 */
					case 2:
						type = args[0];
						content = args[1];
						break;
					/** 
					 * other
					 */
					default:
						for(var i in args){
							eval(defaults[i] + ' = args[i];');
						}
				}
				wrapper = wrapper || wrappers[0];
				type = type ||  types[0];
				size = size ||  sizes[0];

				var tpl = '<' + wrapper + ' class="tip-status tip-status-' + size + ' tip-status-' + type + '"><i class="icon ' + type + '"></i>' + content + '</' + wrapper + '>';
				return tpl;
		}
	}
	
}