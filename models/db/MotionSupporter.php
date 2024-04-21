<?php

namespace app\models\db;

use app\components\EmailNotifications;
use app\components\RequestContext;
use app\models\events\MotionSupporterEvent;
use app\models\settings\AntragsgruenApp;
use yii\base\Event;
use yii\db\ActiveQuery;

/**
 * @property int|null $id
 * @property int $motionId
 * @property int $position
 * @property int|null $userId
 * @property string $role
 * @property string|null $comment
 * @property int $personType
 * @property string $name
 * @property string $organization
 * @property string $resolutionDate
 * @property string $contactName
 * @property string $contactEmail
 * @property string $contactPhone
 * @property string $dateCreation
 * @property string $extraData
 *
 * @property User|null $user
 * @property Motion|null $motion
 */
class MotionSupporter extends ISupporter
{
    public const EVENT_SUPPORTED = 'supported_official'; // Called if a new support (like, dislike, official) was created; no initiators
    private static bool $handlersAttached = false;

    public function init(): void
    {
        parent::init();

        $this->on(static::EVENT_AFTER_UPDATE, [$this, 'onSaved'], null, false);
        $this->on(static::EVENT_AFTER_INSERT, [$this, 'onSaved'], null, false);
        $this->on(static::EVENT_AFTER_DELETE, [$this, 'onSaved'], null, false);

        if (!self::$handlersAttached) {
            self::$handlersAttached = true;
            // This handler should be called at the end of the event chain
            Event::on(MotionSupporter::class, MotionSupporter::EVENT_SUPPORTED, [$this, 'checkOfficialSupportNumberReached'], null, true);
        }
    }

    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'motionSupporter';
    }

    public function getMotion(): ActiveQuery
    {
        return $this->hasOne(Motion::class, ['id' => 'motionId']);
    }

    /**
     * @return int[]
     */
    public static function getMyLoginlessSupportIds(): array
    {
        return RequestContext::getSession()->get('loginless_motion_supports', []);
    }

    public static function addLoginlessSupportedMotion(MotionSupporter $support): void
    {
        $pre   = static::getMyLoginlessSupportIds();
        $pre[] = intval($support->id);
        RequestContext::getSession()->set('loginless_motion_supports', $pre);
    }

    public static function getCurrUserSupportStatus(Motion $motion): string
    {
        if (User::getCurrentUser()) {
            foreach ($motion->motionSupporters as $supp) {
                if ($supp->userId === User::getCurrentUser()->id) {
                    return $supp->role;
                }
            }
        } else {
            $supportedIds = self::getMyLoginlessSupportIds();
            foreach ($motion->motionSupporters as $supp) {
                if (in_array($supp->id, $supportedIds)) {
                    return $supp->role;
                }
            }
        }
        return '';
    }

    public static function createSupport(Motion $motion, ?User $user, string $name, string $orga, string $role, string $gender = '', bool $nonPublic = false): void
    {
        $hadEnoughSupportersBefore = $motion->hasEnoughSupporters($motion->getMyMotionType()->getMotionSupportTypeClass());

        $maxPos = 0;
        if ($user) {
            foreach ($motion->motionSupporters as $supp) {
                if ($supp->userId === $user->id) {
                    $motion->unlink('motionSupporters', $supp, true);
                } elseif ($supp->position > $maxPos) {
                    $maxPos = $supp->position;
                }
            }
        } else {
            $alreadySupported = static::getMyLoginlessSupportIds();
            foreach ($motion->motionSupporters as $supp) {
                if (in_array($supp->id, $alreadySupported)) {
                    $motion->unlink('motionSupporters', $supp, true);
                } elseif ($supp->position > $maxPos) {
                    $maxPos = $supp->position;
                }
            }
        }

        $support               = new MotionSupporter();
        $support->motionId     = intval($motion->id);
        $support->userId       = ($user ? intval($user->id) : null);
        $support->name         = $name;
        $support->organization = $orga;
        $support->position     = $maxPos + 1;
        $support->role         = $role;
        $support->dateCreation = date('Y-m-d H:i:s');
        $support->setExtraDataEntry(static::EXTRA_DATA_FIELD_GENDER, ($gender !== '' ? $gender : null));
        $support->setExtraDataEntry(static::EXTRA_DATA_FIELD_NON_PUBLIC, $nonPublic);
        $support->save();

        if (!$user) {
            static::addLoginlessSupportedMotion($support);
        }

        $motion->refresh();

        $support->trigger(MotionSupporter::EVENT_SUPPORTED, new MotionSupporterEvent($support, $hadEnoughSupportersBefore));
    }

    public static function checkOfficialSupportNumberReached(MotionSupporterEvent $event): void
    {
        $support = $event->supporter;
        if ($support->role !== static::ROLE_SUPPORTER) {
            return;
        }
        /** @var Motion $motion */
        $motion = $support->getIMotion();
        $supportType = $motion->getMyMotionType()->getMotionSupportTypeClass();

        if (!$event->hadEnoughSupportersBefore && $motion->hasEnoughSupporters($supportType)) {
            EmailNotifications::sendMotionSupporterMinimumReached($motion);
        }
    }

    public function rules(): array
    {
        return [
            [['motionId', 'position', 'role'], 'required'],
            [['id', 'motionId', 'position', 'userId', 'personType'], 'number'],
            [['resolutionDate', 'contactName', 'contactEmail', 'contactPhone'], 'safe'],
            [['position', 'comment', 'personType', 'name', 'organization'], 'safe'],
        ];
    }

    public function getIMotion(): IMotion
    {
        if (Consultation::getCurrent()) {
            $motion = Consultation::getCurrent()->getMotion($this->motionId);
        } else {
            $motion = null;
        }
        if ($motion) {
            return $motion;
        } else {
            return $this->motion;
        }
    }

    public function onSaved(): void
    {
        $this->getIMotion()->onSupportersChanged();
    }
}
