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

include("functions.php");
if (isBuggyIe())
		ob_start(); //we need OB for the etag to work.
	else
		ob_start("ob_gzhandler");

header('Content-Type: text/cache-manifest');
header('Cache-Control: no-cache');
function process_dir($dir) {
	$hash_sum='';
	if ($dir=='./')
		$Realdir='';
	else
		$Realdir=$dir;
	if ($handle = opendir($dir)) {
		while (false !== ($file = readdir($handle))) {
			if (is_file($Realdir.$file) && is_readable($Realdir.$file) && !preg_match("/(.*?).php/i", $file)) {
				echo "$Realdir$file\n";
				$hash_sum.=md5_file($Realdir.$file);
			} elseif (is_dir($Realdir.$file) && $file != "." && $file != "..") {
				$hash_sum.=process_dir($Realdir.$file."/");
			}
		}
		closedir($handle);
	}
	else {
		closedir($handle);
		die("error");	
	}
return $hash_sum;
}
/* This function returns a md5 hash of all file names in a specified directory.
 * I do this to make sure Gallery.php is re-cached in case files in the gallery root were added or removed */
function hash_filelist($dir) {
	$hash_sum='';
	if ($dir=='./')
		$Realdir='';
	else
		$Realdir=$dir;
	if ($handle = opendir($dir)) {
		while (false !== ($file = readdir($handle))) {
			if (is_file($Realdir.$file) && is_readable($Realdir.$file)) {
				$hash_sum.=md5($Realdir.$file);
			} elseif (is_dir($Realdir.$file) && is_readable($Realdir.$file) && $file != "." && $file != "..") {
				$hash_sum.=hash_filelist($Realdir.$file."/");
			}
		}
		closedir($handle);
	}
	else {
		closedir($handle);
		die("error");	
	}
return $hash_sum;
}
echo "CACHE MANIFEST\n";
$hash_sum=process_dir('./');
$hash_sum.=hash_filelist('../');
echo "\nNETWORK:\n";
echo '*php' . "\n"; 
echo '../*' . "\n";
$hash_sum.=md5(ob_get_contents());
echo("\n#Fingerprint: ".md5($hash_sum)."\n");

$etag=md5(ob_get_contents()); 
checkEtag($etag, true);
?>
