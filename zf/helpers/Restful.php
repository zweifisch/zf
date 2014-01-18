<?php

namespace zf\helpers;

trait Restful
{
	public function resource()
	{
		$customMethods = is_array(end($args = func_get_args())) ? array_pop($args) : null;
		$fsPath = implode('/', $args);
		$name = array_pop($args);
		$path = implode('', array_map(function($segment) {
			return '/' . $segment . '/:' . $segment . 'Id';
		}, $args)) . '/' . $name;
		$id = $name . 'Id';
		$routes = [
			['GET'    , "$path"             , ["$fsPath/index"]],
			['GET'    , "$path/new"         , ["$fsPath/new"]],
			['POST'   , "$path"             , ["$fsPath/create"]],
			['GET'    , "$path/:$id"      , ["$fsPath/show"]],
			['GET'    , "$path/:$id/edit" , ["$fsPath/edit"]],
			['PUT'    , "$path/:$id"      , ["$fsPath/update"]],
			['PATCH'  , "$path/:$id"      , ["$fsPath/modify"]],
			['DELETE' , "$path/:$id"      , ["$fsPath/destroy"]],
		];
		if ($customMethods)
		{
			foreach($customMethods as $method)
			{
				$routes[] = ['POST', "/$path/:$id/$method", ["$fsPath/$method"]];
			}
		}
		$this->router->bulk($routes);
		return $this;
	}


}
