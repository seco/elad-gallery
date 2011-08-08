<?php
//Detect preferd langauge (TODO: add a way to override browser setting)
function detect_lang() {
	$langs = array();

	if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
	    // break up string into pieces (languages and q factors)
	    preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_parse);

	    if (count($lang_parse[1])) {
		// create a list like "en" => 0.8
		$langs = array_combine($lang_parse[1], $lang_parse[4]);
	    	
		// set default to 1 for any without q factor
		foreach ($langs as $lang => $val) {
		    if ($val === '') $langs[$lang] = 1;
		}

		// sort list based on value	
		arsort($langs, SORT_NUMERIC);
	    }
	}
	$filearray=array();
	$i=0;
	if ($handle = opendir('locale/')) {
		while (false !== ($file = readdir($handle))) {
			if (!is_dir($file) && preg_match("/(.*?).php/i", $file))
				$filearray[$i]=$file;
				$i++;
        	}
		closedir($handle);
	}
	// look through sorted list and use first one that matches our languages
	foreach ($langs as $lang => $val) {
		if (in_array($lang.".php", $filearray) || $lang=='en') {
			return $lang;
		}
	}
	return "en";

}

define('LANG', detect_lang());

//Return translated string
function trans($what) {
	$location = 'locale/' . LANG . '.php';
	if(file_exists($location))
	{
		include $location;
	}
	if (isset($lang[$what])) {
		return $lang[$what];
	} else {
		return $what;
	}
}
?>
