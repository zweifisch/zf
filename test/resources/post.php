<?php

namespace resources\post;

/**
 * @param string $postId
 */
function show($postId) {
	return $this->mongo->posts->findOneById($postId);
}

/**
 * @schema post.json
 */
function create($body, $mongo) {
	$result = $mongo->posts->insert($body);
	return $result;
}

/**
 * @param string $postId
 */
function modify($postId) {
	return $this->mongo->posts->set($postId, $this->body);
}

/**
 * @schema post.json
 * @param string $postId
 */
function update($postId) {
	return $this->mongo->posts->updateById($postId, $this->body);
}
