/**
 * sinapic_upload
 * 
 * @version 2.0
 * @author KM@INN STUDIO
 */
 
function sinapicv2(){

if(!$) $ = jQuery;

this.config = {
	process_url 		: '',
	file_id 			: '#sinapicv2-file',
	add_id 				: '#sinapicv2-add',
	loading_tip_id 		: '#sinapicv2-loading-tip',
	completion_tip_id	: '#sinapicv2-completion-tip',
	btns_id 			: '#sinapicv2-btns',
	tpl_id				: '#sinapicv2-tpl-container',
	tools_id			: '#sinapicv2-tools',
	unauthorize_id 		: '#sinapicv2-unauthorize',
	go_authorize_id 	: '#sinapicv2-go-authorize',
	reload_id 			: '#sinapicv2-reloadme',
	error_file_tip_id	: '#sinapicv2-error-file-tip',
	error_files			: '#sinapicv2-error-files',
	progress_id			: '#sinapicv2-progress',
	progress_tx_id		: '#sinapicv2-progress-tx',
	progress_bar_id		: '#sinapicv2-progress-bar',
	accept_formats 		: ['png','jpg','gif'],
	cookie_last_size 	: 'sinapic_last_size',
	authorized			: false,
	is_ssl				: false,
	max_upload_size		: 1024*2048,
	interval_timer 		: 3000,
	show_title			: false,
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
		M00009 : 'As custom meta feature image',
		M00010 : 'Detects that files can not be uploaded:'
	},
	sizes : {
		thumb150 	: 'max 150x150, crop',
		mw600 		: 'max-width:600',
		large 		: 'original size',
		square 		: 'max-width:80 or max-height:80',
		thumbnail 	: 'max-width:120 or max-height:120',
		bmiddle 	: 'max-width:440'
		
	}
}
var cache = {
	errors : [],
	file_index : 0,
	file_count : 0,
	files : false,
	error_files : [],
	is_uploading : false
},
	that = this;
this.init = function(){
	$(document).ready(function(){
		cache.$loading_tip 		= $(that.config.loading_tip_id).hide();
		cache.$add 				= $(that.config.add_id).show();
		cache.$file 			= $(that.config.file_id);
		cache.$reload 			= $(that.config.reload_id);
		cache.$completion_tip 	= $(that.config.completion_tip_id);
		cache.$tools 			= $(that.config.tools_id);
		cache.$tpl 				= $(that.config.tpl_id);
		cache.$unauthorize		= $(that.config.unauthorize_id);
		cache.$error_file_tip	= $(that.config.error_file_tip_id);
		cache.$error_files		= $(that.config.error_files);
		cache.$progress			= $(that.config.progress_id);
		cache.$progress_tx		= $(that.config.progress_tx_id);
		cache.$progress_bar		= $(that.config.progress_bar_id);
		
		authorize_init();
		bulk_insert_imgs();
		clear_list();
		/** 
		 * reload 
		 */
		if(cache.$reload[0]){
			cache.$reload.on('click',function(){
				reload_me();
				return false;
			});
		}
		/** 
		 * $file event
		 */
		cache.$file.on({
			change 		: file_select,
			drop 		: file_select
			// drop 		: file_dop
		})
	});
}
function reload_me(){
	cache.$loading_tip.show();
	cache.$unauthorize.hide();
	$.ajax({
		url :that.config.process_url,
		dataType : 'json',
		data : {
			type : 'check_authorize'
		}
	}).done(function(data){
		if(data && data.status === 'success'){
			exports.cache.$add.show()
		}else if(data && data.status === 'error'){
			alert(data.msg);
			exports.cache.$unauthorize.show();
		}else{
			alert(that.config.lang.E00003);
			exports.cache.$unauthorize.show();
		}
	}).error(function(data){
		exports.cache.$unauthorize.show();
	}).always(function(){
		exports.cache.$loading_tip.hide();
	})
}
function file_select(e){
	e.stopPropagation();  
	e.preventDefault();  

	cache.files = e.target.files.length ? e.target.files : e.originalEvent.dataTransfer.files;
	cache.file_count = cache.files.length;
	cache.file = cache.files[0];
	cache.file_index = 0;
	
	cache.$error_file_tip.hide();
	cache.$completion_tip.hide();

	/** 
	 * start upload file
	 */
	file_upload(cache.files[0]);
}
function file_upload(file){
	cache.start_time = new Date();
	var	reader = new FileReader();
	reader.onload = function (e) {
		/** 
		 * exceed max upload size and in not allow file type
		 */
		if(file.size > that.config.max_upload_size || !/image/.test(file.type)){
			error_file_tip(file);
			return false;
		}
		submission(file);

	};
	reader.readAsDataURL(file);		
}
function error_file_tip(file){
	if(file === 'hide'){
		cache.$error_files.empty();
		cache.$error_file_tip.hide();
		return;
	}
	var $content = $('<span class="error-file">' + file.name + '</span>');
	cache.$error_files.append($content);
	cache.$error_file_tip.show();
}
function submission(file){
	if(cache.is_uploading) return;
	cache.is_uploading = true;
	beforesend_callback();
	
	var fd = new FormData(),
		xhr = new XMLHttpRequest();
	fd.append('file',file);
	xhr.open('post',that.config.process_url + '&type=upload');
	xhr.onload = complete_callback;
	xhr.onreadystatechange = function(){
		if (xhr && xhr.readyState === 4) {
			status = xhr.status;
			if (status >= 200 && status < 300 || status === 304) {
			
			}else{
				error_callback();
			}
		}
		cache.is_uploading = false;
		xhr = null;
	}
	xhr.upload.onprogress = function(e){
		if (e.lengthComputable) {
			var percent = e.loaded / e.total * 100;		
			cache.$progress_bar.animate({
				width : percent + '%'
			},500);
			
		}
	}
	xhr.send(fd);
}
/** 
 * upload_started
 */
