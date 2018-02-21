<?
/**
* Проверяем авторизацию
*/
if(!session_id() && !headers_sent()) session_start();
if(!isset($_SESSION['apUser'])) die;

setlocale(LC_ALL,'ru_RU.UTF8');

/**
* Класс выдает список файлов и папок в папке заданной параметром $_REQUEST['dir']
*/
class userfiles implements Iterator{
	private $ar = array();
	function __construct(){
		$userFolders = array('image','file','media');
		$_REQUEST['type'] = isset($_REQUEST['type']) && in_array($_REQUEST['type'],$userFolders) ? $_REQUEST['type'] : 'file';
		$this->rootdir = '../userfiles/'.$_REQUEST['type'].'/';
		if(!is_dir($this->rootdir)) mkdir($this->rootdir,0755);
		//$this->rootdir = '../userfiles/image/';
		$path = $this->isValidDir(@$_REQUEST['dir'] ? $_REQUEST['dir'] : $this->rootdir) ? $_REQUEST['dir'] : $this->rootdir;
		
		$arPath = explode('/',$this->rootdir);
		array_pop($arPath);
		$tmp = explode('/',substr($path,strlen($this->rootdir)));
		foreach($tmp as $folder){
			if($folder=='..') array_pop($arPath);
			elseif($folder) array_push($arPath,$folder);
		}
		$this->dir = dir(implode('/',$arPath).'/');
		
		$this->init();
	}
	function __destruct(){
		$this->dir->close();
	}
	/* Проверяет является ли путь подпапкой заданной корневой директории */
	function isValidDir($dir){
		return $dir && strpos($dir,$this->rootdir)===0 && substr_count(substr($dir,strlen($this->rootdir)),'..')<=substr_count($this->rootdir,'..') && is_dir($dir);
	}
	/* Создает массив файлов и папок, папки выносит на первое место */
	function init(){
		$dirs = array();
		$files = array();
		while(false !== ($name = $this->dir->read())){
			if(is_dir($this->dir->path.$name)){
				if($name!='.' && !($name=='..' && $this->dir->path==$this->rootdir))
					$dirs[] = $name;
			}else $files[] = $name;
		}
		$this->ar = array_merge($dirs,$files);
	}
	/* Возвращает текущую папку */
	function getPath(){
		return $this->dir->path;
	}
	/* Возвращает текущую папку относительно корня */
	function getAbsPath(){
		if(substr($this->getPath(),0,1)=='/')
			return $this->getPath();
		$path = explode('/',pathinfo($_SERVER['PHP_SELF'],PATHINFO_DIRNAME));
		$tmp = explode('/',$this->getPath());
		foreach($tmp as $f){
			if($f=='..') array_pop($path);
			elseif($f=='.') continue;
			else array_push($path,$f);
		}
		return implode('/',$path);
	}
	/* Возвращает путь относительно заданной корневой папки */
	function getManagerPath(){
		return substr($this->dir->path,strlen($this->rootdir));
	}
	/* Реализуем интерфейс Iterator */
	function rewind(){return $this->next(reset($this->ar));}
	function current(){return $this->cur;}
	function key(){return key($this->ar);}
	function next($name = null){ /* Выдаем массив со всей нужной инфой о файле/папке */
		$name = $name ? $name : next($this->ar);
		if($name){
			$this->cur = pathinfo($path = $this->dir->path.$name);
			$this->cur['name'] = $name;
			$this->cur['is_dir'] = is_dir($path);
			$this->cur['path'] = $path.($this->cur['is_dir'] ? '/' : null);
			if(!$this->cur['is_dir']){
				$this->cur['time'] = date('d.m.Y H:i',filemtime($path));
				$k = 1024;
				$tmp = $this->cur['size'] = filesize($path);
				if($tmp >= $k){
					$tmp = $tmp/$k;
					if($tmp >= $k){
						$tmp = $tmp/$k;
						$this->cur['size_format'] = number_format($tmp,$tmp<10 ? 1 : 0,',',' ').' Mb';
					}else $this->cur['size_format'] = number_format($tmp,0,',',' ').' Kb';
				}else $this->cur['size_format'] = number_format($tmp,0,',',' ').' b';
			}

		}else $this->cur = null;
		return $this->cur;
	}
	function valid(){return (bool) $this->current();}
}

