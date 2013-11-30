<?php

namespace zf\components;

class User
{
	private $session;
	private $cookie;
	private $secret;
	private $request;
	private $tokenKey;

	public function __construct($session, $cookie, $request, $secret, $tokenKey='zf')
	{
		$this->session = $session;
		$this->cookie = $cookie;
		$this->secret = $secret;
		$this->request = $request;
		$this->tokenKey = $tokenKey;
	}

	public function __get($key)
	{
		if(!strncmp('can', $key, 3))
		{
			return is_array($this->session->permissions) ? in_array(substr($key, 3), $this->session->permissions, true) : false;
		}
		else if(!strncmp('is', $key, 2))
		{
			return substr($key, 2) == $this->session->role;
		}
		else
		{
			return $this->session->$key;
		}
	}

	public function __set($key, $value)
	{
		$this->session->__set($key, $value);
	}

	public function grant($permissions)
	{
		$this->session->permissions = $this->session->permissions ? array_merge($this->session->permissions , $permissions) : $permissions;
		return $this;
	}

	public function loggedIn()
	{
		return (bool)$this->session->id;
	}

	public function login($id, $meta=null)
	{
		$meta or $meta = [];
		$meta['id'] = $id;
		$this->session->mset($meta);
		return $this;
	}

	public function logout()
	{
		$this->session->destroy();
	}

	public function remember($id=null, $time=0)
	{
		if (func_num_args()) {
			$signature = $this->sign($id, $this->request->ip, $this->secret);
			$this->cookie->set($this->tokenKey, $signature . ':' . $id, ['expire' => time() + $time]);
		}
		else
		{
			$token = $this->cookie->{$this->tokenKey};
			if ($token && strpos($token, ':'))
			{
				list($signature, $id) = explode(':', $token, 2);
				if ($this->verify($signature, $id, $this->request->ip, $this->secret))
				{
					return $id;
				}
			}
		}
	}

	private function sign($id, $ip, $secret)
	{
		return hash_hmac('sha256', $ip . ':' . $id, $secret);
	}

	private function verify($signature, $id, $ip, $secret)
	{
		return $this->sign($id, $ip, $secret) == $signature;
	}
}
