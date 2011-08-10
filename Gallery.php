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
define('VERSION', "0.0.2-dev");
//Neatly handle settings file
if ((@include_once("settings.php"))!= 'OK')
 die("Please read README for installation instructions. (settings file missing)");
if (!defined('SCRIPT_DIR_URL') || !defined('IS_DIR_INDEX') || !defined('TITLE'))
 die("Error: Missing mandatory settings options. Please see README for more information");

//Make sure setup.php isn't readable
if (file_exists("setup.php") && strpos(world_premissions("setup.php"), "r")!==FALSE)
	 die("Error: Please remove setup.php or make sure it's not readable to the outside world.");

function world_premissions($file) {
$perms = fileperms($file);

if (($perms & 0xC000) == 0xC000) {
    // Socket
    $info = 's';
} elseif (($perms & 0xA000) == 0xA000) {
    // Symbolic Link
    $info = 'l';
} elseif (($perms & 0x8000) == 0x8000) {
    // Regular
    $info = '-';
} elseif (($perms & 0x6000) == 0x6000) {
    // Block special
    $info = 'b';
} elseif (($perms & 0x4000) == 0x4000) {
    // Directory
    $info = 'd';
} elseif (($perms & 0x2000) == 0x2000) {
    // Character special
    $info = 'c';
} elseif (($perms & 0x1000) == 0x1000) {
    // FIFO pipe
    $info = 'p';
} else {
    // Unknown
    $info = 'u';
}

// World
$info .= (($perms & 0x0004) ? 'r' : '-');
$info .= (($perms & 0x0002) ? 'w' : '-');
$info .= (($perms & 0x0001) ?
            (($perms & 0x0200) ? 't' : 'x' ) :
            (($perms & 0x0200) ? 'T' : '-'));

return $info;
}
include("langauge.php");

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
//Etag checking function
function checkEtag($etag, $flush) {
	header("Etag: $etag");
	$headers = apache_request_headers();
	$DoIDsMatch = (isset($headers['If-None-Match']) && $headers['If-None-Match']==$etag);
	if ($DoIDsMatch){
    	header('HTTP/1.1 304 Not Modified');
    	header('Connection: close');
		ob_end_clean();
		exit;
	} else {
		if ($flush==true)
			@ob_end_flush();
		else
			return false;
	}
}
//Starting compressionable output buffer
if (isBuggyIe())
		ob_start(); //we need OB for the etag to work.
	else
		ob_start("ob_gzhandler");
ini_set('memory_limit', '64M');
header('Content-Type: text/html; charset=utf-8');  

//Some useful vars
$pathinfo=pathinfo($_SERVER['SCRIPT_NAME']);
$url=SCRIPT_DIR_URL;
$basename=$pathinfo['basename'];
if (IS_DIR_INDEX)
	$full_url=$url;
else
	$full_url=$url.$basename;

//Format bytes to a human readable form 
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KiB', 'MiB', 'GiB', 'TiB');
  
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
  
    $bytes /= pow(1024, $pow);
  
    return round($bytes, $precision) . ' ' . $units[$pow];
} 

