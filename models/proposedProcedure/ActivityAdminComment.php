<?php

declare(strict_types=1);

namespace app\models\proposedProcedure;

use app\models\db\IAdminComment;

class ActivityAdminComment extends IActivity
{
    private IAdminComment $adminComment;

    public function __construct(IAdminComment $adminComment) {
        $this->date = new \DateTimeImmutable($adminComment->dateCreation);
        $this->adminComment = $adminComment;
    }

    public function getAdminComment(): IAdminComment
    {
        return $this->adminComment;
    }
}
