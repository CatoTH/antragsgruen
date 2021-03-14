<?php

namespace app\models\db;

use app\models\notifications\CommentNotificationSubscriptions;
use app\models\notifications\MotionNotificationSubscriptions;
use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $userId
 * @property int $consultationId
 * @property int $notificationType
 * @property int $notificationReferenceId
 * @property string|null $settings
 * @property string $lastNotification
 *
 * @property Consultation $consultation
 * @property User $user
 */
class UserNotification extends ActiveRecord
{
    const NOTIFICATION_NEW_MOTION          = 0;
    const NOTIFICATION_NEW_AMENDMENT       = 1;
    const NOTIFICATION_NEW_COMMENT         = 2;
    const NOTIFICATION_AMENDMENT_MY_MOTION = 3;

    const COMMENT_REPLIES             = 0;
    const COMMENT_SAME_MOTIONS        = 1;
    const COMMENT_ALL_IN_CONSULTATION = 2;
    public static $COMMENT_SETTINGS = [1, 0, 2]; // First value defines the default value

    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'userNotification';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultation()
    {
        return $this->hasOne(Consultation::class, ['id' => 'consultationId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'userId'])
            ->andWhere(User::tableName() . '.status != ' . User::STATUS_DELETED);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['userId', 'consultationId', 'notificationType'], 'required'],
            [['id', 'userId', 'consultationId', 'notificationType', 'notificationReferenceId'], 'number'],
        ];
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return null|mixed
     */
    public function getSettingByKey($key, $default = null)
    {
        if ($this->settings) {
            $settings = json_decode($this->settings, true);
            if (isset($settings[$key])) {
                return $settings[$key];
            }
        }
        return $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function setSettingByKey($key, $value)
    {
        $settings = [];
        if ($this->settings) {
            $settings = json_decode($this->settings, true);
        }
        $settings[$key] = $value;
        $this->settings = json_encode($settings);
    }

    /**
     * @param Consultation $consultation
     * @param null|int $notiType
     * @return UserNotification[]
     */
    public static function getConsultationNotifications(Consultation $consultation, $notiType = null)
    {
        if ($notiType) {
            $notifications = [];
            foreach ($consultation->userNotifications as $userNotification) {
                if ($userNotification->notificationType == $notiType) {
                    $notifications[] = $userNotification;
                }
            }
            return $notifications;
        } else {
            return $consultation->userNotifications;
        }
    }

    /**
     * @param User $user
     * @param Consultation $consultation
     * @return static[]
     */
    public static function getUserConsultationNotis(User $user, Consultation $consultation)
    {
        return static::findAll([
            'userId'         => $user->id,
            'consultationId' => $consultation->id,
        ]);
    }

    /** @var UserNotification[] */
    protected static $noticache = [];

    /**
     * @param int $type
     * @param int|null $refId
     */
    public static function getNotification(User $user, Consultation $consultation, $type, $refId = null): ?UserNotification
    {
        $key = $user->id . '-' . $consultation->id . '-' . $type . '-' . $refId;
        if (!array_key_exists($key, static::$noticache)) {
            static::$noticache[$key] = static::findOne([
                'userId'                  => $user->id,
                'consultationId'          => $consultation->id,
                'notificationType'        => $type,
                'notificationReferenceId' => $refId,
            ]);
        }
        return static::$noticache[$key];
    }

    /**
     * @param int|null $refId
     * @return UserNotification
     */
    public static function addNotification(User $user, Consultation $consultation, $type, $refId = null): UserNotification
    {
        $noti = static::getNotification($user, $consultation, $type, $refId);
        if (!$noti) {
            $noti                          = new UserNotification();
            $noti->consultationId          = $consultation->id;
            $noti->userId                  = $user->id;
            $noti->notificationType        = $type;
            $noti->notificationReferenceId = $refId;
            $noti->save();
        }

        static::$noticache = [];

        return $noti;
    }

    /**
     * @param int $commentSetting
     */
    public static function addCommentNotification(User $user, Consultation $consultation, $commentSetting)
    {
        if (!in_array($commentSetting, static::$COMMENT_SETTINGS)) {
            return;
        }
        $noti = static::addNotification($user, $consultation, static::NOTIFICATION_NEW_COMMENT);
        $noti->setSettingByKey('comments', $commentSetting);
        $noti->save();
    }

    /**
     * @param User $user
     * @param Consultation $consultation
     * @param int $type
     * @param int|null $refId
     */
    public static function removeNotification(User $user, Consultation $consultation, $type, $refId = null)
    {
        $noti = static::getNotification($user, $consultation, $type, $refId);
        if ($noti) {
            $noti->delete();
        }
        static::$noticache = [];
    }

    /**
     * @param Motion $motion
     */
    public static function notifyNewMotion(Motion $motion)
    {
        $notificationType = UserNotification::NOTIFICATION_NEW_MOTION;
        $notified         = [];
        foreach ($motion->getMyConsultation()->userNotifications as $noti) {
            if ($noti->notificationType === $notificationType && !in_array($noti->userId, $notified) && $noti->user) {
                new MotionNotificationSubscriptions($motion, $noti->user);

                $notified[]             = $noti->userId;
                $noti->lastNotification = date('Y-m-d H:i:s');
                $noti->save();
            }
        }
    }

    /**
     * @param IComment $comment
     */
    public static function notifyNewComment(IComment $comment)
    {
        $usersRepliedTo = $comment->getUserIdsBeingRepliedToByThis();
        $usersInSameIMotion = $comment->getUserIdsActiveOnThisIMotion();

        $notificationType = UserNotification::NOTIFICATION_NEW_COMMENT;
        $notified = [];
        foreach ($comment->getConsultation()->userNotifications as $noti) {
            if ($noti->userId === $comment->userId) {
                continue;
            }
            if ($noti->notificationType === $notificationType && !in_array($noti->userId, $notified) && $noti->user) {
                $commentSetting = $noti->getSettingByKey('comments', static::$COMMENT_SETTINGS[0]);
                if ($commentSetting === static::COMMENT_SAME_MOTIONS && !in_array($noti->userId, $usersInSameIMotion)) {
                    continue;
                }
                if ($commentSetting === static::COMMENT_REPLIES && !in_array($noti->userId, $usersRepliedTo)) {
                    continue;
                }
                // static::COMMENT_ALL_IN_CONSULTATION => all users get it

                new CommentNotificationSubscriptions($noti->user, $comment);

                $notified[] = $noti->userId;
                $noti->lastNotification = date('Y-m-d H:i:s');
                $noti->save();
            }
        }
    }
}
