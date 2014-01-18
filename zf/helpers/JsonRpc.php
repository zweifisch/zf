<?php

namespace zf\helpers;

use Exception;
use zf\components\ClosureSet;

trait JsonRpc
{
	public function rpc($path, $closureSet)
	{
		$this->post($path, function() use ($closureSet){
			$jsonRpc = new zf\JsonRpc(isset($this->config->{'jsonrpc codes'}) ? $this->get('jsonrpc codes') : null);
			$_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';

			if(!$jsonRpc->parse($this->body->asRaw(null)))
			{
				return $jsonRpc->response();
			}

			$closureSet = new ClosureSet($this, $closureSet);
			$this->helper->register('error', function($code, $data=null) use ($jsonRpc){
				return $jsonRpc->error($code, $data);
			});

			foreach($jsonRpc->calls as $call)
			{
				if(!is_array($call))
				{
					return $jsonRpc->result(null, $call)->response();
				}

				list($method, $params, $id) = $call;

				if(!$closureSet->exists($method))
				{
					return $jsonRpc->result($id, $jsonRpc->methodNotFound())->response();
				}

				try
				{
					$handler = $closureSet->__get($method);
					$middlewares = $this->processDocString($handler);
					$result = null;
					if($middlewares)
					{
						$result = $this->runMiddlewares($this->prepareMiddlewares($middlewares));
					}
					if (!isset($result))
					{
						$result = Closure::apply($handler, $params, $this);
					}
				}
				catch (Exception $e)
				{
					$result = $jsonRpc->internalError((string)$e);
				}
				if($id) $jsonRpc->result($id, $result);
			}
			return $jsonRpc->response();
		});
	}
}
