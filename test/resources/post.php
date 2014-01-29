<?php

namespace resources\post;

/**
 * @schema post.json
 */
function create($body, $mongo){
	return $mongo->posts->insert($body);
}
