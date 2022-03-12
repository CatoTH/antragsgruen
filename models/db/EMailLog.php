<?php

namespace app\models\db;

use app\models\settings\AntragsgruenApp;
use yii\db\{ActiveQuery, ActiveRecord};

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
    public const TYPE_OTHER                        = 0;
    public const TYPE_REGISTRATION                 = 1;
    public const TYPE_MOTION_NOTIFICATION_USER     = 2;
    public const TYPE_MOTION_NOTIFICATION_ADMIN    = 3;
    public const TYPE_ACCESS_GRANTED               = 4;
    public const TYPE_DEBUG                        = 5;
    public const TYPE_PASSWORD_RECOVERY            = 6;
    public const TYPE_SITE_ADMIN                   = 7;
    public const TYPE_MOTION_SUBMIT_CONFIRM        = 8;
    public const TYPE_EMAIL_CHANGE                 = 9;
    public const TYPE_MOTION_SUPPORTER_REACHED     = 10;
    public const TYPE_AMENDMENT_PROPOSED_PROCEDURE = 11;
    public const TYPE_MOTION_PROPOSED_PROCEDURE    = 12;
    public const TYPE_MEMBER_PETITION              = 13;
    public const TYPE_COMMENT_NOTIFICATION_USER    = 14;

    public const STATUS_SENT              = 0;
    public const STATUS_SKIPPED_BLOCKLIST = 1;
    public const STATUS_DELIVERY_ERROR    = 2;
    public const STATUS_SKIPPED_OTHER     = 3;

    /**
     * @return string[]
     */
    public static function getTypes(): array
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
    public static function getStatusNames(): array
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
        return AntragsgruenApp::getInstance()->tablePrefix . 'emailLog';
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'toUserId'])
            ->andWhere(User::tableName() . '.status != ' . User::STATUS_DELETED);
    }

    public function getFromSite(): ActiveQuery
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
            'type'       => $types[$this->type] ?? $this->type,
        ];
    }
}
