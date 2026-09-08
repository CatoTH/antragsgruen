<?php

declare(strict_types=1);

namespace app\components\yii;

/**
 * The default file-based session, guarded against being written to from the REST API.
 * See RestSessionGuard; RestRedisSessionTester is the same thing for Redis-backed installations.
 */
class RestSessionTester extends \yii\web\Session
{
    use RestSessionGuard;
}
