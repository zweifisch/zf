<?php

/**
 * @body post.json
 */
return function(){
	return $this->mongo->posts->insert($this->body->asArray());
};
