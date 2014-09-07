<?php

namespace zf\components;

class CliResponse extends Response
{
	public function stderr($content)
	{
        $content = "\033[01;31m$content\033[0m";
		file_put_contents('php://stderr', $content, FILE_APPEND);
	}

	public function notFound($message='')
	{
        $this->router->rules or exit(1);
        echo $this->router->help();
        exit(1);
	}

}
