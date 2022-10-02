<?php

namespace app\models\http;

class RestApiExceptionResponse extends RestApiResponse
{
    public function __construct(int $statusCode, string $message)
    {
        parent::__construct($statusCode, [
            'success' => false,
            'message' => $message,
        ]);
    }
}
