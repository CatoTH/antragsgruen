<?php

declare(strict_types=1);

namespace app\components\mail;

use app\components\BackgroundJobScheduler;
use app\models\backgroundJobs\SendNotification;
use app\models\settings\AntragsgruenApp;
use app\models\db\{Consultation, IMotion, User};

class Tools
{
    public static function getDefaultMailFromName(?Consultation $consultation = null): string
    {
        $name = AntragsgruenApp::getInstance()->mailFromName;
        if ($consultation && $consultation->getSettings()->emailFromName) {
            $name = $consultation->getSettings()->emailFromName;
        }
        return $name;
    }

    public static function getDefaultReplyTo(?IMotion $imotion = null, ?Consultation $consultation = null, ?User $user = null): ?string
    {
        if ($imotion && $imotion->responsibilityId && $imotion->responsibilityUser && $imotion->responsibilityUser->getSettingsObj()->ppReplyTo) {
            return $imotion->responsibilityUser->getSettingsObj()->ppReplyTo;
        }

        if ($user && $user->getSettingsObj()->ppReplyTo) {
            return $user->getSettingsObj()->ppReplyTo;
        }

        $replyTo = null;
        if ($imotion && $imotion->getMyConsultation()) {
            $consultation = $imotion->getMyConsultation();
        }
        if ($consultation) {
            if ($consultation->getSettings()->emailReplyTo) {
                $replyTo = $consultation->getSettings()->emailReplyTo;
            } elseif (AntragsgruenApp::getInstance()->multisiteMode && $consultation->adminEmail) {
                $adminEmails = $consultation->getAdminEmails();
                if (count($adminEmails) > 0) {
                    $replyTo = $adminEmails[0];
                }
            }
        }

        if ($replyTo === null) {
            $replyTo = AntragsgruenApp::getInstance()->mailDefaultReplyTo;
        }

        return $replyTo;
    }

    public static function sendWithLog(
        int $mailType,
        ?Consultation $fromConsultation,
        string $toEmail,
        ?int $toPersonId,
        string $subject,
        string $textPlain,
        string $textHtml = '',
        ?array $noLogReplaces = null,
        ?string $fromName = null,
        ?string $replyTo = null
    ): void {
        $params = AntragsgruenApp::getInstance();
        $fromEmail = $params->mailFromEmail;
        if (!$fromName) {
            $fromName = Tools::getDefaultMailFromName($fromConsultation);
        }
        if (!$replyTo) {
            $replyTo = Tools::getDefaultReplyTo(null, $fromConsultation);
        }

        BackgroundJobScheduler::executeOrScheduleJob(new SendNotification(
            $fromConsultation,
            $mailType,
            $toEmail,
            $toPersonId,
            $subject,
            $textPlain,
            $textHtml,
            $noLogReplaces,
            $fromEmail,
            $fromName,
            $replyTo
        ));
    }
}
