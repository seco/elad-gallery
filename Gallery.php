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
define('VERSION', "0.0.3-dev");

//Neatly handle settings file
if ((@include_once("settings.php"))!= 'OK')
 die("Please read README for installation instructions. (settings file missing)");
if (!defined('SCRIPT_DIR_URL') || !defined('IS_DIR_INDEX') || !defined('TITLE'))
 die("Error: Missing mandatory settings options. Please see README for more information");

//Include functions
include("internals/functions.php");

//Make sure setup.php isn't readable
if (file_exists("setup.php") && strpos(world_premissions("setup.php"), "r")!==FALSE)
	 die("Error: Please remove setup.php or make sure it's not readable to the outside world.");

//Include l10n
include("internals/langauge.php");


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


//Exif data fetching
if (isset($_GET['exif']) && strpos($_GET['exif'],'..')===false) {
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
	echo(scan($_GET['ajaxDir'], $pathinfo));	
	$etag="galleryAjax".md5(ob_get_contents());
	checkEtag($etag, true);
	exit;
} elseif (!isset($_GET['dir'])) { 
	if ("http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']!=$full_url) {
		header("Location: $full_url");
		die("redirecting");
	}
}
?>
<!doctype html>
<html lang='<?=LANG?>' manifest='internals/appcache.php'>
	<head>
		<title><?=TITLE?></title>
		<meta charset="utf-8">
		<link rel="stylesheet" type="text/css" href="internals/style/gallery.css" />
		<?
			//Load user style, if any
			if (defined('USER_STYLE') && USER_STYLE!='false') { 
		?>
		<link rel="stylesheet" type="text/css" href="<?=USER_STYLE?>" />
		<?
			}
		?>
		<script type="text/javascript" src="internals/js/cache.js"></script>
		<script type="text/javascript" src="internals/js/gallery.js"></script>
		<script type="text/javascript" src="internals/js/fft.js"></script>
		<script type="text/javascript" src="internals/js/throbber.js"></script>
		<script type="text/javascript" src="internals/js/dirInfo.js"></script>
		<script type="text/javascript" src="internals/js/classList.js"></script>
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
					echo(scan('.', $pathinfo));
					?>
						<script type="text/javascript">rootDisableAjax=true;</script>
					<?
				}
				elseif(strpos($_GET['dir'],'..')===false) {
					echo(scan($_GET['dir'], $pathinfo));				
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
			<span class="checkbox"><input id="hashimg" type="checkbox" checked><label for="hashimg"><? echo trans("Hash link to the info window")?></label></span>
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
		<div id="net_status">
			<span id="status"></span>
			<span id="cache_status"></span>
		</div>
		<footer style="direction:ltr">
			Using elad-gallery <?=VERSION?> by <a href="http://www.doom.co.il">Elad Alfassa</a><br>
			GPLv3+ licensed source code  is <a href="https://github.com/elad661/elad-gallery">avilable in github</a>. <br>
			Embeds DejaVu Sans font by <a href="http://dejavu-fonts.org">DejaVu fonts</a><br>
			Best viewed in <a href="http://mozilla.com">Mozilla Firefox 5</a> and above.
		</footer>
	</body>
</html>
<?
$etag=md5(ob_get_contents()); 
checkEtag($etag, true);
?>
