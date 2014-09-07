<?php

namespace components;

class Response extends \zf\components\Response
{
    public function send()
    {
        if (!array_key_exists($this->status, $this->statuses))
        {
            throw new \Exception("invalid status code '$this->status'");
        }

        $buffer = ob_get_clean();
        if ($buffer)
        {
            $this->stderr($buffer);
        }
        return $this;
    }

    public function notFound($message='')
    {
        $this->status = 404;
        $this->body = $message;
        return $this;
    }
}
