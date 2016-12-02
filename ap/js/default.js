// JavaScript Document
function editor(id,prop){
	var v={
// General options
mode : "none",
theme : "advanced",
plugins : "autolink,lists,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,wordcount,advlist,autosave",
language : "ru",

// Theme options
theme_advanced_buttons1 : "save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontselect,fontsizeselect",
theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen",
theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak,restoredraft",
theme_advanced_toolbar_location : "top",
theme_advanced_toolbar_align : "left",
theme_advanced_statusbar_location : "bottom",

document_base_url : tinymce_base_url,
relative_urls : true,

theme_advanced_resize_horizontal : false,
theme_advanced_resizing : true,

// Example content CSS (should be your site CSS)
content_css : "ap/css/html-editor-content.css",

// Drop lists for link/image/media/template dialogs
template_external_list_url : "lists/template_list.js",
external_link_list_url : "lists/link_list.js",
external_image_list_url : "lists/image_list.js",
media_external_list_url : "lists/media_list.js",


// Replace values for the template plugin
template_replace_values : {
	username : "Some User",
	staffid : "991234"
},

file_browser_callback: function(field_name, url, type, win){
		tinyMCE.activeEditor.windowManager.open({
			file: 'uploader.php?opener=tinymce&type='+type,
			title: 'Active Page File Manager',
			width: 700,
			height: 500,
			resizable: "yes",
			inline: true,
			close_previous: "no",
			popup_css: false
		},{
			callback: function(url){
				win.document.getElementById(field_name).value=url;
				if(typeof(win.ImageDialog) != "undefined"){
					if(win.ImageDialog.getImageData)win.ImageDialog.getImageData();
					if(win.ImageDialog.showPreviewImage)win.ImageDialog.showPreviewImage(url);
				};	
			}
		});
		return false;
	}
};
	if(typeof(prop)=='object')for(var i in prop)v[i]=prop[i];
	tinyMCE.init(v);
	if(id&&v.mode=='none')tinyMCE.execCommand("mceAddControl",true,id);
};

todo.onload(function(){
initDropMenu('sidemenu');
todo.loop(document.getElementsByTagName('table'),function(){
	if(this.className=='rows'){
		this._getSelectedItems=function(){
			var inp=this.getElementsByTagName('input'),v=[];
			for(var i=0;i<inp.length;i++)if(inp[i].className=='select_row'&&inp[i].checked)v.push(inp[i].value);
			return v;
		};
		todo.loop(this.getElementsByTagName('input'),function(){
			if(this.type.toLowerCase()!='checkbox')return;
			switch(this.className){
				case 'select_row':
					this._checkSelection=function(){this.parentNode.parentNode.className=this.checked?'selected':null;};
					this.onclick=function(){try{
						var el=this.parentNode.offsetParent.rows[0].getElementsByTagName('input');
						for(var i=0;i<el.length;i++)if(el[i].className=='select_all_rows')el[i]._checkSelection();
						this._checkSelection();
					}catch(er){}};
					this._checkSelection();
					break;
				case 'select_all_rows':
					this._checkSelection=function(){
						this.checked=true;
						var el=this.parentNode.offsetParent.getElementsByTagName('input');
						for(var i=0;i<el.length;i++)if(el[i].className=='select_row'&&!el[i].checked){
							this.checked=false;
							return;
						}
					};
					this.onclick=function(){try{
						var el=this.parentNode.offsetParent.getElementsByTagName('input');
						for(var i=0;i<el.length;i++)if(el[i].className=='select_row'){
							el[i].checked=this.checked;
							el[i]._checkSelection();
						}
					}catch(er){}};
					this._checkSelection();
					break;
			}
		});
	}
});
});