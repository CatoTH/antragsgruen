<?php
namespace app\models\db;

use app\models\initiatorForms\IInitiatorForm;
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
 * @property int $cssIcon
 * @property int $pdfLayout
 * @property string $deadlineMotions
 * @property string $deadlineAmendments
 * @property string $policyMotions
 * @property string $policyAmendments
 * @property string $policyComments
 * @property string $policySupport
 * @property int $contactEmail
 * @property int $contactPhone
 * @property int $initiatorForm
 * @property string $initiatorFormSettings
 *
 * @property Consultation $consultation
 * @property ConsultationSettingsMotionSection[] $motionSections
 * @property Motion[] $motions
 * @property ConsultationAgendaItem[] $agendaItems
 */
class ConsultationMotionType extends ActiveRecord
{
    const CONTACT_NA       = 0;
    const CONTACT_OPTIONAL = 1;
    const CONTACT_REQUIRED = 2;

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
     * @return IInitiatorForm
     */
    public function getMotionInitiatorFormClass()
    {
        return IInitiatorForm::getImplementation($this->initiatorForm, $this, $this->initiatorFormSettings);
    }

    /**
     * @return IInitiatorForm
     */
    public function getAmendmentInitiatorFormClass()
    {
        return IInitiatorForm::getImplementation($this->initiatorForm, $this, $this->initiatorFormSettings);
    }

    /**
     * @return IPDFLayout|null
     */
    public function getPDFLayoutClass()
    {
        $class = IPDFLayout::getClassById($this->pdfLayout);
        if ($class === null) {
            return null;
        }
        return new $class($this);
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
            [['contactEmail', 'contactPhone'], 'required'],

            [['id', 'consultationId', 'position', 'contactEmail', 'contactPhone', 'pdfLayout'], 'number'],

            [['titleSingular', 'titlePlural', 'createTitle'], 'safe'],
            [['motionPrefix', 'position', 'initiatorForm', 'contactEmail', 'contactPhone', 'pdfLayout'], 'safe'],
            [['policyMotions', 'policyAmendments', 'policyComments', 'policySupport'], 'safe'],
        ];
    }
}