$dir = new userfiles;


/**
* Обработка действий
*/
if(isset($_REQUEST['action'])){
	
	// list of valid extensions, ex. array("jpeg", "xml", "bmp")
	$allowedExtensions = array('jpeg','jpg','gif','png','pdf','txt','rtf','odt','doc','docx','ods','xls','xlsx','zip','rar','swf','flv','xml');
	
	switch($_REQUEST['action']){
		case 'upload':
			if($dir->isValidDir(@$_REQUEST['dir'])){
				// max file size in bytes
				$sizeLimit = 8 * 1024 * 1024;
				
				require 'lib/uploader.php';
				$uploader = new qqFileUploader($allowedExtensions,$sizeLimit);
				$result = $uploader->handleUpload($dir->getPath());
				die(htmlspecialchars(json_encode($result),ENT_NOQUOTES));
			}
			die("{'error':'wrong dir ".$_REQUEST['dir']."'}");
			
		case 'create_folder':
			if(preg_match('/^[a-zA-Z0-9_ \-\!\.\,]+$/',$folderName = trim($_REQUEST['new_name']))){
				mkdir($dir->getPath().$folderName,0755);
			}
			break;
		
		case 'delete':
			function deleteDirectory($dir){
				if(!file_exists($dir)) return true;
				if(!is_dir($dir) || is_link($dir)) return unlink($dir);
				foreach(scandir($dir) as $item){
					if($item == '.' || $item == '..') continue;
					$path = $dir.'/'.$item;
					if(!deleteDirectory($path)){
						chmod($path, 0777);
						if(!deleteDirectory($path)) return false;
					}
				}
				return rmdir($dir);
			}
			$ar = array();
			if(isset($_REQUEST['dirs']) && is_array($_REQUEST['dirs']))$ar = array_merge($ar,$_REQUEST['dirs']);
			if(isset($_REQUEST['files']) && is_array($_REQUEST['files'])) $ar = array_merge($ar,$_REQUEST['files']);
			foreach($ar as $name) deleteDirectory($dir->getPath().$name);
			break;
		
		case 'rename':
			if(preg_match('/^[a-zA-Z0-9_ \-\!\.\,]+$/',$newName = trim($_REQUEST['new_name']))
				&& preg_match('/.+\.([^\.]+)$/',$newName,$m)
				&& in_array(strtolower($m[1]),$allowedExtensions)
			){
				$ar = array();
				if(isset($_REQUEST['dirs']) && is_array($_REQUEST['dirs'])) $ar = array_merge($ar,$_REQUEST['dirs']);
				if(isset($_REQUEST['files']) && is_array($_REQUEST['files'])) $ar = array_merge($ar,$_REQUEST['files']);
				if($name = reset($ar)){
					rename($dir->getPath().$name,$dir->getPath().$newName);
				}
			}
			break;
	}
	header('Location: '.$_SERVER['PHP_SELF'].'?dir='.urlencode($dir->getPath()).'&type='.$_REQUEST['type']);
	die;
}

