function todo(){
	this.action=[];
	this.execute=function(){if(!this.action)return;for(var i in this.action)try{this.action[i]();}catch(er){alert('todo >> "'+er.message+'" in "'+er.fileName+'" on "'+er.lineNumber+'"');};this.action=null;};
	var onLoadAction=function(o){return function(){o.execute();}}(this);
	if(document.addEventListener)document.addEventListener('DOMContentLoaded',onLoadAction,false);
	else if(document.attachEvent)document.attachEvent('onreadystatechange',function(){if(document.readyState==='complete')onLoadAction();});
	else if(window.addEventListener)window.addEventListener('load',onLoadAction,false);
	else if(window.attachEvent)window.attachEvent('onload',onLoadAction);
};
todo.prototype.get=function(id){return document.getElementById(id);};
todo.prototype.create=function(tag,attrs,text,style){var e=document.createElement(tag);if(attrs)for(var i in attrs)switch(i){case 'class': e.className=attrs[i];break;case 'id': e.id=attrs[i];break;default: e.setAttribute(i,attrs[i]);break;};if(text)e.appendChild(document.createTextNode(text));if(style)for(var i in style)e.style[i]=style[i];return e;};
todo.prototype.onload=function(func){if(String(typeof(func)).match(/function/i))this.action.push(func);};
todo.prototype.loop=function(e,func,i,step){if(!e||!e.length)return;step=step?Math.abs(step):1;var res;for(var i=i?i:0;i<e.length;i+=step){if(typeof(e[i])=='object')res=func.call(e[i],i);else res=func(i,e[i]);if(res===false)break;}};
todo.prototype.stopPropagation=function(e){e=e||window.event;if(!e)return;if(e.stopPropagation)e.stopPropagation();else e.cancelBubble=true;};
todo.prototype.ajax=function(url,callback,params,method){
	if(XMLHttpRequest==undefined){XMLHttpRequest=function(){try{return new ActiveXObject('Msxml2.XMLHTTP.6.0');}catch(e){};try{return new ActiveXObject('Msxml2.XMLHTTP.3.0');}catch(e){};try{return new ActiveXObject('Msxml2.XMLHTTP');}catch(e){};try{return new ActiveXObject('Microsoft.XMLHTTP');}catch(e){};throw new Error('This browser does not support XMLHttpRequest');}};
	var r=new XMLHttpRequest,data='';
	r._callback=callback;
	r.onreadystatechange=function(){if(this.readyState==4){if(this.status==200&&this._callback)this._callback(this.responseText,this.responseXML);}};
	if(method=='post'){
		if(params)for(var i in params)data+=i+'='+encodeURIComponent(params[i])+'&';
		r.open('POST',url,true);
		r.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
		r.setRequestHeader('Content-Length',data.length);
	}else{if(params){
		url+=url.match(/\?/)?'&':'?';
		for(var i in params)url+=i+'='+encodeURIComponent(params[i])+'&';
	};r.open('GET',url,true);};
	r.send(data);
};
todo.prototype.setClass=function(e,name,v){if(v!==false)todo.addClass(e,name);else todo.removeClass(e,name);};
todo.prototype.addClass=function(e,name){todo.removeClass(e,name);e.className+=' '+name;};
todo.prototype.removeClass=function(e,name){var c=e.className.split(' '),ar=[];for(var i=0;i<c.length;i++){c[i]=c[i].replace(/^\s+|\s+$/g,'');if(c[i]&&c[i]!=name)ar.push(c[i]);};e.className=ar.join(' ');};
todo.prototype.hasClass=function(e,name){var c=e.className.split(' ');for(var i=0;i<c.length;i++)if(c[i]==name)return true;return false;};
todo.prototype.clientSize=function(){if(document.compatMode=='CSS1Compat'){return {'w':document.documentElement.clientWidth,'h':document.documentElement.clientHeight};}else{return {'w':document.body.clientWidth,'h':document.body.clientHeight}}};
todo.prototype.motion=function(v,start,finish,step){v=parseInt(v);var last=Math.abs(Math.abs(finish)-Math.abs(v)),step=Math.ceil((step+last)/step),res,ready;if(finish>=start){res=v+step;ready=res>=finish;}else{res=v-step;ready=res<=finish};return {'res':ready?finish:res,'ready':ready};};
todo.prototype.css=function(e,n){return (typeof(e.currentStyle)=='undefined'?document.defaultView.getComputedStyle(e,null):e.currentStyle)[s];};
todo.prototype.opacity=function(e,opacity,delay,breakOff,callback){var stopIt=function(e){if(e && e._timer){window.clearInterval(e._timer);e._timer=null;if(e.callback)e.callback.call(e._);}};if(e._opacity && e._opacity._timer){if(breakOff)stopIt(e._opacity);else return;};e._opacity={'_':e,'delay':delay,'step':0.07,'finish':opacity,'callback':callback,'run':function(){if(!this._timer)this._timer=window.setInterval(function(o,stopIt){return function(){var v=o.get()+o.step*o.sign;if((o.sign>0 && v>=o.finish) || (o.sign<0 && v<=o.finish) || v>1 || v<0){o.set(o.finish);stopIt(o);}else o.set(v);}}(this,stopIt),this.delay);},'set':function(v){var p=this.prop();if(p=='filter'){if(v==1)v='';else v='alpha(opacity='+v*100+')';};this._.style[p]=v;},'get':function(){var p=this.prop();if(p=='filter'){var tmp=this._.style.filter.match(/opacity=([0-9]{1,3})/);if(tmp&&tmp[1])return parseInt(tmp[1])/100;return 1;};return this._.style[p]==''?1:parseFloat(this._.style[p]);},'prop':function(){if(typeof document.body.style.opacity=='string')return 'opacity';else if(typeof document.body.style.MozOpacity=='string')return 'MozOpacity';else if(typeof document.body.style.KhtmlOpacity=='string')return 'KhtmlOpacity';else if(document.body.filters&&navigator.appVersion.match(/MSIE ([\d.]+);/)[1]>=5.5)return 'filter';return 'opacity';}};if(!delay){e._opacity.set(opacity);return;};e._opacity.sign=e._opacity.get()>opacity?-1:1;e._opacity.run();};
todo.prototype.setEvent=function(name,e,f){if(typeof(e._actionQueue)=='undefined')e._actionQueue={};if(!e._actionQueue[name]){e._actionQueue[name]=e['on'+name]?[e['on'+name]]:[];e['on'+name]=function(event){var e=event||window.event;for(var i=0;i<this._actionQueue[e.type].length;i++)if(this._actionQueue[e.type][i])this._actionQueue[e.type][i].call(this,event);}};var i=e._actionQueue[name].length;e._actionQueue[name][i]=f;return i;};
todo.prototype.unsetEvent=function(name,e,i){try{delete e._actionQueue[name][i];}catch(er){};};
todo=new todo;