<?php

namespace app\models\db;

use app\components\DateTools;
use app\components\Tools;
use app\models\settings\AntragsgruenApp;
use app\models\settings\Layout;
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
 * @property string $deadlines
 * @property int $policyMotions
 * @property int $policyAmendments
 * @property int $policyComments
 * @property int $policySupportMotions
 * @property int $policySupportAmendments
 * @property int $initiatorsCanMergeAmendments
 * @property int $motionLikesDislikes
 * @property int $amendmentLikesDislikes
 * @property int $contactName
 * @property int $contactEmail
 * @property int $contactPhone
 * @property int $supportType
 * @property string $supportTypeSettings
 * @property int $amendmentMultipleParagraphs
 * @property int $status
 * @property int $layoutTwoCols
 * @property int $sidebarCreateButton
 * @property int $pdfPageNumbers
 *
 * @property ConsultationSettingsMotionSection[] $motionSections
 * @property Motion[] $motions
 * @property ConsultationAgendaItem[] $agendaItems
 * @property TexTemplate $texTemplate
 */
class ConsultationMotionType extends ActiveRecord
{
    const CONTACT_NONE     = 0;
    const CONTACT_OPTIONAL = 1;
    const CONTACT_REQUIRED = 2;

    const STATUS_VISIBLE = 0;
    const STATUS_DELETED = -1;

    const INITIATORS_MERGE_NEVER           = 0;
    const INITIATORS_MERGE_NO_COLLISSION   = 1;
    const INITIATORS_MERGE_WITH_COLLISSION = 2;

    const DEADLINE_MOTIONS    = 'motions';
    const DEADLINE_AMENDMENTS = 'amendments';
    const DEADLINE_COMMENTS   = 'comments';
    const DEADLINE_MERGING    = 'merging';
    public static $DEADLINE_TYPES = ['motions', 'amendments', 'comments', 'merging'];

    protected $deadlinesObject = null;

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
     * @throws \app\models\exceptions\Internal
     */
    public function getMotionPolicy()
    {
        return IPolicy::getInstanceByID($this->policyMotions, $this);
    }

    /**
     * @return IPolicy
     * @throws \app\models\exceptions\Internal
     */
    public function getAmendmentPolicy()
    {
        return IPolicy::getInstanceByID($this->policyAmendments, $this);
    }

    /**
     * @return IPolicy
     * @throws \app\models\exceptions\Internal
     */
    public function getCommentPolicy()
    {
        return IPolicy::getInstanceByID($this->policyComments, $this);
    }

    /**
     * @return IPolicy
     * @throws \app\models\exceptions\Internal
     */
    public function getMotionSupportPolicy()
    {
        return IPolicy::getInstanceByID($this->policySupportMotions, $this);
    }

    /**
     * @return IPolicy
     * @throws \app\models\exceptions\Internal
     */
    public function getAmendmentSupportPolicy()
    {
        return IPolicy::getInstanceByID($this->policySupportAmendments, $this);
    }

    /**
     * @return ISupportType
     * @throws \app\models\exceptions\Internal
     */
    public function getMotionSupportTypeClass()
    {
        return ISupportType::getImplementation($this->supportType, $this, $this->supportTypeSettings);
    }

    /**
     * @return ISupportType
     * @throws \app\models\exceptions\Internal
     */
    public function getAmendmentSupportTypeClass()
    {
        return ISupportType::getImplementation($this->supportType, $this, $this->supportTypeSettings);
    }

    /**
     * @return Consultation
     */
    public function getMyConsultation()
    {
        $current = Consultation::getCurrent();
        if ($current && $current->id == $this->consultationId) {
            return $current;
        } else {
            return Consultation::findOne($this->consultationId);
        }
    }

    /**
     * @return IPDFLayout|null
     * @throws \app\models\exceptions\Internal
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
        $layout    = $this->getConsultation()->site->getSettings()->siteLayout;
        $layoutDef = Layout::getLayoutPluginDef($layout);
        if ($layoutDef && $layoutDef['odtTemplate']) {
            return $layoutDef['odtTemplate'];
        } else {
            $dir = \yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
            return $dir . 'OpenOffice-Template-Std.odt';
        }
    }

    /**
     * @return string[]
     */
    public function getAvailablePDFTemplates()
    {
        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;
        $return = [];
        foreach (IPDFLayout::getClasses($params) as $id => $data) {
            $return['php' . $id] = $data;
        }
        if ($params->xelatexPath) {
            /** @var TexTemplate[] $texLayouts */
            $texLayouts = TexTemplate::find()->all();
            foreach ($texLayouts as $layout) {
                if ($layout->id === 1) {
                    $preview = $params->resourceBase . 'img/pdf_preview_latex_bdk.png';
                } else {
                    $preview = null;
                }
                $return[$layout->id] = [
                    'title'   => $layout->title,
                    'preview' => $preview,
                ];
            }
        }
        return $return;
    }

    /**
     * @param string $type
     * @return array
     */
    public function getDeadlinesByType($type)
    {
        if ($this->deadlinesObject === null) {
            $this->deadlinesObject = json_decode($this->deadlines, true);
        }
        return (isset($this->deadlinesObject[$type]) ? $this->deadlinesObject[$type] : []);
    }

