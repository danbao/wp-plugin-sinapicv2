/**
 * sinapic_upload
 * 
 * @version 2.0
 * @author KM@INN STUDIO
 */
var sinapic_upload = {
	
	config : {
		process_url 		: '',
		file_element_id 	: '#sinapic_upload_new',
		loading_tip_id 		: '#sinapic_loading_tip',
		completion_tip_id 	: '#sinapic_completion_tip',
		btns_id 			: '#sinapic_btns',
		remote_file_id 		: 'my_uploaded_file',
		unauthorize_id 		: '#sinapic_unauthorize',
		go_authorize_id 	: '#sinapic_go_authorize',
		reload_id 			: '#sinapic_reload',
		post_id 			: '',
		form_id 			: '#sinapicv2_form',
		iframe_id 			: '#sinapicv2_iframe',
		add_new_btn_id 		: '#add_new',
		tools_id 			: '#sinapic_tools',
		tpl_id				: '#tpl',
		timer 				: 4000,
		accept_formats 		: ['png','jpg','gif'],
		cookie_last_size 	: 'sinapic_last_size',
		lang : {
			E00001 : 'Error: ',
			E00002 : 'Upload failed, please login weibo and try again. If you still failed, please contact the plugin author.',
			E00003 : 'Sorry, plugin can not get authorized data, please try again later or contact plugin author.',
			M00001 : 'Uploading {1}/{2}, please wait...',
			M00002 : '{0} files have been uploaded, enjoy it.',
			M00003 : 'Image URL: ',
			M00004 : 'ALT attribute: ',
			M00005 : 'Set ALT attribute text',
			M00006 : 'Control: ',
			M00007 : 'Insert to post with link',
			M00008 : 'Insert to post image only',
			M00009 : 'As custom meta feature image'
		},
		/**
		 * sizes
		 */
		sizes : {
			thumb150 	: 'max 150x150, crop',
			mw600 		: 'max-width:600',
			large 		: 'organize size',
			square 		: 'max-width:80 or max-height:80',
			thumbnail 	: 'max-width:120 or max-height:120',
			bmiddle 	: 'max-width:440'
			
		},
		/** 
		 * set intervaltime for each file upload
		 */
		interval : 3000

	},
	cache : {
		i : 0
	},
	/**
	 * init
	 */
	init : function(){
		var exports = sinapic_upload;
		exports.hook.bind();
	},
	/**
	 * hook
	 */
	hook : {
		/**
		 * bind
		 */
		bind : function(){
			var exports = sinapic_upload;
			/** 
			 * set cache
			 */
			exports.cache.$loading_tip 		= jQuery(exports.config.loading_tip_id).hide();
			exports.cache.$file 			= jQuery(exports.config.file_element_id);
			exports.cache.$reload 			= jQuery(exports.config.reload_id);
			exports.cache.$completion_tip 	= jQuery(exports.config.completion_tip_id);
			exports.cache.$btns 			= jQuery(exports.config.btns_id);
			exports.cache.$tools 			= jQuery(exports.config.tools_id);
			exports.cache.$tpl 				= jQuery(exports.config.tpl_id);
			exports.cache.$unauthorize		= jQuery(exports.config.unauthorize_id);
			exports.cache.$add_new_btn		= jQuery(exports.config.add_new_btn_id);
			/** 
			 * other action
			 */
			exports.hook.authorize_init();
			exports.hook.btn_hover();
			exports.hook.batch_thumb_insert();
			exports.hook.clear_list();

			/** 
			 * file btn
			 */
			exports.cache.$file.on({
				change 		: exports.hook.file_select,
				dragenter 	: exports.hook.drop_enter,
				dragover 	: exports.hook.drop_over,
				dragleave 	: exports.hook.drop_leave,
				drop 		: exports.hook.file_select
			});
			/** 
			 * authorized reload
			 */
			if(exports.cache.$reload[0]){
				exports.cache.$reload.on('click',function(){
					exports.hook.reload_me();
				});
			};
		},
		/** 
		 * upload_started
		 */
		upload_started : function(i,file,count){
			var t = format(exports.config.lang.M00001,i,count);
			exports.hook.uploading_tip('loading',t);
		},
		/**
		 * The tip when pic is uploading
		 *
		 * @param string status 'loading','success' ,'error'
		 * @param string text The content of tip
		 * @return 
		 * @version 1.0.0
		 * @author KM@INN STUDIO
		 */
		uploading_tip : function(status,text){
			var exports = sinapic_upload;
			exports.cache.$completion_tip;
			/** 
			 * uploading status
			 */
			if(!status || status === 'loading'){
				exports.cache.$loading_tip.html(exports.hook.status_tip('loading',text)).show();
				exports.cache.$btns.hide();
				exports.cache.$completion_tip.hide();
			/** 
			 * success status
			 */
			}else{
				exports.cache.$completion_tip.html(exports.hook.status_tip(status,text)).show();
				exports.cache.$loading_tip.hide();
				exports.cache.$btns.show();
			}
		},
		btn_hover : function(){
			var exports = sinapic_upload,
				$sinapic_add_new = jQuery('#sinapic_add_new');
			exports.cache.$file.mouseover(function(){
				$sinapic_add_new.addClass('button-primary');
			}).mouseout(function(){
				$sinapic_add_new.removeClass('button-primary');
			}).mousedown(function(){
				$sinapic_add_new.addClass('active');
			}).mouseup(function(){
				$sinapic_add_new.removeClass('active');
			});			

		},
		go_authorize : function(){
			var exports = sinapic_upload;
			
		},
		authorize_init : function(){
			var exports = sinapic_upload;
			if(exports.config.authorized === false){
				exports.cache.$unauthorize.show();
				exports.cache.$btns.hide();
			}else{
				exports.cache.$btns.show();
			}
		},
		/**
		 * reload_me
		 *
		 * @return 
		 * @version 1.0.0
		 * @author KM@INN STUDIO
		 */
		reload_me : function(){
			var exports = sinapic_upload,
				ajax_data = {
					type : 'check_authorize'
				};
			jQuery.ajax({
				url : exports.config.process_url,
				type : 'get',
				dataType : 'json',
				data : ajax_data,
				beforeSend : function(){
					exports.cache.$loading_tip.show();
					exports.cache.$unauthorize.hide();
				},success : function(data){
					if(data && data.status === 'success'){
						exports.cache.$loading_tip.hide();
						exports.cache.$btns.show()
					
					}else if(data && data.status === 'error'){
						// exports.hook.uploading_tip('error',data.des.content);
						alert(data.des.content);
					}else{
						// alert(exports.config.lang.E
					}
						// jQuery(exports.config.loading_tip_id).hide();
						// exports.cache.$unauthorize.show();
						
					// }
				},error : function(){
					exports.cache.$loading_tip.hide();
					exports.cache.$unauthorize.show();
				}
			});
		},
		
		/**
		 * drop_enter
		 */
		drop_enter : function(e){
			var exports = sinapic_upload;	
			exports.cache.$add_new_btn.addClass('dropenter button-primary');
		},
		/**
		 * drag_over
		 */
		drag_over : function(e){
			var exports = sinapic_upload;	
			// e.stopPropagation();  
			// e.preventDefault();  
			exports.cache.$add_new_btn.removeClass('dropenter button-primary');
		},
		/**
		 * drop_leave
		 */
		drop_leave : function(e){
			var exports = sinapic_upload;	
			exports.cache.$add_new_btn.removeClass('dropenter button-primary');
		},
		/**
		 * file_select
		 */
		file_select : function(e){
			 e.stopPropagation();  
			 e.preventDefault();  
			var exports = sinapic_upload;

			exports.cache.files = e.target.files.length ? e.target.files : e.originalEvent.dataTransfer.files;
			exports.cache.file_count = exports.cache.files.length;
			exports.cache.file = exports.cache.files[0];
			/** 
			 * start upload file
			 */
			exports.hook.file_upload(exports.cache.files[0]);
			
		},
		/**
		 * file_upload
		 */
		file_upload : function(file){
			var exports = sinapic_upload;
			exports.cache.start_time = new Date();
			var	reader = new FileReader();
			reader.onload = function (e) {
				var base64 = e.target.result.split(',')[1];
				exports.hook.submission(base64);

			};
			reader.readAsDataURL(file);		
		
		},
		/**
		 * submission
		 * 
		 * @return n/a
		 * @version 1.1.0
		 * @author KM@INN STUDIO
		 */
		submission : function(base64){
			var exports = sinapic_upload,
				ajax_data = {
					file_name : exports.cache.files[exports.cache.i].name,
					file_b64 : base64
				};
			
			jQuery.ajax({
				url : exports.config.process_url + '&type=upload',
				type : 'post',
				dataType : 'json',
				data : ajax_data,
				beforeSend : function(){
					exports.hook.beforesend_callback();
				},success : function(data){
					exports.hook.complete_callback(data);
				},error : function(){
					exports.hook.error();
				}
				
			});
		},
		/**
		 * beforesend_callback
		 */
		beforesend_callback : function(base64){
			var exports = sinapic_upload,
				tx = exports.format(exports.config.lang.M00001,exports.cache.i + 1,exports.cache.file_count);
			exports.hook.uploading_tip('loading',tx);
		},
		/**
		 * complete_callback
		 */
		complete_callback : function(data){
			var exports = sinapic_upload,
				url;
			/** 
			 * success
			 */
			if(data && data.status === 'success'){
				url = data.des.img_url;
				var args = {
						'img_url' : url,
						'size' : ''
					};
				var $tpl_table = jQuery(exports.hook.tpl(args));
				
				$tpl_table.hide();
				exports.cache.$tpl.prepend($tpl_table).show();
				$tpl_table.fadeIn('slow');
				
				/**
				 * bind thumb_change click
				 */
				exports.hook.thumb_change(args.img_url);
				/**
				 * bind thumb_insert click
				 */
				exports.hook.thumb_insert(args.img_url);
				
				/**
				 * focus alt attribute
				 */
				jQuery('#img_alt_' + exports.hook.get_id(args.img_url)).val(jQuery('#title').val()).focus().select();
				
				/** 
				 * show tools
				 */
				if(exports.cache.$tools.is(':hidden')){
					exports.cache.$tools.slideDown();
				}
				
				exports.cache.i++;
				/** 
				 * check all thing has finished, if finished
				 */
				if(exports.cache.file_count ==  exports.cache.i){
					var tx = exports.format(exports.config.lang.M00002,exports.cache.file_count);
					exports.hook.uploading_tip('success',tx);
					/** 
					 * reset
					 */
					exports.cache.i = 0;
					exports.cache.$file.val('');
				/** 
				 * upload next pic
				 */
				}else{
					/** 
					 * check interval time
					 */
					var end_time = new Date(),
						interval_time = end_time - exports.cache.start_time,
						timeout = exports.config.interval - interval_time,
						timeout = timeout < 0 ? 0 :timeout;
					/** 
					 * if curr time > interval time, upload next pic right now 
					 */
					setTimeout(function(){
						exports.hook.file_upload(exports.cache.files[exports.cache.i]);
					},timeout);
				}

			}else if(data && data.status === 'error'){
				// exports.hook.tip('error',data.des.content);
				exports.hook.uploading_tip('error',data.des.content);
				// alert(data.des.content);
				// jQuery(exports.config.loading_tip_id).hide();
				// exports.cache.$btns.show();
			}else{
				exports.hook.uploading_tip('error',exports.config.lang.E00002);
				// alert(exports.config.lang.E00002);
				console.log(data);
				// exports.hook.tip('error',exports.config.lang.M00002);
			}
			exports.cache.$add_new_btn.removeClass('dropenter button-primary');
			
		},
		error : function(){
			var exports = sinapic_upload;
			exports.hook.uploading_tip('error',exports.config.lang.E00002);
			// alert(exports.config.lang.E00002);
			// jQuery(exports.config.loading_tip_id).hide();
			// exports.cache.$btns.show();
		},

		/**
		 * get_img_size_url
		 * 
		 * @params string size The img size,etc:
		 * 						square 		(mw/mh:80)
		 * 						thumbnail 	(mw/mh:120)
		 * 						thumb150 	(150x150,crop)
		 * 						mw600 		(mw:600)
		 * 						bmiddle  	(mw:440)
		 * 						large 		(organize)
		 * @return string The img url
		 * @version 1.0.0
		 * @author KM@INN STUDIO
		 */
		get_img_size_url : function(size,img_url){
			if(!size) size = 'square';
			var file_name = img_url.split('/'),
				file_name = file_name[file_name.length - 1],
				host_name = img_url.substr(7).split('/')[0],
				content = 'http://' + host_name + '/' + size + '/' + file_name;
			return content;
		},
		/**
		 * get_id
		 * 
		 * @params string Image url
		 * @return string The ID
		 * @version 1.0.0
		 * @author KM@INN STUDIO
		 */
		get_id : function(img_url){
			var id = img_url.split('/'),
				id = id[id.length - 1].split('.')[0];
			return id;
		},
		/**
		 * thumb_change
		 * 
		 * @params string img_url
		 * @return n/a
		 * @version 1.0.0
		 * @author KM@INN STUDIO
		 */
		thumb_change : function(img_url){
			var exports = sinapic_upload,
				id = exports.hook.get_id(img_url);
			for(var key in exports.config.sizes){
				/**
				 * start bind
				 */
				jQuery('#' + key + '_' + id).on('click',function(){
					var $this = jQuery(this),
						img_size_url = exports.hook.get_img_size_url($this.val(),img_url);
					jQuery('#img_url_' + id).val(img_size_url);
					jQuery('#img_link_' + id).attr('href',img_size_url);
					/**
					 * set cookie for next default clicked
					 */
					exports.hook.set_cookie(exports.config.cookie_last_size,$this.val(),365);
				});
			}
		},
		/**
		 * send_to_editor
		 * 
		 * @return 
		 * @version 1.0.0
		 * @author KM@INN STUDIO
		 */
		send_to_editor : function(h) {
			var ed, mce = typeof(tinymce) != 'undefined', qt = typeof(QTags) != 'undefined';

			if ( !wpActiveEditor ) {
				if ( mce && tinymce.activeEditor ) {
					ed = tinymce.activeEditor;
					wpActiveEditor = ed.id;
				} else if ( !qt ) {
					return false;
				}
			} else if ( mce ) {
				if ( tinymce.activeEditor && (tinymce.activeEditor.id == 'mce_fullscreen' || tinymce.activeEditor.id == 'wp_mce_fullscreen') )
					ed = tinymce.activeEditor;
				else
					ed = tinymce.get(wpActiveEditor);
			}

			if ( ed && !ed.isHidden() ) {
				// restore caret position on IE
				if ( tinymce.isIE && ed.windowManager.insertimagebookmark )
					ed.selection.moveToBookmark(ed.windowManager.insertimagebookmark);

				if ( h.indexOf('[caption') !== -1 ) {
					if ( ed.wpSetImgCaption )
						h = ed.wpSetImgCaption(h);
				} else if ( h.indexOf('[gallery') !== -1 ) {
					if ( ed.plugins.wpgallery )
						h = ed.plugins.wpgallery._do_gallery(h);
				} else if ( h.indexOf('[embed') === 0 ) {
					if ( ed.plugins.wordpress )
						h = ed.plugins.wordpress._setEmbed(h);
				}

				ed.execCommand('mceInsertContent', false, h);
			} else if ( qt ) {
				QTags.insertContent(h);
			} else {
				document.getElementById(wpActiveEditor).value += h;
			}

			try{tb_remove();}catch(e){};
		},
		
		clear_list : function(){
			var exports = sinapic_upload,
				$clear_list = jQuery('#clear_list');
			$clear_list.on('click',function(){
				exports.cache.$tools.slideUp();
				exports.cache.$completion_tip.slideUp();
				exports.cache.$tpl.slideUp('fast',function(){
					jQuery(this).find('*').remove();
				});
			});
		},
		/**
		 * batch_thumb_insert
		 *
		 * @return 
		 * @version 1.0.0
		 * @author KM@INN STUDIO
		 */
		batch_thumb_insert : function(){
			var exports = sinapic_upload;
			/** 
			 * get_tpl
			 */
			var get_tpl = function(img_url,with_link){
				if(typeof img_url == 'undefined') return false;
				var large_size_url = exports.hook.get_img_size_url('large',img_url),
				id = exports.hook.get_id(img_url),
				img_alt = jQuery('#img_alt_' + id).val(),
				tpl = '<img src="' + img_url + '" alt="' + img_alt + '"/>';
				if(with_link === true){
					tpl = '<a href="' + large_size_url + '" target="_blank">' + tpl + '</a>';
				}
				/** 
				 * wrap the <p>
				 */
				tpl = '<p>' + tpl + '</p>';
				return tpl;
			};
			/** 
			 * with link
			 */
			jQuery('#insert_list_with_link,#insert_list_without_link').on('click',function(){
				var $link = jQuery(this),
					$img_urls = exports.cache.$tpl.find('input:text.img_url');
				if(!$img_urls[0]) return;
				var tpl = [];
				$img_urls.each(function(i){
					var $this = jQuery(this),
					img_url = $this.val();
					if($link.attr('id') === 'insert_list_with_link'){
						tpl_content = get_tpl(img_url,true);
					}else{
						tpl_content = get_tpl(img_url,false);
					}
					tpl.push(tpl_content);
				});
				tpl = tpl.join(exports.hook.get_separate());
				/**
				 * send to editor
				 */
				exports.hook.send_to_editor(tpl);
				
			});
		},
		get_batch_type : function(){
			
		},
		
		get_separate : function(){
			var exports = sinapic_upload,
			$separate_img_type = jQuery('#separate_img_type');
			if(!$separate_img_type[0]) return '';
			switch($separate_img_type.val()){
				case 'nextpage':
					return '<!--nextpage-->';
					break;
				default:
					return '';
				
			}
		},
		
		
		/**
		 * thumb_insert
		 * 
		 * @params string img_url
		 * @return n/a
		 * @version 1.0.0
		 * @author KM@INN STUDIO
		 */
		thumb_insert : function(img_url){
			var exports = sinapic_upload,
				id = exports.hook.get_id(img_url),
				tpl = '';
			/**
			 * with link
			 */
			jQuery('#btn_with_link_' + id).on('click',function(){
				var $this = jQuery(this),
					new_img_src = jQuery('#img_url_' + id).val(),
					img_alt = jQuery('#img_alt_' + id).val();
				jQuery('#img_url_' + id).val();
				jQuery('#img_link_' + id).attr('href',$this.val());
				tpl = '<a href="' + img_url + '" target="_blank"><img src="' + new_img_src + '" alt="' + img_alt + '"/></a>';
				/**
				 * send to editor
				 */
				exports.hook.send_to_editor(tpl);
				return false;
			});
			/**
			 * without link
			 */
			jQuery('#btn_without_link_' + id).on('click',function(){
				var $this = jQuery(this),
					new_img_src = jQuery('#img_url_' + id).val(),
					img_alt = jQuery('#img_alt_' + id).val();
				jQuery('#img_url_' + id).val();
				jQuery('#img_link_' + id).attr('href',$this.val());
				tpl = '<img src="' + new_img_src + '" alt="' + img_alt + '"/>';
				/**
				 * send to editor
				 */
				window.send_to_editor(tpl);
				return false;
			});
			/**
			 * with link
			 */
			jQuery('.as_feature_image').off().on('click',function(){
				jQuery('.as_feature_image').not(this).removeAttr('checked');
			});
		},
		/**
		 * percent
		 * 
		 * @return n/a
		 * @version 1.0.0
		 * @author KM@INN STUDIO
		 */
		percent : function(){
			
		},
		/**
		 * get_cookie
		 * 
		 * @params string
		 * @return string
		 * @version 1.0.0
		 * @author KM@INN STUDIO
		 */
		get_cookie : function(c_name){
			var i,x,y,ARRcookies=document.cookie.split(';');
			for(i=0;i<ARRcookies.length;i++){
				x=ARRcookies[i].substr(0,ARRcookies[i].indexOf('='));
				y=ARRcookies[i].substr(ARRcookies[i].indexOf('=')+1);
				x=x.replace(/^\s+|\s+$/g,'');
				if(x==c_name) return unescape(y);
			}
		},
		/**
		 * set_cookie
		 * 
		 * @params string cookie key name
		 * @params string cookie value
		 * @params int the expires days
		 * @return n/a
		 * @version 1.0.0
		 * @author KM@INN STUDIO
		 */
		set_cookie : function(c_name,value,exdays){
			var exdate = new Date();
			exdate.setDate(exdate.getDate() + exdays);
			var c_value=escape(value) + ((exdays==null) ? '' : '; expires=' + exdate.toUTCString());
			document.cookie = c_name + '=' + c_value;
		},
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
		},

		/**
		 * sinapic_upload.hook.tpl
		 * 
		 * @params object args
		 * args = {
		 * 		'img_url' : 'http://....w1e1iyntr4oaj.jpg',
		 * 		'size' 	: see sinapic_upload.hook.get_img_size_url()
		 * }
		 * @return string HTML
		 * @version 1.0.0
		 * @author KM@INN STUDIO
		 */
		tpl : function(args){
			if(!args) return false;
			var exports = sinapic_upload,
				id = exports.hook.get_id(args.img_url),
				img_url = args.img_url,
				size_string = '',
				i = 0,
				checked = '',
				cookie = exports.hook.get_cookie(exports.config.cookie_last_size),
				last_img_size = cookie ? cookie : 'thumb150';
			for(var key in exports.config.sizes){
				i++;
				/**
				 * check the cookie
				 */
				
				if(!cookie){
					checked = i === 1 ? ' checked="checked" ' : '';
				}else{
					checked = cookie === key ? ' checked="checked" ' : '';
				}
				/**
				 * content
				 */
				size_string += 
					'<label for="' + key + '_' + id + '" title="' + exports.config.sizes[key] + '" class="sizes_label">'+
					'<input id="' + key + '_' + id + '" name="sinapic[size_' + id + ']" class="sizes" type="radio" value="' + key + '"' + checked + '/>'+
					key +
					'</label>'+
				'';
			}
			var content = 
'<table class="tpl_table" id="table_' + id + '"><tbody>'+
	'<tr>'+
		'<th>'+
			'<img id="img_preview_' + id + '" class="img_preview" src="' + exports.hook.get_img_size_url('square',img_url) + '" alt="icon"/>'+
		'</th>'+
		'<td>'+
			size_string +
		'</td>'+
	'</tr>'+
	'<tr>'+
		'<th>'+
			'<label for="img_url_' + id + '"><a id="img_link_' + id + '" href="' + exports.hook.get_img_size_url(last_img_size,img_url) + '" target="_blank">' + exports.config.lang.M00003 + '</a></label>'+
		'</th>'+
		'<td>'+
			'<input id="img_url_' + id + '" type="text" class="img_url regular-text" value="' + exports.hook.get_img_size_url(last_img_size,img_url) + '" name="sinapic[img_url_' + id + ']" readonly="true"/>'+
		'</td>'+
	'</tr>'+
	'<tr>'+
		'<th>'+
			'<label for="img_alt_' + id + '">' + exports.config.lang.M00004 + '</label>'+
		'</th>'+
		'<td>'+
			'<input id="img_alt_' + id + '" type="text" class="img_alt regular-text" placeholder="' + exports.config.lang.M00005 + '"/>'+
		'</td>'+
	'</tr>'+
	'<tr>'+
		'<th>' + exports.config.lang.M00006 + '</th>'+
		'<td scope="col" colspan="2">'+
			'<a id="btn_with_link_' + id + '" href="javascript:void(0);" class="button button-primary">' + exports.config.lang.M00007 + '</a> '+
			'<a id="btn_without_link_' + id + '" href="javascript:void(0);" class="button">' + exports.config.lang.M00008 + '</a> '+
			'<label for ="btn_as_feature_' + id + '" class="button"><input id="btn_as_feature_' + id + '" type="checkbox" name="sinapic[as_feature_image]" class="as_feature_image" value="img_url_' + id + '"/> ' + exports.config.lang.M00009 + '</label>'+
		'</td>'+
	'</tr>'+
'</tbody></table>'+
'';
			return content;
		}
	},
	
	format : function(){
		var ary = [];
		for(var i=1;i<arguments.length;i++){
			ary.push(arguments[i]);
		}
		return arguments[0].replace(/\{(\d+)\}/g,function(m,i){
			return ary[i];
		});
	}
};