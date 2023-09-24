<?php

namespace app\models\db;

use app\components\EmailNotifications;
use app\components\RequestContext;
use app\models\events\AmendmentSupporterEvent;
use app\models\settings\AntragsgruenApp;
use yii\base\Event;
use yii\db\ActiveQuery;

/**
 * @property int|null $id
 * @property int $amendmentId
 * @property int $position
 * @property int|null $userId
 * @property string $role
 * @property string $comment
 * @property int $personType
 * @property string $name
 * @property string $organization
 * @property string $resolutionDate
 * @property string $contactName
 * @property string $contactEmail
 * @property string $contextPhone
 * @property string $dateCreation
 * @property string $extraData
 *
 * @property User|null $user
 * @property Amendment|null $amendment
 */
class AmendmentSupporter extends ISupporter
{
    public const EVENT_SUPPORTED = 'supported_official'; // Called if a new support (like, dislike, official) was created; no initiators
    private static bool $handlersAttached = false;

    public function init(): void
    {
        parent::init();

        $this->on(self::EVENT_AFTER_UPDATE, [$this, 'onSaved'], null, false);
        $this->on(self::EVENT_AFTER_INSERT, [$this, 'onSaved'], null, false);
        $this->on(self::EVENT_AFTER_DELETE, [$this, 'onSaved'], null, false);

        // This handler should be called at the end of the event chain
        if (!self::$handlersAttached) {
            self::$handlersAttached = true;
            Event::on(AmendmentSupporter::class, AmendmentSupporter::EVENT_SUPPORTED, [$this, 'checkOfficialSupportNumberReached'], null, true);
        }
    }

    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'amendmentSupporter';
    }

    public function getAmendment(): ActiveQuery
    {
        return $this->hasOne(Amendment::class, ['id' => 'amendmentId']);
    }

    /**
     * @return int[]
     */
    public static function getMyLoginlessSupportIds(): array
    {
        return RequestContext::getSession()->get('loginless_amendment_supports', []);
    }

    public static function addLoginlessSupportedAmendment(AmendmentSupporter $support): void
    {
        $pre   = self::getMyLoginlessSupportIds();
        $pre[] = intval($support->id);
        RequestContext::getSession()->set('loginless_amendment_supports', $pre);
    }

    public static function getCurrUserSupportStatus(Amendment $amendment): string
    {
        if (User::getCurrentUser()) {
            foreach ($amendment->amendmentSupporters as $supp) {
                if ($supp->userId === User::getCurrentUser()->id) {
                    return $supp->role;
                }
            }
        } else {
            $supportedIds = self::getMyLoginlessSupportIds();
            foreach ($amendment->amendmentSupporters as $supp) {
                if (in_array($supp->id, $supportedIds)) {
                    return $supp->role;
                }
            }
        }
        return '';
    }

    public static function createSupport(Amendment $amendment, ?User $user, string $name, string $orga, string $role, string $gender = '', bool $nonPublic = false): void
    {
        $hadEnoughSupportersBefore = $amendment->hasEnoughSupporters($amendment->getMyMotionType()->getAmendmentSupportTypeClass());

        $maxPos = 0;
        if ($user) {
            foreach ($amendment->amendmentSupporters as $supp) {
                if ($supp->userId === $user->id) {
                    $amendment->unlink('amendmentSupporters', $supp, true);
                } elseif ($supp->position > $maxPos) {
                    $maxPos = $supp->position;
                }
            }
        } else {
            $alreadySupported = self::getMyLoginlessSupportIds();
            foreach ($amendment->amendmentSupporters as $supp) {
                if (in_array($supp->id, $alreadySupported)) {
                    $amendment->unlink('amendmentSupporters', $supp, true);
                } elseif ($supp->position > $maxPos) {
                    $maxPos = $supp->position;
                }
            }
        }

        $support               = new AmendmentSupporter();
        $support->amendmentId  = $amendment->id;
        $support->userId       = ($user ? $user->id : null);
        $support->name         = $name;
        $support->organization = $orga;
        $support->position     = $maxPos + 1;
        $support->role         = $role;
        $support->dateCreation = date('Y-m-d H:i:s');
        $support->setExtraDataEntry(self::EXTRA_DATA_FIELD_GENDER, ($gender !== '' ? $gender : null));
        $support->setExtraDataEntry(self::EXTRA_DATA_FIELD_NON_PUBLIC, $nonPublic);
        $support->save();

        if (!$user) {
            self::addLoginlessSupportedAmendment($support);
        }

        $amendment->refresh();
        $amendment->flushCacheWithChildren(null);

        $support->trigger(AmendmentSupporter::EVENT_SUPPORTED, new AmendmentSupporterEvent($support, $hadEnoughSupportersBefore));
    }

    public static function checkOfficialSupportNumberReached(AmendmentSupporterEvent $event): void
    {
        $support = $event->supporter;
        if ($support->role !== self::ROLE_SUPPORTER) {
            return;
        }
        /** @var Amendment $amendment */
        $amendment = $support->getIMotion();
        $supportType = $amendment->getMyMotionType()->getAmendmentSupportTypeClass();

        if (!$event->hadEnoughSupportersBefore && $amendment->hasEnoughSupporters($supportType)) {
            EmailNotifications::sendAmendmentSupporterMinimumReached($amendment);
        }
    }

    public function rules(): array
    {
        return [
            [['amendmentId', 'position', 'role'], 'required'],
            [['id', 'amendmentId', 'position', 'userId', 'personType'], 'number'],
            [['resolutionDate', 'contactName', 'contactEmail', 'contactPhone'], 'safe'],
            [['position', 'comment', 'personType', 'name', 'organization'], 'safe'],
        ];
    }

    public function getIMotion(): IMotion
    {
        $amendment = Consultation::getCurrent()->getAmendment($this->amendmentId);
        if ($amendment) {
            return $amendment;
        } else {
            return $this->amendment;
        }
    }

    public function onSaved(): void
    {
        $this->getIMotion()->onSupportersChanged();
    }
}
