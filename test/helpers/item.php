<?php

return function($needle, $haystack, $default=null){
	return array_key_exists($needle, $haystack) ? $haystack[$needle] : $default;
};
