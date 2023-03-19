<?php

declare(strict_types=1);

namespace app\plugins\dbwv\controllers;

use app\controllers\Base;
use app\models\http\JsonResponse;

class AjaxHelperController extends Base
{
    public function actionProposeTitlePrefix(int $motionTypeId, int $tagId): JsonResponse
    {
        $tag = $this->consultation->getTagById($tagId);

        $nextPrefix = $this->consultation->getNextMotionPrefix($motionTypeId, [$tag]);

        return new JsonResponse([
            'prefix' => $nextPrefix
        ]);
    }
}