?><!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8">
<link href="css/uploader.css" rel="stylesheet" type="text/css">
<!--[if lt IE 8]>
<style type="text/css">
input[type='button'],
input[type='submit'],
input[type='reset']{padding:3px 0 !important;}
input[type='button'].add,
input[type='submit'].add,
input[type='button'].del,
input[type='submit'].del,
input[type='button'].cancel,
input[type='submit'].cancel{
padding-left:16px !important;
}
.qq-upload-button{display:inline;}
#uploader{float:left;}
</style>
<![endif]-->
<script src="../tinymce/tiny_mce_popup.js" type="text/javascript"></script>
<script src="js/fileuploader.js" type="text/javascript"></script>
<script src="js/todo.js" type="text/javascript"></script>
<script type="text/javascript">
/* Отдает путь к выбранному файлу в TinyMCE */
function selectFile(name){
	/*var url='<?=$dir->getAbsPath()?>'+name,win=tinyMCEPopup.getWindowArg("window"),callback=tinyMCEPopup.getWindowArg("callback");
	if(win){
		win.document.getElementById(tinyMCEPopup.getWindowArg("input")).value=url;
		if(typeof(win.ImageDialog) != "undefined"){
			if(win.ImageDialog.getImageData)win.ImageDialog.getImageData();
			if(win.ImageDialog.showPreviewImage)win.ImageDialog.showPreviewImage(url);
		};
	}*/
	var callback=tinyMCEPopup.getWindowArg("callback"),
		url='<?=$dir->getAbsPath()?>'+name;
	if(typeof callback == 'function'){
		callback(url);
	}else{
		var win=tinyMCEPopup.getWindowArg("window");
		if(win){
			win.document.getElementById(tinyMCEPopup.getWindowArg("input")).value=url;
			if(typeof(win.ImageDialog)!="undefined"){
				if(win.ImageDialog.getImageData)win.ImageDialog.getImageData();
				if(win.ImageDialog.showPreviewImage)win.ImageDialog.showPreviewImage(url);
			};
		}
	}
	
	tinyMCEPopup.close();
};
todo.onload(function(){
	
/* Загрузчик файлов */
	var uploader=new qq.FileUploader({
		element:todo.get('uploader'),
		action:'<?=$_SERVER['PHP_SELF']?>?action=upload&type=<?=$_REQUEST['type']?>&dir=<?=urlencode($dir->getPath())?>',
		debug:true,
		template:'<div class="qq-uploader"><div class="qq-upload-drop-area"><span>Перетащите сюда файлы для загрузки</span></div><div class="qq-upload-button">Загрузить файл(ы)</div><ul class="qq-upload-list"></ul></div>',
		fileTemplate:'<li><span class="qq-upload-file"></span><span class="qq-upload-spinner"></span><span class="qq-upload-size"></span><a class="qq-upload-cancel" href="#">Отмена</a><span class="qq-upload-failed-text">Не загружен</span></li>',
		callback:function(id,fileName,responseJSON){
			if(responseJSON.success&&!this._e._filesInProgress)window.location.reload();
		}
	});
	
	var form=todo.get('files');

/* Перенаправление кликов по строке таблицы в ссылку на имени файла/папки */
	todo.loop(form.getElementsByTagName('a'),function(){
		if(this.parentNode.className=='name'){
			this.parentNode.parentNode.onclick=function(a){return function(e){
				a.click();
				todo.stopPropagation(e);
			}}(this);
		}
	});
	
/* Функция для выделения строки таблицы */
	todo.loop(form.getElementsByTagName('tr'),function(){
		this._selected=function(v){
			todo.setClass(this,'selected',v);
		}
	});

/* Клик на чекбоксе при выделения файла/папки */
	todo.loop(form.getElementsByTagName('input'),function(){
		if(this.type=='checkbox' && this.className=='select_row'){
			this.onclick=function(e){
				todo.stopPropagation(e);
				this.parentNode.parentNode._selected(this.checked);
				todo.get('selectAll')._checkSelection();
				this.form._checkButtons();
			};
			this._checkSelection=function(){this.parentNode.parentNode._selected(this.checked);};
		}
	});

/* Чекбокс выделить все */
	var sa=todo.get('selectAll');
	sa._checkSelection=function(){
		this.checked=true;
		var el=this.parentNode.offsetParent.getElementsByTagName('input');
		for(var i=0;i<el.length;i++)if(el[i].className=='select_row'&&!el[i].checked){
			this.checked=false;
			return;
		};
	};
	sa.onclick=function(e){try{
		todo.stopPropagation(e);
		var el=this.parentNode.offsetParent.getElementsByTagName('input');
		for(var i=0;i<el.length;i++)if(el[i].className=='select_row'){
			el[i].checked=this.checked;
			el[i]._checkSelection();
		}
	}catch(er){alert(er)};this.form._checkButtons();};
	sa._checkSelection();

/* Кнопка создать папку */
	todo.get('button_create_folder').onclick=function(){
		var folderName=window.prompt('Введите имя папки\n(имя может содержать латинские символы и цифры)','folder').replace(/(^\s+)|(\s+$)/g,'');
		if(folderName.match(/^[a-zA-Z0-9_ \-\!\.\,]+$/)){
			this.form.action.value='create_folder';
			this.form.new_name.value=folderName;
			this.form.submit();
		}else alert('Имя папки задано неверно');
	};
	
/* Возвращает массив выделенных файлов/папок */
	form._getSelectedValues=function(){
		var ar=[],
			d=this['dirs[]'],
			f=this['files[]'];
		if(d){
			if(d.length)todo.loop(d,function(){if(this.checked)ar.push(this.value);});
			else if(d.checked)ar.push(d.value);
		};
		if(f){
			if(f.length)todo.loop(f,function(){if(this.checked)ar.push(this.value);});
			else if(f.checked)ar.push(f.value);
		};
		return ar;
	};
	form._checkButtons=function(){
		var v=this._getSelectedValues();
		todo.get('button_delete').disabled=v.length==0
		todo.get('button_rename').disabled=!(v.length==1);
	};

/* Кнопка удалить */
	todo.get('button_delete').onclick=function(){
		if(this.form._getSelectedValues().length){
			if(confirm('Подтвердите удаление.')){
				this.form.action.value='delete';
				this.form.submit();
			}
		}else alert('Ничего не выбрано');
	};
	
/* Кнопка переименовать */
	todo.get('button_rename').onclick=function(){
		var v=this.form._getSelectedValues();
		if(v.length==1){
			var newName=window.prompt('Введите новое имя\n(имя может содержать латинские символы и цифры)',v[0]);
			if(newName){
				newName=newName.replace(/(^\s+)|(\s+$)/g,'');
				if(newName.match(/^[a-zA-Z0-9_ \-\!\.\,]+$/)){
					if(v[0]!=newName){
						this.form.new_name.value=newName;
						this.form.action.value='rename';
						this.form.submit();
					};
				}else alert('Имя задано неверно');
			};
		}else if(v.length>1)alert('Выберите один элемент');
		else alert('Ничего не выбрано');
	};
	
/* Кнопка отмена */
	todo.get('button_cancel').onclick=function(){
		tinyMCEPopup.close();
	};
});
</script>
</head>

