<?php

namespace app\models\db;

use app\components\EmailNotifications;
use app\models\events\AmendmentSupporterEvent;
use yii\base\Event;

/**
 * @package app\models\db
 *
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
 * @property User $user
 * @property Amendment $amendment
 */
class AmendmentSupporter extends ISupporter
{
    const EVENT_SUPPORTED = 'supported_official'; // Called if a new support (like, dislike, official) was created; no initiators
    private static $handlersAttached = false;

    public function init()
    {
        parent::init();

        $this->on(static::EVENT_AFTER_UPDATE, [$this, 'onSaved'], null, false);
        $this->on(static::EVENT_AFTER_INSERT, [$this, 'onSaved'], null, false);
        $this->on(static::EVENT_AFTER_DELETE, [$this, 'onSaved'], null, false);

        // This handler should be called at the end of the event chain
        if (!static::$handlersAttached) {
            static::$handlersAttached = true;
            Event::on(AmendmentSupporter::class, AmendmentSupporter::EVENT_SUPPORTED, [$this, 'checkOfficialSupportNumberReached'], null, true);
        }
    }

    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'amendmentSupporter';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAmendment()
    {
        return $this->hasOne(Amendment::class, ['id' => 'amendmentId']);
    }

    /**
     * @return int[]
     */
    public static function getMyLoginlessSupportIds(): array
    {
        return array_merge(
            \Yii::$app->session->get('loginless_amendment_supports', []),
            \Yii::$app->session->get('anonymous_amendment_supports', []) // @TODO After v4.8
        );
    }

    public static function addLoginlessSupportedAmendment(AmendmentSupporter $support)
    {
        $pre   = static::getMyLoginlessSupportIds();
        $pre[] = intval($support->id);
        \Yii::$app->session->set('anonymous_amendment_supports', $pre);
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
            $alreadySupported = static::getMyLoginlessSupportIds();
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
        $support->setExtraDataEntry(static::EXTRA_DATA_FIELD_GENDER, ($gender !== '' ? $gender : null));
        $support->setExtraDataEntry(static::EXTRA_DATA_FIELD_NON_PUBLIC, $nonPublic);
        $support->save();

        if (!$user) {
            static::addLoginlessSupportedAmendment($support);
        }

        $amendment->refresh();
        $amendment->flushCacheWithChildren(null);

        $support->trigger(AmendmentSupporter::EVENT_SUPPORTED, new AmendmentSupporterEvent($support, $hadEnoughSupportersBefore));
    }

    public static function checkOfficialSupportNumberReached(AmendmentSupporterEvent $event): void
    {
        $support = $event->supporter;
        if ($support->role !== static::ROLE_SUPPORTER) {
            return;
        }
        /** @var Amendment $amendment */
        $amendment = $support->getIMotion();
        $supportType = $amendment->getMyMotionType()->getAmendmentSupportTypeClass();

        if (!$event->hadEnoughSupportersBefore && $amendment->hasEnoughSupporters($supportType)) {
            EmailNotifications::sendAmendmentSupporterMinimumReached($amendment);
        }
    }

    /**
     * @return array
     */
    public function rules()
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
