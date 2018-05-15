// JavaScript Document
function initDinamicRowList(e){
};

function initImageFieldset(fs,fieldName,maximum){
fs._fieldName=fieldName;
fs._maximum=maximum||30;
fs._stopEvent=function(event){
	var e=event||window.event;
	if(e.preventDefault){e.preventDefault();e.stopPropagation();}else{e.returnValue=false;e.cancelBubble=true;};
	return e;
};
fs.checkAddButton=function(){
	var b=todo.get('editimage'+this._fieldName);
	if(b)b.disabled=this._getNumImages()>=this._maximum;
}
fs.ondragover=
fs.ondragenter=function(event){
	var e=this._stopEvent(event)
		,fg_trg
		,target=e.target||e.srcElement;
	if(target){
		if(fg_trg=this._getFigureByElement(target))
			fg_trg.className='dragover';
	}
};
fs.ondragleave=function(event){
	var e=this._stopEvent(event)
		,fg_trg
		,target=e.target||e.srcElement;
	if(target){
		if(fg_trg=this._getFigureByElement(target))
			fg_trg.className='';
	};
};
fs.ondrop=function(event){
	var e=this._stopEvent(event),
		fg_src=this._getFigure(e.dataTransfer),
		fg_trg,
		target=e.target||e.srcElement;
	if(this._getNumImages()>1 && fg_src && target){
		if(fg_trg=this._getFigureByElement(target)){
			this.insertBefore(fg_src,fg_trg);
			fg_trg.className='';
		}else this.appendChild(fg_src);
		this.setSortValue();
	};
};
fs._getFigure=function(dt){
	if(dt){
		var data=dt.getData(dt.types && dt.types[0] ? dt.types[0] : 'URL'),
			m=data?data.match(/([^"\n]+\.jpg[^"\n]*)/):null;
		if(m)return this._getFigureBySrc(m[0]);
	}
};
fs._getFigureByElement=function(e){
	while(typeof(e)=='object' && e.tagName){
		if(e.tagName.toLowerCase()=='figure'){
			if(e.parentNode.className.match(/gallery/))return e;
			else break;
		};
		e=e.parentNode;
	};
};
fs._getFigureBySrc=function(src){
	var ns=this.getElementsByTagName('img'),prep=function(s){return s.replace(/&amp;/g,'&');};
	for(var i=0;i<ns.length;i++)
		if(ns[i].parentNode.tagName.toLowerCase()=='figure' && prep(ns[i].src)==prep(src))return ns[i].parentNode;
};
fs._addImage=function(src,id,title){
	if(this.style.display=='none')this.style.display='block';
	var figure=this.appendChild(todo.create('figure')),name=this._fieldName+(id?'['+id+']':'___new[]');
	figure.innerHTML='<img src="image.php?src='+src+'&w=130&h=100" width="130" height="100" alt="">'
		+(this._hasTitle?'<input type="button" class="titleImageButton"><input type="hidden" name="title_'+name+'" class="titleImageField" value="'+(title?title:'')+'">':'')
		+'<input type="button" class="deleteImageButton">'
		+'<input type="hidden" name="'+name+'" value="'+src+'">';
	this._initImages();
	this.setSortValue();
	this.checkAddButton();
};
fs._getNumImages=function(){
	return this.getElementsByTagName('figure').length;
}
fs._deleteImage=function(figure){
	this.removeChild(figure);
	if(!this.getElementsByTagName('figure').length)this.style.display='none';
	this.setSortValue();
	this.checkAddButton();
};
fs._initImages=function(){todo.loop(this.getElementsByTagName('input'),function(){
	if(this.className=='deleteImageButton')this.onclick=function(){if(window.confirm('Удалить картинку?')){var fg=this.parentNode;fg.parentNode._deleteImage(fg);}}
	else if(this.className=='titleImageButton')this.onclick=function(){
		var e=this;
		while(e=e.nextSibling)if(e.className=='titleImageField'){
			var v=window.prompt('Введите заголовок',e.value);
			if(v!==null)e.value=v;
			return;
		}
	}
});};
fs.setSortValue=function(field){
	var queue=[],
		newCounter=0,
		inp=todo.get(this._fieldName+'_sort_order'),
		ns=this.getElementsByTagName('input'),
		m,
		p1=new RegExp('^'+this._fieldName+'___new\\[\\]$'),
		p2=new RegExp('^'+this._fieldName+'\\['+this._fieldName+'_IMAGE_ID_([a-z_]*[0-9]+)');
	if(inp){
		for(var i=0;i<ns.length;i++){
			if(ns[i].name.match(p1)){
				newCounter++;
				queue.push('new'+newCounter);
			}else if(m=ns[i].name.match(p2)){
				queue.push('id'+m[1]);
			}
		};
		inp.value=queue.join(',');
	}
};
};