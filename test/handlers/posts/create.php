<?php

/**
 * @schema post.json
 */
return function(){
	return $this->mongo->posts->insert($this->body);
};