//Thumbnail generation
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
	if (!file_exists($dir."/.$thumbdir") && is_writable($dir."/.$thumbdir")) {
		mkdir($dir."/.$thumbdir");
	}
	if (file_exists($dir."/.$thumbdir") && file_exists($thumbfile)) {
		if (!checkEtag($etag, false)) {
			readfile($thumbfile);
		}
	} else {
		list($width, $height) = getimagesize($path);
		$newwidth = $width * $percent;
		$newheight = $height * $percent;
		$thumb = imagecreatetruecolor($newwidth, $newheight);
		$has_thumb;
		imageinterlace($thumb, 1); //Progressive JPEG loads faster
		imageantialias($thumb, true); //Antialiasing
		if ($type=='jpeg')
			$source = imagecreatefromjpeg($path);
		elseif ($type=='png')
			$source = imagecreatefrompng($path);
		imagecopyresized($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
		imagedestroy($source);
		if (is_writable($thumbfile)) {
			if ($type=='jpeg')
				imagejpeg($thumb,$thumbfile);
			elseif ($type=='png')
				imagepng($thumb,$thumbfile);	
			imagedestroy($thumb);
			$has_thumb=true;
		} else {
			$has_thumb=false;
		}
		if (!checkEtag($etag, false)) {
			if ($has_thumb)
				readfile($thumbfile);
			else {
				if ($type=='jpeg')
					imagejpeg($thumb);
				elseif ($type=='png')
					imagepng($thumb);	
				imagedestroy($thumb);
			}
		}
	}
	exit;
//Exif data fetching
} elseif (isset($_GET['exif']) && strpos($_GET['exif'],'..')===false) {
	$path=str_replace(SCRIPT_DIR_URL, '', $_GET['exif']);
	$fs = stat($path);
	if (preg_match("/(.*?).jpg/i", $path))
		$exif=@exif_read_data($path);
	else
		$exif=false;
	$size=formatBytes(filesize($path));
	$pathinfo=pathinfo($path);
	$filename=$pathinfo['basename'];
	$dir=$pathinfo['dirname'];
	$comment="";
	if (file_exists($dir."/metadata.xml")) { //Metadata.xml handling
		$metadata = simplexml_load_file($dir."/metadata.xml");
		$file_metadata="";
		foreach ($metadata->file as $filenode) {
			if ((string) $filenode->filename==$filename) {
				$file_metadata=$filenode;
				break;
			}
		}
		if(!empty($file_metadata->name))
			echo("<th>" . $file_metadata->name ."</th>");
		if(!empty($file_metadata->creator))
			echo("<tr><td>". trans("Created by:") ."</td><td>" . $file_metadata->creator ."</td></tr>");
		if(!empty($file_metadata->licence))
			echo("<tr><td>". trans("Licence:") ."</td><td>" . $file_metadata->licence ."</td></tr>");
		elseif(!empty($metadata->{'default-licence'})) {
			echo("<tr><td>". trans("Licence:") ."</td><td>" . $metadata->{'default-licence'} ."</td></tr>");
		} else {
			echo("<tr><td>". trans("Licence:") ."</td><td>All rights reserved</td></tr>");
		}
		if(!empty($file_metadata->comment))
			$comment=$file_metadata->comment;
	}
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
			echo("<tr><td>". trans("Size:") ."</td><td>".$exif['RelatedImageWidth']."x".$exif['RelatedImageHeight']."</td></tr>");
		} else {
			list($width, $height) = getimagesize($path);
			echo("<tr><td>". trans("Size:") ."</td><td>". $width."x".$height ."</td></tr>");		
		}
		if (isset($exif['ExposureTime'])) {
			$ExposureArray=explode("/", $exif['ExposureTime']);
			$Exposure=$ExposureArray[0]/$ExposureArray[1];
			echo("<tr><td>". trans("Exposure time:") ."</td><td>".
			 $Exposure ." ".trans("seconds") ."</td></tr>");
		}
		if (isset($exif['FNumber'])) {
			$FArray=explode("/", $exif['FNumber']);
			$f=$FArray[0]/$FArray[1];
			echo("<tr><td>". trans("F number:") ."</td><td>". $f ."</td></tr>");
		}
	} else if (!preg_match("/(.*?).ogv/i", $path) && !preg_match("/(.*?).webm/i", $path) && !preg_match("/(.*?).oga/i", $path)) {
		list($width, $height) = getimagesize($path);
		echo("<tr><td>". trans("Size:") ."</td><td>". $width."x".$height ."</td></tr>");
	}
	echo("<tr style='max-width: 30px'><td style='max-width: 30px'>" . $comment ."</td></tr>");
	$etag="info".md5(ob_get_contents());
	checkEtag($etag, true);
	exit;
} elseif (isset($_GET['ajaxDir']) && strpos($_GET['ajaxDir'],'..')===false) {
	header("HTTP/1.1 200 OK");
	header("Status: 200 OK");
	header('Content-Type: text/html; charset=utf-8'); 
	scan($_GET['ajaxDir']);	
	$etag="galleryAjax".md5(ob_get_contents());
	checkEtag($etag, true);
	exit;
//Fetch exif thumbnail
} elseif (isset($_GET['exifThumb']) && strpos($_GET['exifThumb'],'..')===false) {
	header("HTTP/1.1 200 OK");
	header("Status: 200 OK");
	header('Content-type: image/jpeg');
	echo(exif_thumbnail($_GET['exifThumb']));
	$etag="galleryAjax".md5(ob_get_contents());
	checkEtag($etag, true);
	exit;
} elseif (!isset($_GET['dir'])) { 
	if ("http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']!=$full_url) {
		header("Location: $full_url");
		die("redirecting");
	}
}
//Proccess directory
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
		echo("<a href='$url$basename' onclick=\"return changeHash('dir', '$parent', false)\"><div class='folder'><span>../</span></div></a><br>");	
	}
	echo("Directory: $dir<br>");
	if (file_exists($dir."/metadata.xml")) {
		?>
		<div class="DirDesc">
		<?
			$metadata = simplexml_load_file($dir."/metadata.xml");
			echo $metadata->{'folder-comment'};
		?>
		</div>
		<?	
	}
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
				echo("<a href='$url$basename?dir=$dir/$file' onclick=\"return changeHash('dir', '$dir/$file', false)\"><div class='folder'><span>$file</span></div></a>");
			} 
			elseif ($file!='.' && $file!='..' && (preg_match("/(.*?).jpg/i", $file) || preg_match("/(.*?).png/i", $file) || preg_match("/(.*?).ogv/i", $file) || preg_match("/(.*?).webm/i", $file) || preg_match("/(.*?).oga/i", $file))) {
				if (preg_match("/(.*?).jpg/i", $file)) {
					$base64=base64_encode(exif_thumbnail($dir.'/'.$file)); //FIXME: some jpeg images has no exif thumbnail.
					if ($dir=='.') 
						echo("<div class='image' id='$y' onclick='ShowInfo(this, event);'><a href='$url$file'><img src='$url$basename?exifThumb=$file' alt='$file' /></a></div>");
					else 
						echo("<div class='image' id='$y' onclick='ShowInfo(this, event);'><a href='$url$dir/$file'><img src='$url$basename?exifThumb=$dir/$file' alt='$file' /></a></div>");
				} elseif (preg_match("/(.*?).png/i", $file)) {
					if ($dir=='.') 
						echo("<div class='image' id='$y' onclick='ShowInfo(this, event);'><a href='$url$file'><img src='$full_url?thumb=$url$file' alt='$file' /></a></div>");
					else 
						echo("<div class='image' id='$y' onclick='ShowInfo(this, event);'><a href='$url$dir/$file'><img src='$full_url?thumb=$url$dir/$file' alt='$file' /></a></div>");
				} elseif (preg_match("/(.*?).webm/i", $file)) {
					if ($dir=='.') 
						echo("<div class='image vid' id='$y' onclick='ShowInfo(this, event);'><a href='$url$file'><img src='$url/.icons/video-webm.svg' alt='$file' /></a></div>");
					else 
						echo("<div class='image vid' id='$y' onclick='ShowInfo(this, event);'><a href='$url$dir/$file'><img src='$url/.icons/video-webm.svg' alt='$file' /></a></div>");
				} elseif (preg_match("/(.*?).ogv/i", $file)) {
					if ($dir=='.') 
						echo("<div class='image vid' id='$y' onclick='ShowInfo(this, event);'><a href='$url$file'><img src='$url/.icons/video-ogv.svg' alt='$file' /></a></div>");
					else 
						echo("<div class='image vid' id='$y' onclick='ShowInfo(this, event);' ><a href='$url$dir/$file'><img src='$url/.icons/video-ogv.svg' alt='$file' /></a></div>");
				} elseif (preg_match("/(.*?).oga/i", $file)) {
					if ($dir=='.') 
						echo("<div class='image aud' id='$y' onclick='ShowInfo(this, event);'><a href='$url$file'><img src='$url/.icons/audio.svg' alt='$file' /></a></div>");
					else 
						echo("<div class='image aud' id='$y' onclick='ShowInfo(this, event);' ><a href='$url$dir/$file'><img src='$url/.icons/audio.svg' alt='$file' /></a></div>");
				}				
				$y++;
			}
		}
	} else {
		die('Configuration error');
	}
}
?>
<!doctype html>
<html lang='<?=LANG?>'>
	<head>
		<title><?=TITLE?></title>
		<meta charset="utf-8">
		<link rel="stylesheet" type="text/css" href="gallery.css" />
		<?
			//Load user style, if any
			if (defined('USER_STYLE') && USER_STYLE!=false) { 
		?>
		<link rel="stylesheet" type="text/css" href="<?=USER_STYLE?>" />
		<?
			}
		?>
		<script type="text/javascript" src="gallery.js"></script>
	</head>
	<body onload="init('<?=$full_url ?>');">
		<?
		/* Load custom header from header.html */
		echo file_get_contents("header.html");
		?>
		<div id="ajaxThrobContainer"></div>
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
		<footer style="direction:ltr">
			Using elad-gallery <?=VERSION?> by <a href="http://www.doom.co.il">Elad Alfassa</a><br> 
			GPLv3+ licensed source code  is <a href="https://github.com/elad661/elad-gallery">avilable in github</a>. <br>
			Best viewed in <a href="http://mozilla.com">Mozilla Firefox 4</a> and above.
		</footer>
	</body>
</html>
<?
$etag=md5(ob_get_contents()); 
checkEtag($etag, true);
?>
