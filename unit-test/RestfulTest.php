<?php

use \zf\helpers\Restful;
use \zf\components\WebRouter;

class RestfulTestRequest
{
	function __construct($method, $path)
	{
		$this->method = $method;
		$this->path = $path;
		$this->segments = explode('/', substr($path, 1));
	}
}

class RestfulTestApp
{
	use Restful;
	public function __construct($router)
	{
		$this->router = $router;
	}

	public function run()
	{
		return $this->router->dispatch();
	}
}

class RestfulTest extends PHPUnit_Framework_TestCase
{
	public function testRouting()
	{
		$app = new RestfulTestApp(new WebRouter(new RestfulTestRequest('GET', '/user')));
		$app->resources('user');

		list($cb, $params) = $app->run();
		$this->assertEquals($cb, ['user/index']);
		$this->assertSame($params, null);

		$app = new RestfulTestApp(new WebRouter(new RestfulTestRequest('POST', '/user')));
		$app->resources('user');

		list($cb, $params) = $app->run();
		$this->assertEquals($cb, ['user/create']);
		$this->assertSame($params, null);

		$app = new RestfulTestApp(new WebRouter(new RestfulTestRequest('PUT', '/user/123')));
		$app->resources('user');

		list($cb, $params) = $app->run();
		$this->assertEquals($cb, ['user/update']);
		$this->assertSame($params, ['userId' => '123']);

		$app = new RestfulTestApp(new WebRouter(new RestfulTestRequest('POST', '/user/123/update')));
		$app->resources('user');

		list($cb, $params) = $app->run();
		$this->assertEquals($cb, ['user/:action']);
		$this->assertSame($params, ['userId' => '123', 'action' => 'update']);
	}

	public function testPrefix()
	{
		$app = new RestfulTestApp(new WebRouter(new RestfulTestRequest('DELETE', '/v1/user/123')));
		$app->resources('v1', ['user']);

		list($cb, $params) = $app->run();
		$this->assertEquals($cb, ['v1/user/destroy']);
		$this->assertSame($params, ['userId' => '123']);
	}

	public function testSubresources()
	{
		$app = new RestfulTestApp(new WebRouter(new RestfulTestRequest('PATCH', '/v1/post/123/comment/abc')));
		$app->resources('v1', ['user', 'post/comment']);

		list($cb, $params) = $app->run();
		$this->assertEquals($cb, ['v1/post-comment/modify']);
		$this->assertSame($params, ['postId' => '123', 'commentId' => 'abc']);

		$app = new RestfulTestApp(new WebRouter(new RestfulTestRequest('POST', '/v1/post/123/comment/234/downvote')));
		$app->resources('v1', ['user', 'post/comment']);

		list($cb, $params) = $app->run();
		$this->assertEquals($cb, ['v1/post-comment/:action']);
		$this->assertSame($params, ['postId' => '123', 'commentId' => '234', 'action' => 'downvote']);
	}
}
