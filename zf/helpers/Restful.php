<?php

namespace zf\helpers;

trait Restful
{
	/**
	 * <code>
	 * resources("v1", ["user", "post"]);
	 * resources(["user", "post"]);
	 * resources("user", "post", "post/comment");
	 * </code>
	 */
	public function resources()
	{
		$args = func_get_args();
		$resources = end($args);
		if (is_array($resources))
		{
			$prefix = is_string($args[0]) ? $args[0]: '';
		}
		else
		{
			$prefix = '';
			$resources = $args;
		}
		foreach($resources as $resource)
		{
			$this->resource($prefix, $resource);
		}
		return $this;
	}

	/*
	 * <code>
	 * resource('v1', 'payment');
	 * resource('payment');
	 * </code>
	 */
	public function resource()
	{
		if(2 === func_num_args())
		{
			list($prefix, $resource) = func_get_args();
		}
		else
		{
			$prefix = '';
			$resource = func_get_arg(0);
		}

		$segments = explode('/', $resource);
		$name = array_pop($segments);

		$path = implode('', array_map(function($segment) {
			return '/' . $segment . '/:' . $segment . 'Id';
		}, $segments)). '/' . $name;

		$fsPath = str_replace('/', '-', $resource);

		if ($prefix) {
			$fsPath = $prefix . '/' . $fsPath;
			$path = '/' . $prefix . $path;
		}
		$id = $name . 'Id';

		$this->router->bulk([
			['GET'    , "$path"              , "$fsPath/index"],
			['GET'    , "$path/new"          , "$fsPath/new"],
			['POST'   , "$path"              , "$fsPath/create"],
			['GET'    , "$path/:$id"         , "$fsPath/show"],
			['GET'    , "$path/:$id/edit"    , "$fsPath/edit"],
			['PUT'    , "$path/:$id"         , "$fsPath/update"],
			['PATCH'  , "$path/:$id"         , "$fsPath/modify"],
			['DELETE' , "$path/:$id"         , "$fsPath/destroy"],
			['POST'   , "$path/:$id/:action" , "$fsPath/:action"],
		]);
	}
}
