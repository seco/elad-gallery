<?php
/*
elad-gallery is a free, open sourced, lightweight and fast gallery that utilizes PHP, CSS3 and HTML5.
	Copyright (C) 2010-2011  Elad Alfassa <elad@fedoraproject.org>

	This file is part of elad-gallery.

	elad-gallery is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	elad-gallery is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with elad-gallery. If not, see <http://www.gnu.org/licenses/>.
*/

//Uncomment the array_combine function if you are using php4
/*function array_combine($arr1, $arr2) { 
    $out = array();
   
    $arr1 = array_values($arr1);
    $arr2 = array_values($arr2);	
   
    foreach($arr1 as $key1 => $value1) {
        $out[(string)$value1] = $arr2[$key1];
    }
   
    return $out;
}*/
require_once("settings.php");

//Detect preferd langauge (TODO: add a way to override browser setting)
function detect_lang() {
	$langs = array();

	if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
	    // break up string into pieces (languages and q factors)
	    preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_parse);

	    if (count($lang_parse[1])) {
		// create a list like "en" => 0.8
		$langs = array_combine($lang_parse[1], $lang_parse[4]);
	    	
		// set default to 1 for any without q factor
		foreach ($langs as $lang => $val) {
		    if ($val === '') $langs[$lang] = 1;
		}

		// sort list based on value	
		arsort($langs, SORT_NUMERIC);
	    }
	}
	$filearray=array();
	$i=0;
	if ($handle = opendir('locale/')) {
		while (false !== ($file = readdir($handle))) {
			if (!is_dir($file) && preg_match("/(.*?).php/i", $file))
				$filearray[$i]=$file;
				$i++;
        	}
		closedir($handle);
	}
	// look through sorted list and use first one that matches our languages
	foreach ($langs as $lang => $val) {
		if (in_array($lang.".php", $filearray) || $lang=='en') {
			return $lang;
		}
	}
	return "en";

}

define('LANG', detect_lang());

//Return translated string
function trans($what) {
	$location = 'locale/' . LANG . '.php';
	if(file_exists($location))
	{
		include $location;
	}
	if (isset($lang[$what])) {
		return $lang[$what];
	} else {
		return $what;
	}
}
function isBuggyIe() {
    $ua = $_SERVER['HTTP_USER_AGENT'];
    // quick escape for non-IEs
    if (0 !== strpos($ua, 'Mozilla/4.0 (compatible; MSIE ')
        || false !== strpos($ua, 'Opera')) {
        return false;
    }
    // no regex = faaast
    $version = (float)substr($ua, 30);
    return (
        $version < 6
        || ($version == 6  && false === strpos($ua, 'SV1'))
    );
}
isBuggyIe() || ob_start("ob_gzhandler");
ini_set('memory_limit', '64M');
header('Content-Type: text/html; charset=utf-8');  
function gzip_page() {
	@ob_end_flush();
}
$pathinfo=pathinfo($_SERVER['SCRIPT_NAME']);
$url=SCRIPT_DIR_URL;
$basename=$pathinfo['basename'];
if (IS_DIR_INDEX)
	$full_url=$url;
