<?php

return function($format){
	return date($format ,($_SERVER['REQUEST_TIME']));
};
