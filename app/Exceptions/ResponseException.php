<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Response;

class ResponseException extends Exception
{
    protected $message;
    protected $code;

    public function __construct($message, $code)
    {
        $this->message = $message;
        $this->code = $code;
    }

    public function render()
    {
        return Response::error($this->message, $this->code);
    }
}
