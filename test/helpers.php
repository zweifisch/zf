<?php

namespace helpers;

function getTime($format) {
	return date($format ,($_SERVER['REQUEST_TIME']));
}

function item($needle, $haystack, $default=null) {
	return array_key_exists($needle, $haystack) ? $haystack[$needle] : $default;
}

function upper($string) {
	return strtoupper((string)$string);
}
