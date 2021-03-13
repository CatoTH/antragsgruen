<?php

namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $fromSiteId
 * @property string $toEmail
 * @property int $toUserId
 * @property int $type
 * @property string $fromEmail
 * @property string $dateSent
 * @property string $subject
 * @property string $text
 * @property string $messageId
 * @property int $status
 * @property string $error
 *
 * @property Site $fromSite
 * @property User $toUser
 */
class EMailLog extends ActiveRecord
{
    const TYPE_OTHER                        = 0;
    const TYPE_REGISTRATION                 = 1;
    const TYPE_MOTION_NOTIFICATION_USER     = 2;
    const TYPE_MOTION_NOTIFICATION_ADMIN    = 3;
    const TYPE_ACCESS_GRANTED               = 4;
    const TYPE_DEBUG                        = 5;
    const TYPE_PASSWORD_RECOVERY            = 6;
    const TYPE_SITE_ADMIN                   = 7;
    const TYPE_MOTION_SUBMIT_CONFIRM        = 8;
    const TYPE_EMAIL_CHANGE                 = 9;
    const TYPE_MOTION_SUPPORTER_REACHED     = 10;
    const TYPE_AMENDMENT_PROPOSED_PROCEDURE = 11;
    const TYPE_MOTION_PROPOSED_PROCEDURE    = 12;
    const TYPE_MEMBER_PETITION              = 13;
    const TYPE_COMMENT_NOTIFICATION_USER    = 14;

    const STATUS_SENT              = 0;
    const STATUS_SKIPPED_BLOCKLIST = 1;
    const STATUS_DELIVERY_ERROR    = 2;
    const STATUS_SKIPPED_OTHER     = 3;

    public static $MANDRILL_TAGS = [
        0  => 'other',
        1  => 'registration',
        2  => 'motion-notification-user',
        3  => 'motion-notification-admin',
        4  => 'access-granted',
        5  => 'debug',
        6  => 'password-recovery',
        7  => 'site-admin',
        8  => 'motion-submitted',
        9  => 'email-change',
        10 => 'motion-supporter-reached',
        11 => 'amendment-proposed-procedure',
        12 => 'motion-proposed-procedure',
        13 => 'member-petitions',
        14 => 'comment-notification-user',
    ];

    /**
     * @return string[]
     */
    public static function getTypes()
    {
        return [
            static::TYPE_OTHER                        => 'Sonstiges',
            static::TYPE_REGISTRATION                 => 'Registrierung',
            static::TYPE_MOTION_NOTIFICATION_USER     => 'Benachrichtigung User',
            static::TYPE_MOTION_NOTIFICATION_ADMIN    => 'Benachrichtigung Admin',
            static::TYPE_ACCESS_GRANTED               => 'Veranstaltungs-Zugriff',
            static::TYPE_DEBUG                        => 'Debug',
            static::TYPE_PASSWORD_RECOVERY            => 'Password-Wiederherstellung',
            static::TYPE_SITE_ADMIN                   => 'Als Admin eingetragen',
            static::TYPE_MOTION_SUBMIT_CONFIRM        => 'Bestätgung: Antrag eingereicht',
            static::TYPE_EMAIL_CHANGE                 => 'E-Mail-Änderung',
            static::TYPE_AMENDMENT_PROPOSED_PROCEDURE => 'Änderungsantrag: Verfahrensvorschlag',
            static::TYPE_MOTION_PROPOSED_PROCEDURE    => 'Antrag: Verfahrensvorschlag',
            static::TYPE_MEMBER_PETITION              => 'Mitgliederpettion',
            static::TYPE_COMMENT_NOTIFICATION_USER    => 'Kommentar-Benachrichtigung',
        ];
    }

    /**
     * @return string[]
     */
    public static function getStatusNames()
    {
        return [
            static::STATUS_SENT              => 'Verschickt',
            static::STATUS_SKIPPED_BLOCKLIST => 'Nicht verschickt (E-Mail-Blocklist)',
            static::STATUS_DELIVERY_ERROR    => 'Versandfehler',
            static::STATUS_SKIPPED_OTHER     => 'Übersprungen',
        ];
    }

    public static function tableName(): string
    {
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'emailLog';
    }

    /**
     * @return \Yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'toUserId'])
            ->andWhere(User::tableName() . '.status != ' . User::STATUS_DELETED);
    }

    /**
     * @return \Yii\db\ActiveQuery
     */
    public function getFromSite()
    {
        return $this->hasOne(Site::class, ['id' => 'fromSiteId']);
    }

    public function getUserdataExportObject(): array
    {
        $types = $this->getTypes();
        return [
            'to'         => $this->toEmail,
            'from'       => $this->fromEmail,
            'date'       => $this->dateSent,
            'subject'    => $this->subject,
            'text'       => $this->text,
            'message_id' => $this->messageId,
            'status'     => $this->status,
            'error'      => $this->error,
            'type'       => (isset($types[$this->type]) ? $types[$this->type] : $this->type),
        ];
    }
}
