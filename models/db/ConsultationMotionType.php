<?php
namespace app\models\db;

use app\models\initiatorForms\DefaultForm;
use app\models\pdfLayouts\IPDFLayout;
use app\models\policies\IPolicy;
use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $consultationId
 * @property string $titleSingular
 * @property string $titlePlural
 * @property string $createTitle
 * @property string $motionPrefix
 * @property int $position
 * @property int $cssicon
 * @property string $deadlineMotions
 * @property string $deadlineAmendments
 * @property string $policyMotions
 * @property string $policyAmendments
 * @property string $policyComments
 * @property string $policySupport
 *
 * @property Consultation $consultation
 * @property ConsultationSettingsMotionSection[] $motionSections
 * @property Motion[] $motions
 * @property ConsultationAgendaItem[] $agendaItems
 */
class ConsultationMotionType extends ActiveRecord
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'consultationMotionType';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultation()
    {
        return $this->hasOne(Consultation::className(), ['id' => 'consultationId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotions()
    {
        return $this->hasMany(Motion::className(), ['motionTypeId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotionSections()
    {
        return $this->hasMany(ConsultationSettingsMotionSection::className(), ['motionTypeId' => 'id'])
            ->where('status = ' . ConsultationSettingsMotionSection::STATUS_VISIBLE)
            ->orderBy('position');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAgendaItems()
    {
        return $this->hasMany(ConsultationAgendaItem::className(), ['motionTypeId' => 'id']);
    }


    /**
     * @return IPolicy
     */
    public function getMotionPolicy()
    {
        return IPolicy::getInstanceByID($this->policyMotions, $this);
    }

    /**
     * @return IPolicy
     */
    public function getAmendmentPolicy()
    {
        return IPolicy::getInstanceByID($this->policyAmendments, $this);
    }

    /**
     * @return IPolicy
     */
    public function getCommentPolicy()
    {
        return IPolicy::getInstanceByID($this->policyComments, $this);
    }

    /**
     * @return IPolicy
     */
    public function getSupportPolicy()
    {
        return IPolicy::getInstanceByID($this->policySupport, $this);
    }

    /**
     * @return DefaultForm
     */
    public function getMotionInitiatorFormClass()
    {
        return new DefaultForm($this);
    }

    /**
     * @return DefaultForm
     */
    public function getAmendmentInitiatorFormClass()
    {
        return new DefaultForm($this);
    }

    /**
     * @return IPDFLayout
     */
    public function getPDFLayoutClass()
    {
        return new IPDFLayout($this);
    }


    /**
     * @return bool
     */
    public function motionDeadlineIsOver()
    {
        $normalized = str_replace(array(" ", ":", "-"), array("", "", ""), $this->deadlineMotions);
        if ($this->deadlineMotions != "" && date("YmdHis") > $normalized) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function amendmentDeadlineIsOver()
    {
        $normalized = str_replace(array(" ", ":", "-"), array("", "", ""), $this->deadlineAmendments);
        if ($this->deadlineAmendments != "" && date("YmdHis") > $normalized) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['consultationId', 'titleSingular', 'titlePlural', 'createTitle'], 'required'],
            [['policyMotions', 'policyAmendments', 'policyComments', 'policySupport'], 'required'],
            [['id', 'consultationId', 'position'], 'number'],
            [['titleSingular', 'titlePlural', 'createTitle'], 'safe'],
            [['motionPrefix', 'position', 'deadlineMotions', 'deadlineAmendments'], 'safe'],
            [['policyMotions', 'policyAmendments', 'policyComments', 'policySupport'], 'safe'],
        ];
    }
}
