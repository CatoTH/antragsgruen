<?php

namespace app\models\db;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $motionId
 * @property int $position
 * @property int $userId
 * @property string $role
 * @property string $comment
 * @property int $personType
 * @property string $name
 * @property string $organization
 * @property string $resolutionDate
 * @property string $contactName
 * @property string $contactEmail
 * @property string $contactPhone
 *
 * @property User $user
 * @property Motion $motion
 */
class MotionSupporter extends ISupporter
{

    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'motionSupporter';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotion()
    {
        return $this->hasOne(Motion::class, ['id' => 'motionId']);
    }

    /**
     * @return int[]
     */
    public static function getMyAnonymousSupportIds()
    {
        return \Yii::$app->session->get('anonymous_motion_supports', []);
    }

    /**
     * @param MotionSupporter $support
     */
    public static function addAnonymouslySupportedMotion($support)
    {
        $pre   = \Yii::$app->session->get('anonymous_motion_supports', []);
        $pre[] = IntVal($support->id);
        \Yii::$app->session->set('anonymous_motion_supports', $pre);
    }

    /**
     * @param Motion $motion
     * @param User|null $user
     * @param string $name
     * @param string $orga
     * @param int $role
     */
    public static function createSupport(Motion $motion, $user, $name, $orga, $role)
    {
        $maxPos = 0;
        if ($user) {
            foreach ($motion->motionSupporters as $supp) {
                if ($supp->userId == $user->id) {
                    $motion->unlink('motionSupporters', $supp, true);
                } elseif ($supp->position > $maxPos) {
                    $maxPos = $supp->position;
                }
            }
        } else {
            $alreadySupported = static::getMyAnonymousSupportIds();
            foreach ($motion->motionSupporters as $supp) {
                if (in_array($supp->id, $alreadySupported)) {
                    $motion->unlink('motionSupporters', $supp, true);
                } elseif ($supp->position > $maxPos) {
                    $maxPos = $supp->position;
                }
            }
        }

        $support               = new MotionSupporter();
        $support->motionId     = $motion->id;
        $support->userId       = ($user ? $user->id : null);
        $support->name         = $name;
        $support->organization = $orga;
        $support->position     = $maxPos + 1;
        $support->role         = $role;
        $support->save();

        if (!$user) {
            static::addAnonymouslySupportedMotion($support);
        }
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['motionId', 'position', 'role'], 'required'],
            [['id', 'motionId', 'position', 'userId', 'personType'], 'number'],
            [['resolutionDate', 'contactName', 'contactEmail', 'contactPhone'], 'safe'],
            [['position', 'comment', 'personType', 'name', 'organization'], 'safe'],
        ];
    }
}
