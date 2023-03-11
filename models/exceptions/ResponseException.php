<?php

declare(strict_types=1);

namespace app\models\exceptions;

use app\models\http\ResponseInterface;

class ResponseException extends ExceptionBase
{
    public ResponseInterface $response;

    public function __construct(ResponseInterface $response, string $message = "", int $code = 0, \Throwable $previous = null)
    {
        $this->response = $response;
        parent::__construct($message, $code, $previous);
    }
}
