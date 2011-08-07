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

//Neatly handle settings file
if ((@include_once("settings.php"))!= 'OK')
 die("Please read README for installation instructions. (settings file missing)");
if (!defined('SCRIPT_DIR_URL') || !defined('IS_DIR_INDEX') || !defined('TITLE'))
 die("Error: Missing mandatory settings options. Please see README for more information");

require_once("functions.php");

define('LANG', detect_lang());

ini_set('memory_limit', '64M');
header('Content-Type: text/html; charset=utf-8');  
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
			GPLv3+ licensed source code  is <a href="https://github.com/elad661/elad-gallery">avilable in github</a>. <br>
			Best viewed in <a href="http://mozilla.com">Mozilla Firefox 4</a> and above.
		</footer>
	</body>
</html>
<?
$etag=md5(ob_get_contents()); 
checkEtag($etag, true);
?>
