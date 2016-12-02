/* common func */
todo.hc = function(s, c) {return ~(' ' + s + ' ').indexOf(' ' + c + ' ')} /*check string (classes)*/
todo.ct = function(t) {return document.createTextNode(t)} //create text node
todo.append = function(n, e) {e = e || document.body;return e.appendChild(n);} //appendChild
todo.cc = function(o, add, del) { /*cnangeClass*/
	var o = o || {}, n = 'className', cN = (undefined != o[n]) ? o[n] : o, ok = 0;
	if ('string' !== typeof cN) return false;
	var re = new RegExp('(\\s+|^)' + del + '(\\s+|$)', 'g');
	if (add) /*addClass*/
		if (!todo.hc(cN, add)) {cN += ' ' + add;ok++;}
	if (del) /*delClass*/
		if (todo.hc(cN, del)) {cN = cN.replace(re, ' ');ok++;}
	if (!ok) return false
	if ('object' == typeof o) o[n] = cN;
	else return cN;
}
todo.bind = function(func, context /*, args*/) {
	/* bind(func, context, аргументы) 
	 * bind(obj, 'method', аргументы).
	 */
	var args = [].slice.call(arguments, 2);
	if (typeof context == "string") { 
		args.unshift(func[context], context);
		return bind.apply(this, args);
	}  
	function wrapper() {
		var unshiftArgs = args.concat( [].slice.call(arguments) );
		return func.apply(context, unshiftArgs);
	}
	return wrapper;
}
todo.calendar = function(c,settings){
	var d = document,
		options = {
			/*settings*/																	/*Default values*/
			boxClassName	: settings&&settings.boxClassName	? settings.boxClassName		: 'todo-calendar'
			,holidays		: settings&&settings.holidays?settings.holidays					: {}
			,dn				: settings&&settings.days			? settings.days				: ['пн', 'вт', 'ср', 'чт', 'пт', 'сб', 'вс']
			,mm				: settings&&settings.months			? settings.months			: ['янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек']
			,MM				: settings&&settings.monthsFull		? settings.monthsFull		: ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь']
			,nav			: settings&&settings.nav			? settings.nav				: true
			,navPosition	: settings&&settings.navPosition	? settings.navPosition		: 'before'
			,navTextNext	: settings&&settings.navTextNext	? settings.navTextNext		: 'Следующий месяц'
			,navTextPrev	: settings&&settings.navTextPrev	? settings.navTextPrev		: 'Предыдущий месяц'
			,position		: settings&&settings.position		? settings.position			: {vAlign:'bottom',hAlign:'right',vOffset:0,hOffset:0}
			,rel			: settings&&settings.rel || null
			,selectDate		: settings&&settings.selectDate		? settings.selectDate		: true
			,selectPosition	: settings&&settings.selectPos		? settings.selectPos		: 'before'
			,selectAttr		: settings&&settings.selectAttr		? settings.selectAttr		: {'class':'list'}
			,sep			: settings&&settings.sep			? settings.sep				: '.'
			,yearOffsetDown	: settings&&settings.yearOffsetDown	? settings.yearOffsetDown	: 5
			,yearOffsetUp	: settings&&settings.yearOffsetUp	? settings.yearOffsetUp		: 1
			/**constant*/
			,hover			: 'hover'
			,year			: new Date().getFullYear()
			,weekendColor	: 'red'
			,correctDn		: function(d){return (d == 0) ? 6 : d - 1}
			,makeYears		: function(y){var years = [];for(var i = y - this.yearOffsetDown; i < y + this.yearOffsetUp + 1; i ++)years[i] = i;return years;}
			,makeDate		: function(s){s = s.split(/\D+/);return new Date(s[2], s[1] - 1, s[0])}
			,formatDate		: function (d) {
				d = [d.getDate(), d.getMonth() + 1, d.getFullYear()]
				return d.join(this.sep || '.').replace(/\b(\d)\b/g, '0$1')
			}
		};
	
	if(!c) throw new Exception('Calendar element not found');
	else{
		c._calendar = {
			'_':c
			,'box':todo.append(todo.create('div',{'class':options.boxClassName+' mm disnone'}))
			,'rel':todo.get(options.rel) || null
			,'date': function() {var parseDate = c.value.split('.'); return new Date(parseDate[2],parseDate[1]-1,parseDate[0])}()
			,'getEvent':function(d){
				d = d.replace(/\b(\d)\b/g, '0$1');
				return options.holidays[d] || options.holidays[d.substr(5)];
			}
			,'cancel':function(e) {
				e = e || window.event;
				if (27 == e.keyCode) this.hide();
			}
			,'init':function () {
				this.show = todo.bind(this.show, this);
				this.hide = todo.bind(this.hide, this);
				d.onkeydown = todo.bind(this.cancel,this);
				
				c.onclick = this.show;
				c.onfocus = this.show;
				c.onblur = this.hide;
				
				if(this.rel) this.rel.onclick = this.show;
			}
			,'setDate':function (e) {
				e = e || window.event;
				var el = e.target || e.srcElement;
				if (!el.tagName || el.tagName.toLowerCase() != 'div') return;
				var o = c._calendar,d = el.firstChild.nodeValue;
				o.date = new Date(this.year, this.month, d);
				c.value = options.formatDate(o.date);
				o.hide(e);//todo.cc(this, 'disnone');
			}
			,'makeHtmlByDate':function(dd){
				var  html = '<table><col><col><col><col><col><col class="weekend"><col class="weekend"><tr><th>' + options.dn.join('</th><th>') + '</th></tr>'
					,cArr = this.buildCArr(dd.getFullYear())
					,arr = cArr[dd.getMonth()]
					,currDay = (this.date) ? dd.getDate() : ''
					,marr = [], week = new Array(7)
					,flush = function(arr){marr.push('<td>' + arr.join('</td><td>') + '</td>');return new Array(7)};

				for (var i = 1,cN = [], title; i <  arr.length; i++,cN = []) {
					if (arr[i] == undefined) continue;
					title = this.getEvent(dd.getFullYear() + '-' + (dd.getMonth() + 1) + '-' + i) || '';
					if (title) {title = ' title="' + title + '"'; cN.push('metka');}
					if (arr[i] > 4) cN.push(options.weekendColor);
					if (i == currDay) cN.push(options.hover);
					cN = (cN.length) ? ' class="' + cN.join(' ') + '"' : '';
					week[arr[i]] = '<div' + cN +  title + '>' + i + '</div>';
					if ((arr[i] === 6) && (i != arr.length-1)) week = flush(week);
				}
				flush(week);

				html += '<tr>' + marr.join('</tr><tr>') + '</tr></table>';
				return html;
			}
			,'getOffsetTopLeft': function(el) {
				var top = 0, left = 0;
				while(el) {
					top = top + parseInt(el.offsetTop)
					left = left + parseInt(el.offsetLeft)
					el = el.offsetParent
				}
				return {top:top, left:left}
			}
			,'hide':function (e, o) {
				o = o || c._calendar;
				c.onfocus = o.show;
				if (o && !todo.hc(o.box.className, 'disnone'))
					setTimeout(function(){todo.cc(o.box, 'disnone')}, 100);
			}
			,'show': function(e, dd){
				e = e || window.event
				var o = this._calendar || this, oInput = e && (e.target || e.srcElement),offset = o.getOffsetTopLeft(c);
				dd = dd || o.date || new Date();
				c.onfocus = '';

				if (this.timer){clearTimeout(o.timer);o.timer = null}
				if (e && e.type == 'focus') return (o.timer = setTimeout(function(){o.show(null, dd)}, 400));
				if (e && e.type == 'click' && oInput == c && !todo.hc(o.box.className, 'disnone')) return setTimeout(function(){todo.cc(o.box, 'disnone')}, 400); //this.hide();
				o.box.innerHTML = o.makeHtmlByDate(dd);
				
				if(options.selectDate){
					var  p = todo.create('p')
						,next = todo.create('a',{href:'javascript:void(0);',title:options.navTextNext,'class':'next'})
						,prev = todo.create('a',{href:'javascript:void(0);',title:options.navTextPrev,'class':'prev'})
						,curM = todo.create('span',{},options.MM[dd.getMonth()])
						,curY = todo.create('span',{},dd.getFullYear())
						,months = o.buildField('ul', options.selectAttr, options.MM,'li','getMonth')
						,years = o.buildField('ul', options.selectAttr, options.makeYears(options.year),'li','getFullYear');
					p.appendChild(curM).appendChild(months).parentNode.parentNode.appendChild(curY).appendChild(years).parentNode.parentNode.appendChild(next).parentNode.appendChild(prev);
					o.box.insertBefore(p,(options.selectPosition == 'after' ? null : o.box.firstChild));
					
					years.parentNode.onclick=
					months.parentNode.onclick=function(){
						var v=this.getElementsByTagName('ul')[0],f=v.style.display=='block';
						todo.loop(this.parentNode.getElementsByTagName('ul'),function(){
							this.style.display='none';
						});
						v.style.display=f?'none':'block';
					};
					years.selectedIndex =  options.yearOffsetDown - options.year + dd.getFullYear();
					years.onchange = function(){o.show(null, new Date(this.value, dd.getMonth(), 1));}
					years.onclick = function(e){e = e || window.event; e.target = e.target || e.srcElement; o.show(null, new Date(e.target.value, dd.getMonth(), 1));}
					months.selectedIndex = dd.getMonth();
					months.onchange = function(){o.show(null, new Date(dd.getFullYear(), this.value, 1));}
					months.onclick = function(e){e = e || window.event; e.target = e.target || e.srcElement; o.show(null, new Date(dd.getFullYear(), e.target.value, 1));}
					
					next.onclick = function(){
						var lastMonth = dd.getMonth()==11;
						var lastYear = dd.getFullYear()==options.year+options.yearOffsetUp;
						o.show(null, new Date(lastMonth && !lastYear ? dd.getFullYear()+1 : dd.getFullYear(),lastMonth?(lastYear?dd.getMonth():0):dd.getMonth()+1, 1))
					}
					prev.onclick = function(){
						var firstMonth = dd.getMonth()==0;
						var firstYear = dd.getFullYear()==options.year-options.yearOffsetDown;
						o.show(null, new Date(firstMonth && ! firstYear? dd.getFullYear()-1 : dd.getFullYear(),firstMonth? (firstYear?dd.getMonth():11):dd.getMonth()-1, 1))
					}
				}else if(options.nav){
					var  p = todo.create('p')
						,next = todo.create('a',{href:'#',title:options.navTextNext,'class':'next'})
						,prev = todo.create('a',{href:'#',title:options.navTextPrev,'class':'prev'});
					p.appendChild(next).parentNode.appendChild(prev);
					todo.append(todo.ct(options.MM[dd.getMonth()] + ' ' + dd.getFullYear()), p);
					o.box.insertBefore(p,(options.navPosition == 'after' ? null : o.box.firstChild));
					
					next.onclick = function(){
						var lastMonth = dd.getMonth()==11;
						var lastYear = dd.getFullYear()==options.year+options.yearOffsetUp;
						o.show(null, new Date(lastMonth && !lastYear ? dd.getFullYear()+1 : dd.getFullYear(),lastMonth?(lastYear?dd.getMonth():0):dd.getMonth()+1, 1))
					}
					prev.onclick = function(){
						var firstMonth = dd.getMonth()==0;
						var firstYear = dd.getFullYear()==options.year-options.yearOffsetDown;
						o.show(null, new Date(firstMonth && ! firstYear? dd.getFullYear()-1 : dd.getFullYear(),firstMonth? (firstYear?dd.getMonth():11):dd.getMonth()-1, 1))
					}
				}
				if (todo.hc(o.box.className, 'disnone')){
					todo.cc(o.box, null, 'disnone');
					var info = {
						wInput	: c.offsetWidth,
						hInput	: c.offsetHeight,
						wBox	: o.box.offsetWidth,
						hBox	: o.box.offsetHeight,
						offset	: offset,
						position: options.position,
						left	: offset.left,
						top		: offset.top
					};
					switch(info.position.vAlign){
						case 'top': info.top = info.offset.top - info.hBox; break;
						case 'middle': info.top = info.offset.top - parseInt(info.hBox/2) + parseInt(info.hInput/2); break;
						case 'bottom':
						default: info.top = info.offset.top + info.hInput;
					}
					switch(info.position.hAlign){
						case 'left': info.left = info.offset.left - info.wBox; break;
						case 'center': info.left = info.offset.left - parseInt(info.wBox/2) + parseInt(info.wInput/2); break;
						case 'right':
						default: info.left = info.offset.left + info.wInput;
					}
					o.box.style.left = info.left + info.position.hOffset + 'px';
					o.box.style.top = info.top + info.position.vOffset + 'px';
				} 
				o.box.month = dd.getMonth();
				o.box.year = dd.getFullYear();
				o.box.onmouseover = function(){c.onblur = '';}
				o.box.onmouseout = function(){c.onblur = o.hide;}
				o.box.onclick = o.setDate;
				c.focus();
			}
			,'buildCArr':function (y) { /*array monthes by year*/
				var date = new Date(y, 0, 1), c = [];
				for (var i = 0; i < 12; i ++) c[i] = [];
				while (date.getFullYear() == y) {
					c[date.getMonth()][date.getDate()] = options.correctDn(date.getDay());
					date.setDate(date.getDate() + 1);
				}
				return c;
			}
			,'buildField': function (el, params, idxs, elc,funcName) {
				var el = todo.create(el,params);
				if (idxs && elc) {
					var values;
					for (var id in idxs) {
						values = {value:id};
						if(this.date[funcName]() == id) values['className'] = 'current';
						todo.append(this.buildFieldSub(elc, values, idxs[id]), el);
					}
				}
				return el;
			}
			,'buildFieldSub': function (el, params, txt) {/** элемент с вложенным textNode */
				var el = todo.create(el);
				for (var id in params) el[id] = params[id];
				if (txt != undefined) todo.append(todo.ct(txt), el);
				return el;
			}
		}
		c._calendar.init();
	}
}