<body>
<form action="<?=$_SERVER['PHP_SELF']?>" id="files" method="post">
	<input type="hidden" name="dir" value="<?=$dir->getPath()?>">
	<input type="hidden" name="type" value="<?=$_REQUEST['type']?>">
	<input type="hidden" name="action" value="">
	<input type="hidden" name="new_name" value="">
	<div id="path">Путь: <strong>/<?=$_REQUEST['type']?>/<?=$dir->getManagerPath()?></strong></div>
	<div id="fileList">
		<table>
			<col class="name">
			<col class="date">
			<col class="size">
			<col class="checkbox">
			<tr>
				<th scope="col" style="padding-left:25px;">Имя</th>
				<th scope="col">Дата</th>
				<th scope="col">Размер</th>
				<th scope="col"><input id="selectAll" type="checkbox"></th>
			</tr>
<?
$arFiles = array();
$arDirs = array();
foreach($dir as $i)
	if($i['is_dir'])
		$arDirs[$i['name']] = $i;
	else
		$arFiles[$i['name']] = $i;
ksort($arDirs);
ksort($arFiles);

foreach($arDirs as $i){
	?><tr class="dir"><td class="name"><a href="<?=$_SERVER['PHP_SELF']?>?dir=<?=urlencode($i['path'])?>&amp;type=<?=$_REQUEST['type']?>"><?=$i['name']?></a></td><td></td><td class="size">Папка</td><td><? if($i['name']!='..'): ?><input type="checkbox" class="select_row" name="dirs[]" value="<?=$i['name']?>"><? endif ?></td></tr><?
}
foreach($arFiles as $i){
	?><tr class="<?=$i['extension']?>"><td class="name"><a href="javascript:selectFile('<?=$i['name']?>')"><?=$i['name']?></a></td><td class="time"><?=$i['time']?></td><td class="size"><?=$i['size_format']?></td><td><input type="checkbox" class="select_row" name="files[]" value="<?=$i['name']?>"></td></tr><?
}

?>
		</table>
	</div>
	<div id="log">
		<div class="buttons">
			<input id="button_create_folder" type="button" value="Создать папку" class="add">
			<input id="button_rename" type="button" value="Переименовать" class="disabled" disabled>
			<input id="button_delete" type="button" value="Удалить" class="del disabled" disabled>
			<input id="button_cancel" type="button" value="Отмена" class="cancel">
		</div>
		<div id="uploader"></div>
	</div>
</form>
</body>
</html>