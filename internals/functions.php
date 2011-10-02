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
define("FUNCTIONS_OK", true);
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

//Output an HTML table containing information about a specific directory
function dirInfo($parent, $dir) {
	$return_str="<div class='dir_info' data-for='$dir'>";
	$total_size=0;
	if ($handle = opendir($parent."/".$dir)) {
		$i=0;
		while (false !== ($file = readdir($handle))) {
			if ($file!='.' && $file!='..') {
				$i++;
				$stat=stat($parent."/".$dir."/".$file);
				$total_size+=$stat['size'];
			}
        	}
		closedir($handle);
	} else {
		return false;
	}
	$total_size=formatBytes($total_size);
	$return_str.="<span class='file_count'>$i ".trans("Files")."</span>";
	$return_str.="<span class='total_size'>".$total_size."</span>";
	$return_str.="</div>";
	return $return_str;
}

//Proccess directory
function scan($dir,$pathinfo) {
	$url=SCRIPT_DIR_URL;
	$basename=$pathinfo['basename'];
	$full_url=$url.$basename;
	$return_str='';
	if (isset($_GET['dir']) && $_GET['dir']!='.') { 
		$parent=dirname($dir);
		$return_str.="<div class='folder'><a href='$url$basename?dir=$parent'><span>../<span></a></div><br>";	
	}
	if (isset($_GET['ajaxDir']) && $_GET['ajaxDir']!='.') {
		$parent=dirname($dir);
		$return_str.="<div class='folder'><a href='$url$basename' onclick=\"return changeHash('dir', '$parent', false)\"><span>../</span></a></div><br>";	
	}
	$return_str.="Directory: $dir<br>";
	if (file_exists($dir."/metadata.xml")) {
		$return_str.="<div class='DirDesc'>";
		$metadata = simplexml_load_file($dir."/metadata.xml");
		$return_str.=$metadata->{'folder-comment'};
		$return_str.="</div>";
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
			if (is_dir($dir.'/'.$file) && $file!='.' && $file!='..' && substr($file,0,1)!='.' && $file!="internals") {
				$url1=$_SERVER['REQUEST_URI'];
				$return_str.="<div class='folder' onmouseover='showDirInfo(this,event)' id='$file'><a href='$url$basename?dir=$dir/$file' onclick=\"return changeHash('dir', '$dir/$file', false)\"><span>$file</span></a></div>";
				$return_str.=dirInfo($dir,$file);
			} 
			elseif ($file!='.' && $file!='..' && (preg_match("/(.*?).jpg/i", $file) || preg_match("/(.*?).png/i", $file) || preg_match("/(.*?).ogv/i", $file) || preg_match("/(.*?).webm/i", $file) || preg_match("/(.*?).oga/i", $file))) {
				if ($dir=='.') {
					$file_url=$url.$file;
					$file_path=$file;
				}
				else
				{
					$file_url="$url$dir/$file";
					$file_path="$dir/$file";
				}
				if (preg_match("/(.*?).jpg/i", $file)) {
					$return_str.="<div class='image' id='$y' onclick='ShowInfo(this, event);'><a href='$file_url'><img src='$url/internals/thumbnail.php?file=$file_path&amp;tryExif=true' alt='$file' /></a></div>";
				} elseif (preg_match("/(.*?).png/i", $file)) {
					$return_str.="<div class='image' id='$y' onclick='ShowInfo(this, event);'><a href='$file_url'><img src='$url/internals/thumbnail.php?file=$file_path' alt='$file' /></a></div>";
				} elseif (preg_match("/(.*?).webm/i", $file)) {
					$return_str.="<div class='image vid' id='$y' onclick='ShowInfo(this, event);'><a href='$file_url'><img src='$url/internals/style/video-webm.svg' alt='$file' /></a></div>";
				} elseif (preg_match("/(.*?).ogv/i", $file)) {
					$return_str.="<div class='image vid' id='$y' onclick='ShowInfo(this, event);'><a href='$file_url'><img src='$url/internals/style/video-ogv.svg' alt='$file' /></a></div>";
				} elseif (preg_match("/(.*?).oga/i", $file)) {
					$return_str.="<div class='image aud' id='$y' onclick='ShowInfo(this, event);'><a href='$file_url'><img src='$url/internals/style/audio.svg' alt='$file' /></a></div>";
				}				
				$y++;
			}
		}
	return $return_str;
	} else {
		die('Configuration error');
	}
}
?>
