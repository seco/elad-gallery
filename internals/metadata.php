<?php
/*
elad-gallery is a free, open sourced, lightweight and fast gallery that utilizes PHP, CSS3 and HTML5.
	Copyright (C) 2010-2012  Elad Alfassa <elad@fedoraproject.org>

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

//Include l10n
include("langauge.php");


//Starting compressionable output buffer
if (isBuggyIe())
		ob_start(); //we need OB for the etag to work.
	else
		ob_start("ob_gzhandler");
ini_set('memory_limit', '64M');
header('Content-Type: text/html; charset=utf-8');  

//metadata fetching
if (isset($_GET['file']) && strpos($_GET['file'],'..')===false) {
	$path=str_replace(SCRIPT_DIR_URL, '../', $_GET['file']);
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
}
else {
	die("no file specified");
}
?>
