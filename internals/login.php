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
//Include functions
include("functions.php");
function auth($name, $password) {
	$xml=simplexml_load_file("../users.xml");
	$user='';
	foreach ($xml->user as $possibe_user) {
		if ((string) $possibe_user->uname==$name) {
			$user=$possibe_user;
			break;
		}
	}
	if (@(string) $user->password==hash('sha512', $password)) {
			return true;
	}
	return false;
}

//Starting compressionable output buffer
if (isBuggyIe())
		ob_start(); //we need OB for the etag to work.
	else
		ob_start("ob_gzhandler");

ini_set('memory_limit', '64M');
header('Content-Type: text/html; charset=utf-8');  

if (!isset($_POST['username']) && !isset($_POST['password']) && !defined("LOGIN_FUNCTIONS_ONLY")) {

?>
<!doctype html>
<html>
	<head>
		<title>Login</title>
		<meta charset="utf-8">
		<link rel="stylesheet" type="text/css" href="style/login.css" />
	</head>
	<body>
		<form method="post" action="" id="login">
			<input name="username" type="text" value="username" /><!--FIXME: Make it look right-->
			<input name="password" type="password" value="password" />
			<input type="submit" />
		</form>
	</body>
</html>
<?php
} elseif (isset($_POST['username']) && isset($_POST['password'])) {
	if(auth($_POST['username'], $_POST['password']))
		echo "yay";
	else
		echo ":(";

}
$etag=md5(ob_get_contents()); 
checkEtag($etag, true);
?>
