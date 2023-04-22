<?php

declare(strict_types=1);

namespace app\models\db;

use yii\db\{ActiveQuery, ActiveRecord};

/**
 * @property int|null $id
 * @property int $userId
 * @property int $status
 * @property string $dateCreation
 * @property string $text
 *
 * @property User $user
 */
abstract class IAdminComment extends ActiveRecord
{
    public const TYPE_PROPOSED_PROCEDURE = 1;
    public const TYPE_PROCEDURE_OVERVIEW = 2;
    public const TYPE_PROTOCOL_PRIVATE = 3;
    public const TYPE_PROTOCOL_PUBLIC = 4;

    public const SORT_DESC = 'desc';
    public const SORT_ASC = 'asc';

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'userId'])
            ->andWhere(User::tableName() . '.status != ' . User::STATUS_DELETED);
    }


    public function getMyUser(): ?User
    {
        if ($this->userId) {
            return User::getCachedUser($this->userId);
        } else {
            return null;
        }
    }
}
