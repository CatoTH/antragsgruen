<?php
namespace app\commands;

use app\models\db\Consultation;
use app\models\db\Site;
use app\models\db\User;
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
        $tables = ['amendment', 'amendmentSection', 'motion', 'motionSection'];
        foreach ($tables as $table) {
            $command = \yii::$app->db->createCommand();
            $command->setSql('UPDATE `' . $table . '` SET cache = ""');
            $command->execute();
        }

        $this->stdout('All caches of all consultations have been flushed' . "\n");
    }
}
