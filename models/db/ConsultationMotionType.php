<?php
namespace app\models\db;

use app\models\supportTypes\ISupportType;
use app\models\policies\IPolicy;
use app\views\pdfLayouts\IPDFLayout;
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
 * @property int $texTemplateId
 * @property string $deadlineMotions
 * @property string $deadlineAmendments
 * @property string $policyMotions
 * @property string $policyAmendments
 * @property string $policyComments
 * @property string $policySupportMotions
 * @property string $policySupportAmendments
 * @property int $contactEmail
 * @property int $contactPhone
 * @property int $supportType
 * @property string $supportTypeSettings
 * @property int $amendmentMultipleParagraphs
 * @property int $status
 * @property int $layoutTwoCols
 *
 * @property ConsultationSettingsMotionSection[] $motionSections
 * @property Motion[] $motions
 * @property ConsultationAgendaItem[] $agendaItems
 * @property TexTemplate $texTemplate
 */
class ConsultationMotionType extends ActiveRecord
{
    const CONTACT_NA       = 0;
    const CONTACT_OPTIONAL = 1;
    const CONTACT_REQUIRED = 2;

    const STATUS_VISIBLE = 0;
    const STATUS_DELETED = -1;

    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'consultationMotionType';
    }

    /**
     * @return Consultation
     */
    public function getConsultation()
    {
        $current = Consultation::getCurrent();
        if ($current && $current->id == $this->consultationId) {
            return $current;
        } else {
            return Consultation::findOne($this->consultationId);
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotions()
    {
        return $this->hasMany(Motion::class, ['motionTypeId' => 'id'])
            ->andWhere(Motion::tableName() . '.status != ' . Motion::STATUS_DELETED);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTexTemplate()
    {
        return $this->hasOne(TexTemplate::class, ['id' => 'texTemplateId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotionSections()
    {
        return $this->hasMany(ConsultationSettingsMotionSection::class, ['motionTypeId' => 'id'])
            ->where('status = ' . ConsultationSettingsMotionSection::STATUS_VISIBLE)
            ->orderBy('position');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAgendaItems()
    {
        return $this->hasMany(ConsultationAgendaItem::class, ['motionTypeId' => 'id']);
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
    public function getMotionSupportPolicy()
    {
        return IPolicy::getInstanceByID($this->policySupportMotions, $this);
    }

    /**
     * @return IPolicy
     */
    public function getAmendmentSupportPolicy()
    {
        return IPolicy::getInstanceByID($this->policySupportAmendments, $this);
    }

    /**
     * @return ISupportType
     */
    public function getMotionSupportTypeClass()
    {
        return ISupportType::getImplementation($this->supportType, $this, $this->supportTypeSettings);
    }

    /**
     * @return ISupportType
     */
    public function getAmendmentSupportTypeClass()
    {
        return ISupportType::getImplementation($this->supportType, $this, $this->supportTypeSettings);
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
     * @return string
     */
    public function getOdtTemplateFile()
    {
        $dir = \yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
        if ($this->getConsultation()->site->getSettings()->siteLayout == 'layout-gruenes-ci') {
            return $dir . 'OpenOffice-Template-Gruen.odt';
        } else {
            return $dir . 'OpenOffice-Template-Std.odt';
        }
    }


    /**
     * @return bool
     */
    public function motionDeadlineIsOver()
    {
        $normalized = str_replace([' ', ':', '-'], ['', '', ''], $this->deadlineMotions);
        if ($this->deadlineMotions != '' && date('YmdHis') > $normalized) {
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
        $normalized = str_replace([' ', ':', '-'], ['', '', ''], $this->deadlineAmendments);
        if ($this->deadlineAmendments != '' && date('YmdHis') > $normalized) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function isDeletable()
    {
        foreach ($this->motions as $motion) {
            if ($motion->status != Motion::STATUS_DELETED) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['consultationId', 'titleSingular', 'titlePlural', 'createTitle', 'layoutTwoCols'], 'required'],
            [['policyMotions', 'policyAmendments', 'policyComments', 'policySupportMotions'], 'required'],
            [['policySupportAmendments', 'supportType'], 'required'],
            [['contactEmail', 'contactPhone', 'amendmentMultipleParagraphs', 'position', 'status'], 'required'],

            [['id', 'consultationId', 'position', 'contactEmail', 'contactPhone', 'pdfLayout', 'status'], 'number'],
            [['amendmentMultipleParagraphs', 'layoutTwoCols'], 'number'],

            [['titleSingular', 'titlePlural', 'createTitle'], 'safe'],
            [['motionPrefix', 'position', 'supportType', 'contactEmail', 'contactPhone', 'pdfLayout'], 'safe'],
            [['policyMotions', 'policyAmendments', 'policyComments', 'policySupportMotions'], 'safe'],
            [['policySupportAmendments', 'layoutTwoCols'], 'safe'],
        ];
    }

    /**
     * @param bool $includeWithdrawn
     * @return Motion[]
     */
    public function getVisibleMotions($includeWithdrawn = true)
    {
        $return = [];
        foreach ($this->motions as $motion) {
            if (!in_array($motion->status, $this->getConsultation()->getInvisibleMotionStati(!$includeWithdrawn))) {
                $return[] = $motion;
            }
        }
        return $return;
    }
}
