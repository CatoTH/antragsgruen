<?php

declare(strict_types=1);

namespace app\commands;

use app\models\forms\MotionDeepCopy;
use app\models\db\{Consultation, Motion, Site};
use yii\console\{Controller, ExitCode};

class MotionCloneController extends Controller
{
    public function actionCopyToOtherConsultation(int $motionId, string $siteSubdomain, string $newConsultationUrl, int $newMotionTypeId): int
    {
        /** @var Motion|null $motion */
        $motion = Motion::findOne($motionId);
        if (!$motion) {
            $this->stderr('Motion not found' . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $newConsultation = $this->getConsultation($siteSubdomain, $newConsultationUrl);
        if ($newConsultation->siteId !== $motion->getMyConsultation()->siteId) {
            $this->stderr('Motion does not belong to the same site as consultation' . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
        if ($newConsultation->id === $motion->consultationId) {
            $this->stderr('This command is meant to copy a motion to a different consultation' . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $newMotionType = $newConsultation->getMotionType($newMotionTypeId);
        if (!$newMotionType->isCompatibleTo($motion->getMyMotionType(), [])) {
            $this->stderr('The new motion type is not compatible' . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        MotionDeepCopy::copyMotion($motion, $newMotionType, null, $motion->titlePrefix, $motion->version, false);

        $this->stdout('Successfully moved');

        return 0;
    }

    private function getConsultation(string $siteSubdomain, string $consultationUrl): ?Consultation
    {
        /** @var Site|null $site */
        $site = Site::findOne(['subdomain' => $siteSubdomain]);
        if (!$site) {
            $this->stderr('Site not found' . "\n");
            return null;
        }
        $con = null;
        foreach ($site->consultations as $cons) {
            if ($cons->urlPath == $consultationUrl) {
                $con = $cons;
            }
        }
        if (!$con) {
            $this->stderr('Consultation not found' . "\n");
            return null;
        }

        return $con;
    }
}
