<?php

namespace app\commands;

use app\components\{IMotionStatusFilter, SitePurger};
use app\models\db\{Amendment, Consultation, Motion, Site};
use app\models\settings\AntragsgruenApp;
use yii\console\Controller;

class AdminController extends Controller
{
    private function getConsultationFromParams(string $subdomain, string $consultation): ?Consultation
    {
        if ($subdomain === '' || $consultation === '') {
            $this->stdout('yii admin/flush-consultation-caches [subdomain] [consultationPath]' . "\n");
            return null;
        }
        /** @var Site|null $site */
        $site = Site::findOne(['subdomain' => $subdomain]);
        if (!$site) {
            $this->stderr('Site not found' . "\n");
            return null;
        }
        $con = null;
        foreach ($site->consultations as $cons) {
            if ($cons->urlPath === $consultation) {
                $con = $cons;
            }
        }
        if (!$con) {
            $this->stderr('Consultation not found' . "\n");
            return null;
        }
        return $con;
    }

    /**
     * Flush all caches for a given consultation
     */
    public function actionFlushConsultationCaches(string $subdomain, string $consultation): void
    {
        if ($subdomain === '' || $consultation === '') {
            $this->stdout('yii admin/flush-consultation-caches [subdomain] [consultationPath]' . "\n");
            return;
        }

        $con = $this->getConsultationFromParams($subdomain, $consultation);
        if ($con) {
            $con->flushCacheWithChildren(null);
            $this->stdout('All caches of this consultation have been flushed' . "\n");
        }
    }

    /**
     * Flush all consultation caches in the whole system
     */
    public function actionFlushAllConsultationCaches(): void
    {
        AntragsgruenApp::flushAllCaches();
        $this->stdout('All caches of all consultations have been flushed' . "\n");
    }

    /**
     * Pre-caches some important data.
     * HINT: Probably needs to be called several time, if the memory fills up or the execution time exceeds the limit
     */
    public function actionBuildConsultationCaches(string $subdomain, string $consultation): void
    {
        $params = AntragsgruenApp::getInstance();

        $con = $this->getConsultationFromParams($subdomain, $consultation);
        if (!$con) {
            return;
        }

        foreach (IMotionStatusFilter::onlyUserVisible($con, true)->getFilteredConsultationMotions() as $motion) {
            echo '- Motion ' . $motion->id . "\n";
            $motion->getNumberOfCountableLines();
            $motion->getFirstLineNumber();
            if ($params->xelatexPath || $params->lualatexPath) {
                \app\views\motion\LayoutHelper::createPdfLatex($motion);
            }
            foreach ($motion->amendments as $amendment) {
                if ($amendment->status === Amendment::STATUS_DELETED) {
                    continue;
                }
                echo '  - Amendment ' . $amendment->id . "\n";
                $amendment->getFirstDiffLine();
                if ($params->xelatexPath || $params->lualatexPath) {
                    \app\views\amendment\LayoutHelper::createPdfLatex($amendment);
                }
            }
        }
        if ($params->xelatexPath || $params->lualatexPath) {
            $this->stdout(
                'Please remember to ensure the runtime/cache-directory and all files are still writable ' .
                'by the web process if the current process is being run with a different user.' . "\n"
            );
        }
    }

