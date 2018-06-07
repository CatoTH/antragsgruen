<?php

namespace app\plugins\memberPetitions\commands;

use app\models\db\Consultation;
use app\plugins\memberPetitions\notifications\AdminResponseRequired;
use app\plugins\memberPetitions\notifications\DiscussionOver;
use app\plugins\memberPetitions\Tools;
use yii\console\Controller;

class NotificationsController extends Controller
{
    public $defaultAction = 'send';

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
     * Notify the user that the discussion period is over
     */
    private function sendDiscussionOverNotifications()
    {
        foreach ($this->getConsultations() as $consultation) {
            foreach (Tools::getDiscussionType($consultation)->motions as $motion) {
                $until = Tools::getDiscussionUntil($motion);
                if (!$until || $until->getTimestamp() > time()) {
                    continue;
                }
                $daysOver = floor((time() - $until->getTimestamp()) / (3600 * 24));
                if (($daysOver % 7) === 0) {
                    echo "Sending notification for: " . $motion->id . " / " . $motion->title . "\n";
                    new DiscussionOver($motion);
                }
            }
        }
    }

    /**
     * A reminder to the administrators to reply to a petition
     */
    private function sendAdminAnswerReminders()
    {
        foreach ($this->getConsultations() as $consultation) {
            foreach (Tools::getPetitionType($consultation)->motions as $motion) {
                $until = Tools::getPetitionResponseDeadline($motion);
                if (!$until || $until->getTimestamp() > time()) {
                    continue;
                }
                $daysOver = floor((time() - $until->getTimestamp()) / (3600 * 24));
                if (($daysOver % 1) === 0) {
                    echo "Sending admin-notification for: " . $motion->id . " / " . $motion->title . "\n";
                    new AdminResponseRequired($motion);
                }
            }
        }
    }

    /**
     * Send notifications
     */
    public function actionSend()
    {
        \Yii::$app->urlManager->baseUrl = \Yii::$app->params->domainPlain;
        $this->sendDiscussionOverNotifications();
        $this->sendAdminAnswerReminders();
    }
}
