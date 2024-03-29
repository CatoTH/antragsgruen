<?php

namespace app\plugins\member_petitions\commands;

use app\models\db\Consultation;
use app\models\settings\AntragsgruenApp;
use app\plugins\member_petitions\notifications\{AdminResponseRequired, DiscussionOver};
use app\plugins\member_petitions\Tools;
use yii\console\Controller;

class NotificationsController extends Controller
{
    public $defaultAction = 'send';

    /**
     * @return Consultation[]
     */
    private function getConsultations(): array
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
    private function sendDiscussionOverNotifications(): void
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
    private function sendAdminAnswerReminders(): void
    {
        foreach ($this->getConsultations() as $consultation) {
            foreach (Tools::getPetitionType($consultation)->motions as $motion) {
                $until = Tools::getPetitionResponseDeadline($motion);
                if (!$until || $until->getTimestamp() > time()) {
                    continue;
                }
                $daysOver = floor((time() - $until->getTimestamp()) / (3600 * 24));
                if (($daysOver % 3) === 0) {
                    echo "Sending admin-notification for: " . $motion->id . " / " . $motion->title . "\n";
                    new AdminResponseRequired($motion);
                }
            }
        }
    }

    /**
     * Send notifications
     */
    public function actionSend(): void
    {
        \Yii::$app->urlManager->baseUrl = AntragsgruenApp::getInstance()->domainPlain;
        $this->sendDiscussionOverNotifications();
        $this->sendAdminAnswerReminders();
    }
}
