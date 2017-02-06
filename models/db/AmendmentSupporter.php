<?php

namespace app\models\db;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $amendmentId
 * @property int $position
 * @property int $userId
 * @property string $role
 * @property string $comment
 * @property string $personType
 * @property string $name
 * @property string $organization
 * @property string $resolutionDate
 * @property string $contactName
 * @property string $contactEmail
 * @property string $contextPhone
 *
 * @property User $user
 * @property Amendment $amendment
 */
class AmendmentSupporter extends ISupporter
{

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
    public static function getMyAnonymousSupportIds()
    {
        return \Yii::$app->session->get('anonymous_amendment_supports', []);
    }

    /**
     * @param AmendmentSupporter $support
     */
    public static function addAnonymouslySupportedAmendment($support)
    {
        $pre   = \Yii::$app->session->get('anonymous_amendment_supports', []);
        $pre[] = IntVal($support->id);
        \Yii::$app->session->set('anonymous_amendment_supports', $pre);
    }

    /**
     * @param Amendment $amendment
     * @param User|null $user
     * @param string $name
     * @param string $orga
     * @param null $role
     */
    public static function createSupport(Amendment $amendment, $user, $name, $orga, $role)
    {
        $maxPos = 0;
        if ($user) {
            foreach ($amendment->amendmentSupporters as $supp) {
                if ($supp->userId == $user->id) {
                    $amendment->unlink('amendmentSupporters', $supp, true);
                } elseif ($supp->position > $maxPos) {
                    $maxPos = $supp->position;
                }
            }
        } else {
            $alreadySupported = static::getMyAnonymousSupportIds();
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
        $support->save();

        if (!$user) {
            static::addAnonymouslySupportedAmendment($support);
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
}
