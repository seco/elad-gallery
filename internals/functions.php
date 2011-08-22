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

function world_premissions($file) {
$perms = fileperms($file);
$info = (($perms & 0x0004) ? 'r' : '-');
$info .= (($perms & 0x0002) ? 'w' : '-');
$info .= (($perms & 0x0001) ?
            (($perms & 0x0200) ? 't' : 'x' ) :
            (($perms & 0x0200) ? 'T' : '-'));

return $info;
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

//Format bytes to a human readable form 
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KiB', 'MiB', 'GiB', 'TiB');
  
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
  
    $bytes /= pow(1024, $pow);
  
    return round($bytes, $precision) . ' ' . $units[$pow];
} 
?>
