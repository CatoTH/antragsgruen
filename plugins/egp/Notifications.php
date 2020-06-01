<?php

namespace app\plugins\egp;

use app\models\events\{AmendmentSupporterEvent, MotionSupporterEvent};
use app\components\mail\Tools as MailTools;
use app\models\db\{Amendment, EMailLog, Motion};

class Notifications
{
    public static function onAmendmentSupport(AmendmentSupporterEvent $event)
    {
        $event->handled = true;

        /** @var Amendment $amendment */
        $amendment = $event->supporter->getIMotion();
        $consultation = $amendment->getMyConsultation();

        $text = "The following amendment was supported:\n" .
                "Title: " . $amendment->getTitleWithPrefix() . "\n" .
                "Link: " . $amendment->getLink(true) . "\n" .
                "Supporter: " . $event->supporter->organization . "\n";

        foreach ($consultation->getAdminEmails() as $email) {
            MailTools::sendWithLog(
                EMailLog::TYPE_DEBUG,
                $amendment->getMyConsultation(),
                $email,
                null,
                'New amendment support',
                $text
            );
        }
    }

    public static function onMotionSupport(MotionSupporterEvent $event)
    {
        $event->handled = true; // Prevent the standard "number reached" email

        /** @var Motion $motion */
        $motion = $event->supporter->getIMotion();
        $consultation = $motion->getMyConsultation();

        $text = "The following emergency resolution was supported:\n" .
                "Title: " . $motion->getTitleWithPrefix() . "\n" .
                "Link: " . $motion->getLink(true) . "\n" .
                "Supporter: " . $event->supporter->organization . "\n";

        foreach ($consultation->getAdminEmails() as $email) {
            MailTools::sendWithLog(
                EMailLog::TYPE_DEBUG,
                $motion->getMyConsultation(),
                $email,
                null,
                str_replace('%TITLE%', $motion->getMyMotionType()->titleSingular, 'New %TITLE% support'),
                $text
            );
        }
    }
}
