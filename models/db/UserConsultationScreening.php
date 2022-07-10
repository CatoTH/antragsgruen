<?php

namespace app\models\db;

use app\models\notifications\UserAsksPermission;
use app\models\settings\AntragsgruenApp;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $userId
 * @property int $consultationId
 * @property string $dateCreation
 *
 * @property User $user
 * @property Consultation $consultation
 */
class UserConsultationScreening extends ActiveRecord
{
    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'userConsultationScreening';
    }

    public function getUser(): User
    {
        return User::getCachedUser($this->userId);
    }

    public function getConsultation(): ActiveQuery
    {
        return $this->hasOne(Consultation::class, ['id' => 'consultationId']);
    }

    public static function askForConsultationPermission(User $user, Consultation $consultation): UserConsultationScreening
    {
        foreach ($consultation->screeningUsers as $screeningUser) {
            if ($screeningUser->userId === $user->id) {
                // Already asked
                return $screeningUser;
            }
        }

        $screen = new UserConsultationScreening();
        $screen->userId = $user->id;
        $screen->consultationId = $consultation->id;
        $screen->dateCreation = date('Y-m-d H:i:s');
        $screen->save();

        new UserAsksPermission($user, $consultation);

        return $screen;
    }
}
