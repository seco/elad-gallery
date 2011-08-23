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
if ((@include_once("../settings.php"))!= 'OK')
 die("Please read README for installation instructions. (settings file missing)");
if (!defined('SCRIPT_DIR_URL') || !defined('IS_DIR_INDEX') || !defined('TITLE'))
 die("Error: Missing mandatory settings options. Please see README for more information");

//Include functions
include("functions.php");
if (isset($_GET['file']) && strpos($_GET['file'],'..')===false) {
	$args['path']=str_replace(SCRIPT_DIR_URL, '', $_GET['file']);
	if (!file_exists($args['path']) && file_exists("../$args[path]"))
		$args['path']="../$args[path]";
	$args['pathinfo']=pathinfo($args['path']);
	$args['dir']=dirname($args['path']);
	$args['basename']=$args['pathinfo']['basename'];
	$args['md5']=md5_file($args['path']);
	thumbnail($args);
}
function thumbnail($args) {
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
	if (preg_match("/(.*?).jpg/i", $args['path'])) {
		if (isset($_GET['scale']) && $_GET['scale']=='tiny' && !isset($args['no-exif'])) {
			exif_thumb($args);
		}		
		header('Content-type: image/jpeg');
		$type="jpeg";
	}
	elseif (preg_match("/(.*?).png/i", $args['path']))
	{
		header('Content-type: image/png');
		$type="png";
	}
	$thumbfile="$args[dir]/.$thumbdir/$args[basename]@md5=$args[md5]";
	$fs = stat($args['path']);
	$etag=sprintf('"thumb%x-%x-%s"', $fs['ino'], $fs['size'],base_convert(str_pad($fs['mtime'],16,"0"),10,16));
	$headers = apache_request_headers();
	header("Etag: $etag");
	if (!file_exists($args['dir']."/.$thumbdir") && is_writable($args['dir']."/.$thumbdir")) {
		mkdir($args['dir']."/.$thumbdir");
	}
	if (file_exists($args['dir']."/.$thumbdir") && file_exists($thumbfile)) {
		if (!checkEtag($etag, false)) {
			readfile($thumbfile);
		}
	} else {
		list($width, $height) = getimagesize($args['path']);
		$newwidth = $width * $percent;
		$newheight = $height * $percent;
		$thumb = imagecreatetruecolor($newwidth, $newheight);
		$has_thumb;
		imageinterlace($thumb, 1); //Progressive JPEG loads faster
		imageantialias($thumb, true); //Antialiasing
		if ($type=='jpeg')
			$source = imagecreatefromjpeg($args['path']);
		elseif ($type=='png')
			$source = imagecreatefrompng($args['path']);
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
}
function exif_thumb($args) {
	ob_start();
	header("HTTP/1.1 200 OK");
	header("Status: 200 OK");
	header('Content-type: image/jpeg');
	$thumb=exif_thumbnail($args['path']);
	if ($thumb===false)
		return false;
	else
		echo $thumb;
	$etag="exif_thumb".md5(ob_get_contents());
	checkEtag($etag, true);
	exit;
}
?>