    /**
     * Pre-caches some important data.
     * HINT: Probably needs to be called several time, if the memory fills up or the execution time exceeds the limit
     */
    public function actionBuildMotionCache(string $motionSlug): void
    {
        $params = AntragsgruenApp::getInstance();

        $motions = Motion::findAll(['slug' => $motionSlug]);
        echo 'Found ' . count($motions) . ' motion(s)' . "\n";
        foreach ($motions as $motion) {
            echo '- Motion ' . $motion->id . "\n";
            $motion->getNumberOfCountableLines();
            $motion->getFirstLineNumber();
            if ($params->xelatexPath || $params->lualatexPath) {
                \app\views\motion\LayoutHelper::createPdfLatex($motion);
            }
            foreach ($motion->amendments as $amendment) {
                if ($amendment->status === Amendment::STATUS_DELETED) {
                    continue;
                }
                echo '  - Amendment ' . $amendment->id . "\n";
                $amendment->getFirstDiffLine();
                if ($params->xelatexPath || $params->lualatexPath) {
                    \app\views\amendment\LayoutHelper::createPdfLatex($amendment);
                }
            }
        }
        if ($params->xelatexPath || $params->lualatexPath) {
            $this->stdout(
                'Please remember to ensure the runtime/cache-directory and all files are still writable ' .
                'by the web process if the current process is being run with a different user.' . "\n"
            );
        }
    }

    /**
     * Delete all sites ready for purging.
     */
    public function actionPurgeFromDatabase(): void
    {
        $app = AntragsgruenApp::getInstance();
        $sql = 'SELECT * FROM `' . $app->tablePrefix . 'site` WHERE `dateDeletion` IS NOT NULL';
        /** @var Site[] $sites */
        $sites = Site::findBySql($sql)->all();
        foreach ($sites as $site) {
            if (!$site->readyForPurge()) {
                $this->stderr("Site " . $site->id . " not ready for purging\n");
                continue;
            }
            try {
                $this->stdout('Purging data of site ' . $site->id . "\n");
                SitePurger::purgeSite($site->id);
                $this->stdout("-> Finished\n");
            } catch (\Exception $e) {
                $this->stderr($e->getMessage());
            }
        }
    }

    /**
     * Delete all sites older than 3 days. Only available in Sandbox mode.
     */
    public function actionDeleteOldSandboxInstances(): void
    {
        $app = AntragsgruenApp::getInstance();
        if ($app->mode !== 'sandbox') {
            $this->stderr('This can only be used in sandbox mode');
            return;
        }

        $sql = 'SELECT * FROM `' . $app->tablePrefix . 'site` ' .
            'WHERE `dateCreation` < NOW() - INTERVAL 3 DAY AND `dateDeletion` IS NULL';
        /** @var Site[] $sites */
        $sites = Site::findBySql($sql)->all();
        foreach ($sites as $site) {
            try {
                $site->setDeleted();
                $this->stdout('- Deleted: ' . $site->id . "\n");
            } catch (\Exception $e) {
                $this->stderr($e->getMessage());
            }
        }
    }

    /**
     * Exports the language strings of a consultation into a language variant in the messages/-directory
     */
    public function actionCreateLanguageFromConsultation(string $subdomain, string $consultation, string $languageKey): void
    {
        if ($subdomain === '' || $consultation === '') {
            $this->stdout('yii admin/flush-consultation-caches [subdomain] [consultationPath]' . "\n");
            return;
        }
        /** @var Site|null $site */
        $site = Site::findOne(['subdomain' => $subdomain]);
        if (!$site) {
            $this->stderr('Site not found' . "\n");
            return;
        }
        $con = null;
        foreach ($site->consultations as $cons) {
            if ($cons->urlPath === $consultation) {
                $con = $cons;
            }
        }
        if (!$con) {
            $this->stderr('Consultation not found' . "\n");
            return;
        }

        $categories = [];
        foreach ($con->texts as $text) {
            if (!isset($categories[$text->category])) {
                $categories[$text->category] = '<?php' . "\n\n" . 'return [' . "\n";
            }
            $categories[$text->category] .= "\t'" . addslashes($text->textId) . "' => '" .
                addslashes(trim($text->text)) . "',\n";
        }

        $baseDir = __DIR__ . '/../messages/' . $languageKey;
        if (!file_exists($baseDir)) {
            mkdir($baseDir);
        }
        foreach ($categories as $catKey => $code) {
            $code .= "];\n";
            file_put_contents($baseDir . '/' . $catKey . '.php', $code);
            $this->stdout('Written: ' . $catKey . ".php\n");
        }
    }
}