    /**
     * @param array $deadlines
     */
    public function setAllDeadlines($deadlines)
    {
        $this->deadlines       = json_encode($deadlines);
        $this->deadlinesObject = null;
    }

    /**
     * @param string|null $deadlineMotions
     * @param string|null $deadlineAmendments
     */
    public function setSimpleDeadlines($deadlineMotions, $deadlineAmendments)
    {
        $this->setAllDeadlines([
            static::DEADLINE_MOTIONS    => [['start' => null, 'end' => $deadlineMotions, 'title' => null]],
            static::DEADLINE_AMENDMENTS => [['start' => null, 'end' => $deadlineAmendments, 'title' => null]],
        ]);
    }

    /**
     * @param array $deadline
     * @param null|int $timestamp
     * @return bool
     */
    public static function isInDeadlineRange($deadline, $timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = DateTools::getCurrentTimestamp();
        }
        if ($deadline['start']) {
            $startTs = Tools::dateSql2timestamp($deadline['start']);
            if ($startTs > $timestamp) {
                return false;
            }
        }
        if ($deadline['end']) {
            $endTs = Tools::dateSql2timestamp($deadline['end']);
            if ($endTs < $timestamp) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param string $type
     * @return \DateTime|null
     */
    public function getUpcomingDeadline($type)
    {
        $deadlines = $this->getDeadlinesByType($type);
        foreach ($deadlines as $deadline) {
            if (static::isInDeadlineRange($deadline) && $deadline['end']) {
                return $deadline['end'];
            }
        }
        return null;
    }

    /**
     * @param string $type
     * @return bool
     */
    public function isInDeadline($type)
    {
        $deadlines = $this->getDeadlinesByType($type);
        if (count($deadlines) === 0) {
            return true;
        }
        foreach ($deadlines as $deadline) {
            if (static::isInDeadlineRange($deadline)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param bool $onlyNamed
     * @return array
     */
    public function getAllCurrentDeadlines($onlyNamed = false)
    {
        $found = [];
        foreach (static::$DEADLINE_TYPES as $type) {
            foreach ($this->getDeadlinesByType($type) as $deadline) {
                if ($onlyNamed && !$deadline['title']) {
                    continue;
                }
                if (static::isInDeadlineRange($deadline)) {
                    $deadline['type'] = $type;
                    $found[]          = $deadline;
                }
            }
        }
        return $found;
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
            [['consultationId', 'titleSingular', 'titlePlural', 'createTitle', 'sidebarCreateButton'], 'required'],
            [['layoutTwoCols'], 'required'],
            [['policyMotions', 'policyAmendments', 'policyComments', 'policySupportMotions'], 'required'],
            [['policySupportAmendments', 'initiatorsCanMergeAmendments', 'supportType', 'status'], 'required'],
            [['contactName', 'contactEmail', 'contactPhone', 'amendmentMultipleParagraphs', 'position'], 'required'],

            [['id', 'consultationId', 'position', 'contactName', 'contactEmail', 'contactPhone'], 'number'],
            [['status', 'amendmentMultipleParagraphs', 'amendmentLikesDislikes', 'motionLikesDislikes'], 'number'],
            [['policyMotions', 'policyAmendments', 'policyComments', 'policySupportMotions'], 'number'],
            [['initiatorsCanMergeAmendments', 'pdfLayout', 'layoutTwoCols', 'sidebarCreateButton'], 'number'],

            [['titleSingular', 'titlePlural', 'createTitle', 'motionLikesDislikes', 'amendmentLikesDislikes'], 'safe'],
            [['motionPrefix', 'position', 'supportType', 'contactName', 'contactEmail', 'contactPhone'], 'safe'],
            [['pdfLayout', 'policyMotions', 'policyAmendments', 'policyComments', 'policySupportMotions'], 'safe'],
            [['policySupportAmendments', 'initiatorsCanMergeAmendments', 'layoutTwoCols'], 'safe'],
            [['sidebarCreateButton'], 'safe']
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

    /**
     * @param ConsultationMotionType $cmpMotionType
     * @return boolean
     */
    public function isCompatibleTo(ConsultationMotionType $cmpMotionType)
    {
        $mySections  = $this->motionSections;
        $cmpSections = $cmpMotionType->motionSections;

        if (count($mySections) !== count($cmpSections)) {
            return false;
        }
        for ($i = 0; $i < count($mySections); $i++) {
            if ($mySections[$i]->type !== $cmpSections[$i]->type) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param ConsultationMotionType $cmpMotionType
     * @return array
     */
    public function getSectionCompatibilityMapping(ConsultationMotionType $cmpMotionType)
    {
        $mapping = [];
        for ($i = 0; $i < count($this->motionSections); $i++) {
            $mapping[$this->motionSections[$i]->id] = $cmpMotionType->motionSections[$i]->id;
        }
        return $mapping;
    }

    /**
     * @return ConsultationMotionType[]
     */
    public function getCompatibleMotionTypes()
    {
        $compatible = [];
        foreach ($this->getMyConsultation()->motionTypes as $motionType) {
            if ($motionType->isCompatibleTo($this)) {
                $compatible[] = $motionType;
            }
        }
        return $compatible;
    }
}