function upload_started(i,file,count){
	var t = format(that.config.lang.M00001,i,count);
	uploading_tip('loading',t);
}
/**
 * The tip when pic is uploading
 *
 * @param string status 'loading','success' ,'error'
 * @param string text The content of tip
 * @return 
 * @version 1.0.1
 * @author KM@INN STUDIO
 */
function uploading_tip(status,text){
	/** 
	 * uploading status
	 */
	if(!status || status === 'loading'){
		cache.$progress_tx.html(status_tip('loading','middle',text));
		cache.$progress.show();
		cache.$add.hide();
		cache.$completion_tip.hide();
	/** 
	 * success status
	 */
	}else{
		cache.$completion_tip.html(status_tip(status,text)).show();
		cache.$progress.hide();
		cache.$add.show();
	}
}
function beforesend_callback(){
	var tx = format(that.config.lang.M00001,cache.file_index + 1,cache.file_count);
	cache.$progress_bar.width('0');
	uploading_tip('loading',tx);
}
function complete_callback(){
	var data = this.responseText,
		url;
	try{
		data = $.parseJSON(this.responseText);
	}catch(error){
		data = false;
	}

	cache.file_index++;

	/** 
	 * success
	 */
	if(data && data.status === 'success'){
		url = data.img_url;
		// console.log(url);
		var args = {
				'img_url' : url,
				'size' : ''
			};
		var $tpl_table = $(tpl(args));
		
		$tpl_table.hide();
		cache.$tpl.show().prepend($tpl_table);
		$tpl_table.fadeIn('slow');
		
		/**
		 * bind thumb_change click
		 */
		change_size(url);
		/**
		 * focus alt attribute
		 */
		$('#img-alt-' + get_id(args.img_url)).val($('#title').val()).focus().select();
		/**
		 * bind thumb_insert click
		 */
		insert_img(url);
		
		
		/** 
		 * show tools
		 */
		if(cache.$tools.is(':hidden')){
			cache.$tools.slideDown();
		}
		
		/** 
		 * check all thing has finished, if finished
		 */
		if(cache.file_count === cache.file_index){
			cache.all_complete = true;
			cache.is_uploading = false;
			var tx = format(that.config.lang.M00002,cache.file_count);
			uploading_tip('success',tx);
			/** 
			 * reset file input
			 */
			cache.$file.val('');

		/** 
		 * upload next pic
		 */
		}else{
			upload_next(cache.files[cache.file_index]);
		}
	/** 
	 * no success
	 */
	}else{
		/** 
		 * notify current file is error
		 */
		if(cache.file_index > 0){
			error_file_tip(cache.files[cache.file_index - 1]);
		}
		/** 
		 * if have next file, continue to upload next file
		 */
		if(cache.file_count > cache.file_index){
			upload_next(cache.files[cache.file_index]);
		/** 
		 * have not next file, all complete
		 */
		}else{
			cache.is_uploading = false;
			if(data && data.status === 'error'){
				error_callback(data.msg);
			}else{
				error_callback(that.config.lang.E00002);
				console.error(data);
			}
			/** 
			 * reset file input
			 */
			cache.$file.val('');

		}
	}
}
function upload_next(next_file){
	/** 
	 * check interval time
	 */
	var end_time = new Date(),
		interval_time = end_time - cache.start_time,
		timeout = that.config.interval - interval_time,
		timeout = timeout < 0 ? 0 :timeout;
	/** 
	 * if curr time > interval time, upload next pic right now 
	 */
	setTimeout(function(){
		file_upload(next_file);
	},timeout);
}
function error_callback(msg){
	msg = msg ? msg : that.config.lang.E00002;
	uploading_tip('error',msg);
}
/**
 * get_img_url_by_size
 * 
 * @params string size The img size,eg:
 * 						square 		(mw/mh:80)
 * 						thumbnail 	(mw/mh:120)
 * 						thumb150 	(150x150,crop)
 * 						mw600 		(mw:600)
 * 						bmiddle  	(mw:440)
 * 						large 		(organize)
 * @return string The img url
 * @version 1.0.2
 * @author KM@INN STUDIO
 */
