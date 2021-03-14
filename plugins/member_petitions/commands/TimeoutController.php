<?php

namespace app\plugins\member_petitions\commands;

use app\components\UrlHelper;
use app\models\db\Consultation;
use app\models\db\Motion;
use app\models\settings\AntragsgruenApp;
use app\plugins\member_petitions\Tools;
use yii\console\Controller;

class TimeoutController extends Controller
{
    public $defaultAction = 'timeout';

    /**
     * @return Consultation[]
     */
    private function getConsultations()
    {
        $consultations = Consultation::findAll(['dateDeletion' => null]);
        $valid         = [];
        foreach ($consultations as $consultation) {
            $discussionType = Tools::getDiscussionType($consultation);
            $petitionType   = Tools::getPetitionType($consultation);
            if ($discussionType && $petitionType) {
                $valid[] = $consultation;
            }
        }
        return $valid;
    }

    /**
     * Send notifications
     */
    public function actionTimeout()
    {
        /** @var AntragsgruenApp $app */
        $app = \Yii::$app->params;
        \Yii::$app->urlManager->baseUrl = $app->domainPlain;

        echo "Timeouting the following petitions:\n";
        foreach ($this->getConsultations() as $consultation) {
            foreach (Tools::getAllMotions([$consultation]) as $motion) {
                UrlHelper::setCurrentConsultation($motion->getMyConsultation());
                UrlHelper::setCurrentSite($motion->getMyConsultation()->site);

                if (Tools::isMotionOverallDeadlineOver($motion)) {
                    echo $motion->id . " - " . $motion->getMyConsultation()->urlPath . " - " . $motion->title . " (";
                    $limit = Tools::getMotionOverallLimit($motion);
                    echo $limit->format('Y-m-d');
                    echo ")\n";

                    $motion->status = Motion::STATUS_PAUSED;
                    $motion->save();
                } else {
                    echo $motion->id . " - " . $motion->getMyConsultation()->urlPath . " - " . $motion->title . " (";
                    $limit = Tools::getMotionOverallLimit($motion);
                    if ($limit) {
                        echo $limit->format('Y-m-d');
                    }
                    echo ")\n";
                }
            }
        }
    }
}
