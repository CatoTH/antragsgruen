<?php
namespace app\commands;

use app\components\latex\Exporter;
use app\models\db\Amendment;
use app\models\db\Consultation;
use app\models\db\Motion;
use app\models\db\Site;
use app\models\db\User;
use app\models\settings\AntragsgruenApp;
use yii\console\Controller;

/**
 * Antragsgrün Administration
 * @package app\commands
 */
class AdminController extends Controller
{
    /**
     * Resets the password for a given user
     * @param string $auth
     * @param string $password
     */
    public function actionSetUserPassword($auth, $password)
    {
        if (mb_strpos($auth, ':') === false) {
            if (mb_strpos($auth, '@') !== false) {
                $auth = 'email:' . $auth;
            } else {
                $auth = User::wurzelwerkId2Auth($auth);
            }
        }
        /** @var User $user */
        $user = User::findOne(['auth' => $auth]);
        if (!$user) {
            $this->stderr('User not found: ' . $auth . "\n");
            return;
        }

        $user->changePassword($password);
        $this->stdout('The password has been changed.' . "\n");
    }

    /**
     * Flush all caches for a given consultation
     * @param string $subdomain
     * @param string $consultation
     */
    public function actionFlushConsultationCaches($subdomain, $consultation)
    {
        if ($subdomain == '' || $consultation == '') {
            $this->stdout('yii admin/flush-consultation-caches [subdomain] [consultationPath]' . "\n");
            return;
        }
        /** @var Site $site */
        $site = Site::findOne(['subdomain' => $subdomain]);
        if (!$site) {
            $this->stderr('Site not found' . "\n");
            return;
        }
        $con = null;
        foreach ($site->consultations as $cons) {
            if ($cons->urlPath == $consultation) {
                $con = $cons;
            }
        }
        if (!$con) {
            $this->stderr('Consultation not found' . "\n");
            return;
        }
        /** @var Consultation $con */
        $con->flushCacheWithChildren();
        $this->stdout('All caches of this consultation have been flushed' . "\n");
    }

    /**
     * Flush all consultation caches in the whole system
     */
    public function actionFlushAllConsultationCaches()
    {
        AntragsgruenApp::flushAllCaches();
        $this->stdout('All caches of all consultations have been flushed' . "\n");
    }

    /**
     * Pre-caches some important data.
     * HINT: Probably needs to be called several time, if the memory fills up or the execution time exeeds the limit
     */
    public function actionBuildCaches()
    {
        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;

        /** @var Motion[] $motions */
        $motions = Motion::find()->all();
        foreach ($motions as $motion) {
            if ($motion->status == Motion::STATUS_DELETED) {
                continue;
            }
            echo '- Motion ' . $motion->id . "\n";
            $motion->getNumberOfCountableLines();
            $motion->getFirstLineNumber();
            if ($params->xelatexPath) {
                Exporter::createMotionPdf($motion);
            }
            foreach ($motion->amendments as $amendment) {
                if ($amendment->status == Amendment::STATUS_DELETED) {
                    continue;
                }
                echo '  - Amendment ' . $amendment->id . "\n";
                $amendment->getFirstDiffLine();
                if ($params->xelatexPath) {
                    Exporter::createAmendmentPdf($amendment);
                }
            }
        }
        if ($params->xelatexPath) {
            $this->stdout(
                'Please remember to ensure the runtime/cache-directory and all files are still writable ' .
                'by the web process if the current process is being run with a different user.'  . "\n"
            );
        }
    }
}