else
	$full_url=$url.$basename;
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KiB', 'MiB', 'GiB', 'TiB');
  
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
  
    $bytes /= pow(1024, $pow);
  
    return round($bytes, $precision) . ' ' . $units[$pow];
} 
if (isset($_GET['thumb']) && strpos($_GET['thumb'],'..')===false) {
	$path=str_replace(SCRIPT_DIR_URL, '', $_GET['thumb']);
	$pathinfo=pathinfo($path);
	$dir=dirname($path);
	$basename=$pathinfo['basename'];
	$md5=md5_file($path);
	$thumbdir="thumbs";
	$percent = 0.2;
	if (isset($_GET['scale'])) {
		switch($_GET['scale']) {
			case "medium":
				$thumbdir="thumbs-med";
				$percent=0.5;
			break;
			case "high":
				$thumbdir="thumbs-high";
				$percent=0.8;
			break;
		}
	}
	$thumbfile="$dir/.$thumbdir/$basename@md5=$md5";
	$fs = stat($path);
	$etag=sprintf('"thumb%x-%x-%s"', $fs['ino'], $fs['size'],base_convert(str_pad($fs['mtime'],16,"0"),10,16));
	$headers = apache_request_headers();
	header("Etag: $etag");
	if (preg_match("/(.*?).jpg/i", $path)) {
		header('Content-type: image/jpeg');
		$type="jpeg";
	}
	elseif (preg_match("/(.*?).png/i", $path))
	{
		header('Content-type: image/png');
		$type="png";
	}
	if (!file_exists($dir."/.$thumbdir")) {
		mkdir($dir."/.$thumbdir");
	}
	if (file_exists($thumbfile)) {
		$DoIDsMatch = (isset($headers['If-None-Match']) && $headers['If-None-Match']==$etag);
		if ($DoIDsMatch){
    		header('HTTP/1.1 304 Not Modified');
    		header('Connection: close');
			ob_end_clean();
			exit;
		} else {
			readfile($thumbfile);
		}
	} else {
		list($width, $height) = getimagesize($path);
		$newwidth = $width * $percent;
		$newheight = $height * $percent;
		$thumb = imagecreatetruecolor($newwidth, $newheight);
		imageinterlace($thumb, 1); //Progressive JPEG loads faster
		imageantialias($thumb, true); //Antialiasing
		if ($type=='jpeg')
			$source = imagecreatefromjpeg($path);
		elseif ($type=='png')
			$source = imagecreatefrompng($path);
		imagecopyresized($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
		imagedestroy($source);
		if ($type=='jpeg')
			imagejpeg($thumb,$thumbfile);
		elseif ($type=='png')
		{
			imagepng($thumb,$thumbfile);	
		}
		imagedestroy($thumb);
		$DoIDsMatch = (isset($headers['If-None-Match']) && $headers['If-None-Match']==$etag);
		if ($DoIDsMatch){
	    	header('HTTP/1.1 304 Not Modified');
	    	header('Connection: close');
			ob_end_clean();
			exit;
		} else {
			readfile($thumbfile);
		}
	}
	exit;
} elseif (isset($_GET['exif']) && strpos($_GET['exif'],'..')===false) {
	//ob_start();
	$path=str_replace(SCRIPT_DIR_URL, '', $_GET['exif']);
	$fs = stat($path);
	if (preg_match("/(.*?).jpg/i", $path))
		$exif=@exif_read_data($path);
	else
		$exif=false;
	$size=formatBytes(filesize($path));
	$pathinfo=pathinfo($path);
	$filename=$pathinfo['basename'];
	echo("<tr style='display:none'><td>" . filesize($path) ."</td></tr>");
	echo("<tr><td>" . trans("File name:") ."</td><td>" . $filename ."</td></tr>");
	echo("<tr><td>" . trans("File size:") . "</td><td>" . $size ."</td></tr>");
	if ($exif) {
		if (isset($exif['DateTimeOriginal']))
			echo("<tr><td>". trans("Creation date:") ."</td><td>" . $exif['DateTimeOriginal'] ."</td></tr>");
		if (isset($exif['Make']))
			echo("<tr><td>". trans("Camera maker:") . "</td><td>".$exif['Make']."</td></tr>");
		if (isset($exif['Model']))	
			echo("<tr><td>". trans("Camera model:")."</td><td>".$exif['Model']."</td></tr>");
		if (isset($exif['RelatedImageWidth'])) {
			echo("<tr><td>". trans("Dimensions:") ."</td><td>".$exif['RelatedImageWidth']."x".$exif['RelatedImageHeight']."</td></tr>");
		} else {
			list($width, $height) = getimagesize($path);
			echo("<tr><td>". trans("Dimensions:") ."</td><td>". $width."x".$height ."</td></tr>");		
		}
		if (isset($exif['ExposureTime'])) {
			$ExposureArray=explode("/", $exif['ExposureTime']);
			$Exposure=$ExposureArray[0]/$ExposureArray[1];
			echo("<tr><td>". trans("Exposure time:") ."</td><td>". $Exposure .trans("seconds") ."</td></tr>");
		}
		if (isset($exif['FNumber'])) {
			$FArray=explode("/", $exif['FNumber']);
			$f=$FArray[0]/$FArray[1];
			echo("<tr><td>". trans("F number:") ."</td><td>". $f ."</td></tr>");
		}
	} else if (!preg_match("/(.*?).ogv/i", $path) && !preg_match("/(.*?).webm/i", $path) && !preg_match("/(.*?).oga/i", $path)) {
		list($width, $height) = getimagesize($path);
		echo("<tr><td>". trans("Dimensions:") ."</td><td>". $width."x".$height ."</td></tr>");
	}
	$etag="info".md5(ob_get_contents());
	header("Etag: $etag");
	$headers = apache_request_headers();
	$DoIDsMatch = (isset($headers['If-None-Match']) && $headers['If-None-Match']==$etag);
	if ($DoIDsMatch){
    	header('HTTP/1.1 304 Not Modified');
    	header('Connection: close');
		ob_end_clean();
		exit;
	} else {
		gzip_page();
	}
	exit;
} elseif (isset($_GET['ajaxDir']) && strpos($_GET['ajaxDir'],'..')===false) {
	header("HTTP/1.1 200 OK");
	header("Status: 200 OK");
	header('Content-Type: text/html; charset=utf-8'); 
	//ob_start();
	scan($_GET['ajaxDir']);	
	$etag="galleryAjax".md5(ob_get_contents());
	header("Etag: $etag");
	$headers = apache_request_headers();
	$DoIDsMatch = (isset($headers['If-None-Match']) && $headers['If-None-Match']==$etag);
	if ($DoIDsMatch){
    	header('HTTP/1.1 304 Not Modified');
    	header('Connection: close');
		ob_end_clean();
		exit;
	} else {
		gzip_page();
	}
	exit;
} elseif (isset($_GET['exifThumb']) && strpos($_GET['exifThumb'],'..')===false) {
	header("HTTP/1.1 200 OK");
	header("Status: 200 OK");
	header('Content-type: image/jpeg');
	//ob_start();
	echo(exif_thumbnail($_GET['exifThumb']));
	$etag="galleryAjax".md5(ob_get_contents());
	header("Etag: $etag");
	$headers = apache_request_headers();
	$DoIDsMatch = (isset($headers['If-None-Match']) && $headers['If-None-Match']==$etag);
	if ($DoIDsMatch){
    	header('HTTP/1.1 304 Not Modified');
    	header('Connection: close');
		ob_end_clean();
		exit;
	} else {
		gzip_page();
	}
	exit;
} elseif (!isset($_GET['dir'])) { 
	if ("http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']!=$full_url) {
		header("Location: $full_url");
		die("redirecting");
	}
}
function scan($dir) {
	$pathinfo=pathinfo($_SERVER['SCRIPT_NAME']);
	$url=SCRIPT_DIR_URL;
	$basename=$pathinfo['basename'];
	$full_url=$url.$basename;
	if (isset($_GET['dir']) && $_GET['dir']!='.') { 
		$parent=dirname($dir);
		echo("<a href='$url$basename?dir=$parent'><div class='folder'><span>../<span></div></a><br>");	
	}
	if (isset($_GET['ajaxDir']) && $_GET['ajaxDir']!='.') {
		$parent=dirname($dir);
		echo("<a href='$url$basename' onclick=\"return changeHash('dir', '$parent', false)\"><div class='folder'><span>../<span></div></a><br>");	
	}
	echo("directory: $dir<br>");
	$filearray=array();
	$i=0;
	if ($handle = opendir($dir)) {
		while (false !== ($file = readdir($handle))) {
			$i++;
			$filearray[$i]=$file;
        	}
		closedir($handle);
		sort($filearray);
		$y=0;
		foreach($filearray as $file) {
			if (is_dir($dir.'/'.$file) && $file!='.' && $file!='..' && substr($file,0,1)!='.' && $file!="locale") {
				$url1=$_SERVER['REQUEST_URI'];
				echo("<a href='$url$basename?dir=$dir/$file' onclick=\"return changeHash('dir', '$dir/$file', false)\"><div class='folder'><span>$file<span></div></a>");
			} 
			elseif ($file!='.' && $file!='..' && (preg_match("/(.*?).jpg/i", $file) || preg_match("/(.*?).png/i", $file) || preg_match("/(.*?).ogv/i", $file) || preg_match("/(.*?).webm/i", $file) || preg_match("/(.*?).oga/i", $file))) {
				if (preg_match("/(.*?).jpg/i", $file)) {
					$base64=base64_encode(exif_thumbnail($dir.'/'.$file)); //FIXME: some jpeg images has no exif thumbnail.
					if ($dir=='.') 
						echo("<div class='image' id='$y' onclick='ShowInfo(this, event);'><a href='$url$file'><img src='$url$basename?exifThumb=$file' /></a></div>");
					else 
						echo("<div class='image' id='$y' onclick='ShowInfo(this, event);'><a href='$url$dir/$file'><img src='$url$basename?exifThumb=$dir/$file' /></a></div>");
				} elseif (preg_match("/(.*?).png/i", $file)) {
					if ($dir=='.') 
						echo("<div class='image' id='$y' onclick='ShowInfo(this, event);'><a href='$url$file'><img src='$full_url?thumb=$url$file' /></a></div>");
					else 
						echo("<div class='image' id='$y' onclick='ShowInfo(this, event);'><a href='$url$dir/$file'><img src='$full_url?thumb=$url$dir/$file' /></a></div>");
				} elseif (preg_match("/(.*?).webm/i", $file)) {
					if ($dir=='.') 
						echo("<div class='image vid' id='$y' onclick='ShowInfo(this, event);'><a href='$url$file'><img src='$url/.icons/video-webm.svg' /></a></div>");
					else 
						echo("<div class='image vid' id='$y' onclick='ShowInfo(this, event);'><a href='$url$dir/$file'><img src='$url/.icons/video-webm.svg' /></a></div>");
				} elseif (preg_match("/(.*?).ogv/i", $file)) {
					if ($dir=='.') 
						echo("<div class='image vid' id='$y' onclick='ShowInfo(this, event);'><a href='$url$file'><img src='$url/.icons/video-ogv.svg' /></a></div>");
					else 
						echo("<div class='image vid' id='$y' onclick='ShowInfo(this, event);' ><a href='$url$dir/$file'><img src='$url/.icons/video-ogv.svg' /></a></div>");
				} elseif (preg_match("/(.*?).oga/i", $file)) {
					if ($dir=='.') 
						echo("<div class='image aud' id='$y' onclick='ShowInfo(this, event);'><a href='$url$file'><img src='$url/.icons/audio.svg' /></a></div>");
					else 
						echo("<div class='image aud' id='$y' onclick='ShowInfo(this, event);' ><a href='$url$dir/$file'><img src='$url/.icons/audio.svg' /></a></div>");
				}				
				$y++;
			}
		}
	} else {
		die('Configuration error');
	}
}
//ob_start();
?>
<!doctype html>
<html lang='<?=LANG?>'>
	<head>
		<title>גלריית תמונות - אלעד אלפסה</title> <!-- You can modify the title to your needs -->
		<meta charset="utf-8">
		<link rel="stylesheet" type="text/css" href="gallery.css" />
		<script type="text/javascript" src="gallery.js"></script>
	</head>
	<body onload="init('<?=$full_url ?>');">
		<?
		/* Load custom header from header.html */
		echo file_get_contents("header.html");
		?>
		<span id="showsettings" onclick="toggleSettingsDialog();"><? echo trans("Settings"); ?></span>
		<div id="galleryContainer">
			<?
				if (!isset($_GET['dir'])) { 
					scan('.');
					?>
						<script type="text/javascript">rootDisableAjax=true;</script>
					<?
				}
				elseif(strpos($_GET['dir'],'..')===false) {
					scan($_GET['dir']);				
				} else {
					echo trans("No");				
				}
			?>
		</div>
		<div id="settings" style="display:none">
			<span class="checkbox"><input id="hq" type="checkbox"><label for="hq">
<? echo trans("High quality preview") ?>
</label></span><span class="default"><? echo trans("Default: disabled") ?>
-<span class="bad"><? echo trans("low performance") ?></span></span>
			<div class="explain"><? echo trans("High quality preview improves the preview quality in the info window, by downloading a full version of the image from the server (instead of a scaled-down version)and scaling it down in-browser. Using this feature will allow to zoom in and out. This is not recommended for slow connections. High quality preview is always enabled for files smaller than 1 MiB.")?>
			</div>
			<span class="checkbox"><input id="hashimg" type="checkbox" checked="true"><label for="hashimg"><? echo trans("Hash link to the info window")?></label></span>
<span class="default"><? echo trans("Default: enabled")?>-
<span class="good"><? echo trans("comfortable") ?></span></span>
			<div class="explain"><? echo trans("Hash link to the info window changes the address add add a picture ID to it when opening the info window. Using this feature will allow copying a link to open the page and the info window and sending it to friends to point them to a specific picture, but might flood the browser history with entry for every picture viewed.") ?>
			</div>
		</div>
		<div id="keyboard" style="display:none;" class="hidden">
			<div><kbd>+</kbd><span><? echo trans("Zoom in") ?>*</span></div>
			<div><kbd>-</kbd><span><? echo trans("Zoom out") ?>*</span></div>
			<div><kbd>→</kbd><span><? echo trans("Next picture") ?></span></div>
			<div><kbd>←</kbd><span><? echo trans("Previous picture") ?></span></div>
			<div><kbd>f</kbd><span><? echo trans("Hide metadata") ?></span></div>
			<div><? echo trans("*Available in high quality preview only")?></div>
			<div class="arrow-down"></div>
		</div>
		<span class="btnK" title="<? echo trans("Keyboard shortcuts") ?>" onclick="toggleKeyboardList()">⌨</span>
		<footer>
			WIP. <a href="https://github.com/elad661/elad-gallery">הגלריה הזו בגיטהאב</a> ברישיון GPLv3 ומעלה.
			<br>
			 היישום נצפה בצורה הטובה ביותר ב
<a href="http://www.mozilla.com/">
פיירפוקס 4</a>,
 בצורה קצת פחות טובה בגוגל כרום (בעיות עם האנימציות), ובצורה בסיסית ביותר בדפדפנים מיושנים (IE). מומלץ להשתמש בדפדפן מודרני.
			<div>
				<a href="http://www.w3.org/html/logo/">
					<img src="http://www.w3.org/html/logo/badge/html5-badge-h-css3-graphics-multimedia-performance-semantics.png" width="261" height="64" alt="HTML5 Powered with CSS3 / Styling, Graphics, 3D &amp; Effects, Multimedia, Performance &amp; Integration, and Semantics" title="HTML5 Powered with CSS3 / Styling, Graphics, 3D &amp; Effects, Multimedia, Performance &amp; Integration, and Semantics">
				</a>
			</div>
		</footer>
	</body>
</html>
<?
header("Etag: ".md5(ob_get_contents())); 
gzip_page();
?>