function get_img_url_by_size(size,img_url){
	if(!size) size = 'square';
	var file_obj = img_url.split('/'),
		len = file_obj.length,
		basename = file_obj[len - 1],
		old_size = file_obj[len - 2],
		hostname = img_url.substr(0,img_url.indexOf(old_size));
		url = hostname + size + '/' + basename;
	return url;
}
/**
 * get_id
 * 
 * @params string Image url
 * @return string The ID
 * @version 1.0.0
 * @author KM@INN STUDIO
 */
function get_id(img_url){
	var id = img_url.split('/'),
		id = id[id.length - 1].split('.')[0];
	return id;
}
/**
 * change_size
 * 
 * @params string img_url
 * @return n/a
 * @version 1.0.1
 * @author KM@INN STUDIO
 */
function change_size(img_url){
	var id = get_id(img_url);
	for(var key in that.config.sizes){
		/**
		 * start bind
		 */
		$('#' + key + '-' + id).on('click',function(){
			var $this = $(this),
				img_size_url = get_img_url_by_size($this.val(),img_url);
			$('#img-url-' + id).val(img_size_url);
			$('#img-link-' + id).attr('href',img_size_url);
			/**
			 * set cookie for next default clicked
			 */
			set_cookie(that.config.cookie_last_size,$this.val(),365);
		});
	}
}
/**
 * send_to_editor
 * 
 * @return 
 * @version 1.0.0
 * @author KM@INN STUDIO
 */
function send_to_editor(h) {
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
}
function authorize_init(){
	if(that.config.authorized === false){
		cache.$unauthorize.show();
		cache.$add.hide();
	}else{
		cache.$add.show();
	}
}
function get_split_str(){
	var $split = $('#sinapicv2-split');
	if(!$split[0]) return '';
	switch($split.val()){
		case 'nextpage':
			return '<!--nextpage-->';
			break;
		default:
			return '';
	}
}

/** 
 * bulk insert images to tpl
 */
