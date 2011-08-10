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
ini_set('memory_limit', '64M');
header('Content-Type: text/html; charset=utf-8'); 
include("langauge.php");
$title=trans("Image gallery - initial setup");
function check_status() {
	$pathinfo=pathinfo($_SERVER['SCRIPT_NAME']);
	$basename=$pathinfo['basename'];
	$status['version']=version_compare(PHP_VERSION, '5.0.0', '>=');
	$status['settings_writable']=can_create("settings.php");
	$status['htaccess_writable']=can_create(".htaccess");
	$status['files']=(file_exists("langauge.php") && file_exists("Gallery.php") && file_exists("gallery.js") && file_exists("gallery.css"));
	return $status;
}
function can_create($filename) {
	if(file_exists($filename) && is_writable($filename))
		return true;
	elseif (file_exists($filename) && !is_writable($filename))
		return false;
	elseif (!file_exists($filename)) {
		$file=@fopen($filename, "w");
		if ($file) {
			fclose($file);
			return true;
		}
		else
			return false;
	}
	else
		return true;
}
function check() {
	$status=check_status();
	$msg="<div id='status'><ul>";	
	$msg.="<li>".trans("Compatible PHP version:");
	if ($status['version'])
		$msg.=" <span class='ok'>". trans("Yes") ."</span>";
	else
		$msg.=" <span class='prob'>No, your version is too old</span>";
	$msg.="</li>";
	$msg.="<li>".trans("Settings file writeable: ");
	if ($status['settings_writable'])
		$msg.=" <span class='ok'>". trans("Yes") ."</span>";
	else
		$msg.=" <span class='prob'>". trans("Can't write settings file, check permissions") ."</span>";
	$msg.="</li>";
	$msg.="<li>".trans("Files: ");
	if ($status['files'])
		$msg.=" <span class='ok'>". trans("All files present") ."</span>";
	else
		$msg.=" <span class='prob'>". trans("Some important files are missing") ."</span>";
	$msg.="</li>";
	$msg.="</div>";
	return $msg;
}
function setup() {
	$status=check_status();
	if ($status['htaccess_writable'] && $_POST['htaccess']=="Create .htaccess file") {
		$htaccess='AddDefaultCharset UTF-8';
		$htaccess.="\n";
		$htaccess.='AddType video/ogg ogg ogv';
		$htaccess.="\n";
		$htaccess.='AddType audio/ogg oga';
		$htaccess.="\n";
		$htaccess.='AddType video/webm webm';
		$htaccess.="\n";		
		$htaccess.='AddType image/svg+xml svg';
		$htaccess.="\n";
		$htaccess.='<FilesMatch \'\.(js|css|svg|appcache|html|htm)$\'>';
		$htaccess.="\n";
		$htaccess.='	SetOutputFilter DEFLATE';
		$htaccess.="\n";
		$htaccess.='</FilesMatch>';
		$htaccess.="\n";
		$htaccess.='FileETag MTime Size';
		$htaccess.="\n";
		$htaccess.='DirectoryIndex Gallery.php';
		file_put_contents(".htaccess", $htaccess);
	}
	if ($status['version'] && $status['settings_writable'] && $status['files']) {
		$settings='<?php';
		$settings.="\n";
		$settings.='define(\'SCRIPT_DIR_URL\',\'' . $_POST['url'] .'/\');';
		$settings.="\n";
		$settings.='define(\'IS_DIR_INDEX\',true);';
		$settings.="\n";
		$settings.='define(\'TITLE\',\'' . $_POST['title'] .'\');';
		if(!empty($_POST['stylesheet'])) {
			$settings.="\n";
			$settings.='define(\'USER_STYLE\',\'' . $_POST['stylesheet'] .'\');';
		}
		$settings.="\n";
		$settings.='return(\'OK\');';
		$settings.='?>';
		file_put_contents("settings.php", $settings);
	}
	echo('<div id=\'Success\'><span class=\'title\'>' .trans("Setup"). '</span>' . trans("Setup completed successfuly") . '<br/><a href=' .$_POST['url']. '>' .trans("Continue").'</a></div>');
}
function form() {
?>
		<form method="post" action="setup.php">
			<span class="title"><?=trans("Image gallery - initial setup");?></span>
			<?=check()?>
			<table>
				<tr>
					<td>
						<?=trans("Full URL to the directory where Gallery.php is")?>
					</td>
					<td>
						<input id="url" name="url" required type="url" x-moz-errormessage='<?=trans("invalid URL entered")?>'/>
					</td>
				</tr>
				<tr>
					<td>
						<?=trans("Gallery page title")?>
					</td>
					<td>
						<input id="title" name="title" type="text"  required/>
					</td>
				</tr>
				<tr>
					<td>
						<?=trans("URL, relative path or file name for your custom style sheet. leave empty if you don't have such stylesheet.")?>
					</td>
					<td>
						<input id="stylesheet" name="stylesheet" type="text"  />
					</td>
				</tr>
				<tr>
					<td>
						<input type="checkbox" id="htaccess" name="htaccess" value="Create .htaccess file" <? $status=check_status(); if (!$status['htaccess_writable']) echo('disabled'); else echo "checked"; ?>/><label for="htaccess"><?=trans("Create .htaccess file")?></label>
					</td>
				</tr>
			</table>
			<input type="submit" value='<?=trans("continue");?>' <? $status=check_status(); if (!($status['version']&&$status['settings_writable']&&$status['files'])) { echo('disabled'); }?>>
		</form>
<?
}
?>
<!doctype html>
<html lang='<?=LANG?>'>
	<head>
		<title><?=$title?></title>
		<meta charset="utf-8">
		<link rel="stylesheet" type="text/css" href="gallery.css" />
	</head>
	<body class="setup">
		<?
			if (!isset($_POST['url']))	
				echo form();
			else
				setup();
		?>
	</body>
</html>
