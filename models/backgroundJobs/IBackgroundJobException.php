<?php

declare(strict_types=1);

namespace app\models\backgroundJobs;

interface IBackgroundJobException
{
    public function isCritical(): bool;
}
