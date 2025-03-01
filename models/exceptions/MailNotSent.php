<?php

declare(strict_types=1);

namespace app\models\exceptions;

use app\models\backgroundJobs\IBackgroundJobException;

class MailNotSent extends ExceptionBase implements IBackgroundJobException
{
    private bool $critical;

    public function __construct(bool $critical, string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->critical = $critical;
    }

    public function isCritical(): bool
    {
        return $this->critical;
    }
}
