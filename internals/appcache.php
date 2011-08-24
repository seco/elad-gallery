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
$hash_sum='';
function process_dir($dir) {
$hash_sum='';
	if ($handle = opendir($dir)) {
		while (false !== ($file = readdir($handle))) {
			if (is_file($dir.'/'.$file) && is_readable($dir.'/'.$file) && !preg_match("/(.*?).php/i", $file)) {
				echo "$dir/$file\n";
				$hash_sum.=md5($dir.'/'.$file);
			} elseif (is_dir($dir.'/'.$file) && $file != "." && $file != "..") {
				process_dir($dir."/".$file);
			}
		}
		closedir($handle);
	}
	else {
		die("error");	
	}
return $hash_sum;
}
echo "CACHE MANIFEST\n";
$hash_sum=process_dir('.');
echo "\nNETWORK:\n";
echo '*' . "\n";
echo '../*' . "\n";
echo '*/*' . "\n";

echo("\n#Fingerprint: ".md5($hash_sum)."\n");

$etag=md5(ob_get_contents()); 
checkEtag($etag, true);
?>
