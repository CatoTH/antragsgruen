<?php

declare(strict_types=1);

namespace app\components\yii;

/**
 * The Redis-backed session, guarded against being written to from the REST API.
 * See RestSessionGuard; RestSessionTester is the same thing for file-based installations.
 */
class RestRedisSessionTester extends \yii\redis\Session
{
    use RestSessionGuard;
}