function bulk_insert_imgs(){
	/** 
	 * get_tpl
	 */
	var get_tpl = function(url,with_link){
		if(typeof url == 'undefined') return false;
		var tpl,
			large_size_url = get_img_url_by_size('large',url),
			id = get_id(url),
		
			new_img_src = $('#img-url-' + id).val(),
			img_alt_val = $('#img-alt-' + id).val(),
			img_alt = ' alt="' + img_alt_val + '"',
			img_title = that.config.show_title ? ' title="' + img_alt_val + '" ': '',
			img = '<img src="' + new_img_src + '" ' + img_alt + img_title + '/>';

		if(with_link === true){
			tpl = '<a href="' + large_size_url + '" target="_blank">' + img + '</a>';
		}else{
			tpl = img;
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
	$('#sinapicv2-insert-list-with-link,#sinapicv2-insert-list-without-link').on('click',function(){
		var $link = $(this),
			$img_urls = cache.$tpl.find('input.img-url');
		if(!$img_urls[0]) return;
		var tpl = [];
		$img_urls.each(function(i){
			var $this = $(this),
			url = $this.val();
			if($link[0].id === 'sinapicv2-insert-list-with-link'){
				tpl_content = get_tpl(url,true);
			}else{
				tpl_content = get_tpl(url,false);
			}
			tpl.push(tpl_content);
		});
		tpl = tpl.join(get_split_str());
		/**
		 * send to editor
		 */
		send_to_editor(tpl);
		
	});
}
/**
 * insert_img
 * 
 * @params string img_url
 * @return n/a
 * @version 2.0.0
 * @author KM@INN STUDIO
 */
function insert_img(img_url){
	var id = get_id(img_url),
		tpl = '',
		$img_url = $('#img-url-' + id).on('click',function(){
			$(this).select();
		});
		new_img_src = $img_url.val(),
		img_alt_val = $('#img-alt-' + id).val(),
		img_alt = ' alt="' + img_alt_val + '"',
		img_title = that.config.show_title ? ' title="' + img_alt_val + '" ': '',
		img = '<img src="' + new_img_src + '" ' + img_alt + img_title + '/>';
	/**
	 * with link
	 */
	$('#btn-with-link-' + id).on('click',function(){
		tpl = '<a href="' + img_url + '" target="_blank">' + img + '</a>';
		/**
		 * send to editor
		 */
		send_to_editor(tpl);
		return false;
	});
	/**
	 * without link
	 */
	$('#btn-without-link-' + id).on('click',function(){
		tpl = img;
		/**
		 * send to editor
		 */
		window.send_to_editor(tpl);
		return false;
	});
	/**
	 * as feature
	 */
	$('#btn-as-feature-' + id).on('click',function(){
		alert('This feature will be finished next version.');
		$('.as-feature-image').not(this).removeAttr('checked');
		return false;
	});
}
/**
 * get_cookie
 * 
 * @params string
 * @return string
 * @version 1.0.0
 * @author KM@INN STUDIO
 */
function get_cookie(c_name){
	var i,x,y,ARRcookies=document.cookie.split(';');
	for(i=0;i<ARRcookies.length;i++){
		x=ARRcookies[i].substr(0,ARRcookies[i].indexOf('='));
		y=ARRcookies[i].substr(ARRcookies[i].indexOf('=')+1);
		x=x.replace(/^\s+|\s+$/g,'');
		if(x==c_name) return unescape(y);
	}
}
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
function set_cookie(c_name,value,exdays){
	var exdate = new Date();
	exdate.setDate(exdate.getDate() + exdays);
	var c_value=escape(value) + ((exdays==null) ? '' : '; expires=' + exdate.toUTCString());
	document.cookie = c_name + '=' + c_value;
}
/**
 * sinapic_upload.hook.tpl
 * 
 * @params object args
 * args = {
 * 		'img_url' : 'http://....w1e1iyntr4oaj.jpg',
 * 		'size' 	: see sinapic_upload.hook.get_img_url_by_size()
 * }
 * @return string HTML
 * @version 1.0.0
 * @author KM@INN STUDIO
 */
function tpl(args){
	if(!args) return false;
	var id = get_id(args.img_url),
		img_url = args.img_url,
		size_string = '',
		i = 0,
		checked = '',
		cookie = get_cookie(that.config.cookie_last_size),
		last_img_size = cookie ? cookie : 'thumb150';
	for(var key in that.config.sizes){
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
			'<label for="' + key + '-' + id + '" title="' + that.config.sizes[key] + '" class="sizes-label">'+
			'<input id="' + key + '-' + id + '" name="sinapic[size-' + id + ']" class="size-input" type="radio" value="' + key + '"' + checked + '/>'+
			'<span>' + key +'</span>'+
			'</label>'+
		'';
	}
	var content = 
'<table class="tpl-table" id="table-' + id + '"><tbody>'+
'<tr>'+
'<th>'+
	'<a id="img-link-' + id + '" href="' + get_img_url_by_size(last_img_size,img_url) + '" target="_blank"><img id="img-preview-' + id + '" class="img-preview" src="' + get_img_url_by_size('square',img_url) + '" alt="preview"/></a>'+
'</th>'+
'<td>'+
	'<div class="size-group">'+
	size_string +
	'</div>'+
	'<input id="img-url-' + id + '" type="text" class="img-url regular-text" value="' + get_img_url_by_size(last_img_size,img_url) + '" name="sinapic[img-url-' + id + ']" readonly="true"/>'+
'</td>'+
'</tr>'+
'<tr>'+
'<th>'+
	'<label for="img-alt-' + id + '">' + that.config.lang.M00004 + '</label>'+
'</th>'+
'<td>'+
	'<input id="img-alt-' + id + '" type="text" class="img-alt regular-text" placeholder="' + that.config.lang.M00005 + '"/>'+
'</td>'+
'</tr>'+
'<tr>'+
'<th>' + that.config.lang.M00006 + '</th>'+
'<td scope="col" colspan="2">'+
	'<a id="btn-with-link-' + id + '" href="javascript:void(0);" class="button button-primary">' + that.config.lang.M00007 + '</a> '+
	'<a id="btn-without-link-' + id + '" href="javascript:void(0);" class="button">' + that.config.lang.M00008 + '</a> '+
	'<label for="btn-as-feature-' + id + '" class="button btn-as-feature-image"><input id="btn-as-feature-' + id + '" type="checkbox" name="sinapic[as-feature-image]" class="as-feature-image" value="img-url-' + id + '"/> ' + that.config.lang.M00009 + '</label>'+
'</td>'+
'</tr>'+
'</tbody></table>'+
'';
	return content;
}
function format(){
	var ary = [];
	for(var i=1;i<arguments.length;i++){
		ary.push(arguments[i]);
	}
	return arguments[0].replace(/\{(\d+)\}/g,function(m,i){
		return ary[i];
	});
}

/**
 * status_tip
 *
 * @param mixed
 * @return string
 * @version 1.1.0
 * @author KM@INN STUDIO
 */
function status_tip(){
	var defaults = ['type','size','content','wrapper'],
		types = ['loading','success','error','question','info','ban','warning'],
		sizes = ['small','middle','large'],
		wrappers = ['div','span'],
		type = null,
		icon = null,
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
	
		switch(type){
			case 'success':
				icon = 'smiley';
				break;
			case 'error' :
				icon = 'no';
				break;
			case 'info':
			case 'warning':
				icon = 'info';
				break;
			case 'question':
			case 'help':
				icon = 'editor-help';
				break;
			case 'ban':
				icon = 'minus';
				break;
			case 'loading':
			case 'spinner':
				icon = 'update';
				break;
			default:
				icon = type;
		}

		var tpl = '<' + wrapper + ' class="tip-status tip-status-' + size + ' tip-status-' + type + '"><span class="dashicons dashicons-' + icon + '"></span><span class="after-icon">' + content + '</span></' + wrapper + '>';
		return tpl;
}

/** 
 * Clean tpl list
 */
function clear_list(){
	cache.$clear_list = $('#sinapicv2-clear-list');
	if(!cache.$clear_list[0]) return false;
	cache.$clear_list.on('click',function(){
		cache.$tools.slideUp();
		cache.$completion_tip.slideUp();
		cache.$tpl.slideUp('fast',function(){
			$(this).find('*').remove();
		});
	});
}

}
