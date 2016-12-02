/**
* Drop Menu
* author: Pavel Khoroshkov aka pgood
* http://pgood.ru
*/
function initDropMenu(id){
var menu=todo.get(id);
if(!menu)return;
var _dmCookie=new Cookie('sidemenu'+id),
	indexes=_dmCookie.indexes?_dmCookie.indexes.split(','):[],
	isOpen=function(id){for(var i=0;i<indexes.length;i++)if(indexes[i]==id)return true;},
	createFolderButton=function(state,parent){
		var e=parent.appendChild(todo.create('span'));
		e._dmChangeState=function(){
			this._dmState=!this._dmState;
			this.className=this._dmState?'open':'close';
		};
		e.onclick=function(){
			if(this.parentNode.parentNode._dmSubmenu())
				this._dmChangeState();
			return false;
		};
		e._dmState=!state;
		e._dmChangeState();
		return e;
	};
_dmCookie.addIndex=function(id){
	var idx=this.indexes?this.indexes.split(','):[];
	for(var i=0;i<idx.length;i++)if(idx[i]==id)return;
	idx.push(id);
	this.indexes=idx.join(',');
	this.store(1);
};
_dmCookie.removeIndex=function(id){
	var idx=this.indexes?this.indexes.split(','):[];
	for(var i=0;i<idx.length;i++)if(idx[i]==id){
		idx.splice(i,1);
		this.indexes=idx.join(',');
		this.store(1);
	}
};
todo.loop(menu.getElementsByTagName('dt'),function(k){
	this._dmIndex=k;
	this._dmGetDd=function(){
		return function(e){while(e=e.nextSibling)if(e.nodeType==1)return e.nodeName.toLowerCase()=='dd'?e:null;}(this);
	};
	var	dd=this._dmGetDd(),
		s=this.className.search(/open/)!=-1||isOpen(this._dmIndex);
	if(dd){
		this._dmButton=createFolderButton(s,this.getElementsByTagName('a')[0]||this.getElementsByTagName('span')[0]);
		if(s)dd.style.height='auto';
		this._dmSubmenu=function(){
			var dd=this._dmGetDd(),
				m=function(){
					var r=todo.motion(dd.style.height,dd._task.start,dd._task.finish,4);
					if(r.ready){
						dd.style.height=dd._task.finish?'auto':0;
						window.clearInterval(dd._timer);
						dd._timer=null;
					}else dd.style.height=r.res+'px';
				};
			if(!dd._timer){
				if(this._dmButton._dmState){
					_dmCookie.removeIndex(this._dmIndex);
					dd.style.height=dd.offsetHeight+'px';
					dd._task={'start':dd.offsetHeight,'finish':0};
				}else{
					_dmCookie.addIndex(this._dmIndex);
					if(isNaN(parseInt(dd.style.height)))dd.style.height=0;
					dd._task={'start':0,'finish':dd.getElementsByTagName('dl')[0].offsetHeight};
				};
				dd._timer=window.setInterval(m,50);
				return true;
			}
			return false;
		}
	}
});
};