<?php

declare(strict_types=1);

namespace app\controllers\rest;

use app\models\db\User;

class TestController extends RestBase
{
    public function actionIndex(): string
    {
        $this->handleRestHeaders(['GET']);
        if (User::getCurrentUser()) {
            return "Hello, " . User::getCurrentUser()->auth;
        } else {
            return "Hello, Guest";
        }
    }
}
