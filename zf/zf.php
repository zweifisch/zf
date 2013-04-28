<?php

namespace zf;

function zfAutoLoader($className)
{
	$className = str_replace(__NAMESPACE__.'\\', '', $className);
	$filename = __DIR__ . DIRECTORY_SEPARATOR . $className . ".php";
	if (is_readable($filename))
	{
		require $filename;
	}
}

spl_autoload_register("\zf\zfAutoLoader");
