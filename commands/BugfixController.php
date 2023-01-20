<?php

namespace app\commands;

use app\components\HTMLTools;
use app\components\yii\MessageSource;
use app\models\db\{Amendment, Consultation, EMailLog, Motion, Site};
use app\models\sectionTypes\ISectionType;
use app\models\settings\AntragsgruenApp;
use yii\console\Controller;

/**
 * Tool to fix some problems (usually only during development)
 * @package app\commands
 */
class BugfixController extends Controller
{
    /**
     * Runs cleanSimpleHtml for a given motion
     */
    public function actionFixMotionText(int $motionId): void
    {
        /** @var Motion|null $motion */
        $motion = Motion::findOne($motionId);
        if (!$motion) {
            $this->stderr('Motion not found' . "\n");
        }
        $changedCount = 0;
        foreach ($motion->getActiveSections() as $section) {
            try {
                if ($section->getSettings()->type !== ISectionType::TYPE_TEXT_SIMPLE) {
                    continue;
                }
                $newText = HTMLTools::cleanSimpleHtml($section->getData());
                $newText = HTMLTools::removeSectioningFragments($newText);
                if ($newText !== $section->getData()) {
                    $changedCount++;
                    $section->setData($newText);
                    $section->save();
                }
            } catch (\Exception $e) {
            }
        }
        if ($changedCount > 0) {
            $this->stdout('Changed section(s): ' . $changedCount . "\n");
        } else {
            $this->stdout('No sections changed' . "\n");
        }
    }

    /**
     * Runs cleanSimpleHtml for a given amendment
     */
    public function actionFixAmendmentText(int $amendmentId): void
    {
        /** @var Amendment|null $amendment */
        $amendment = Amendment::findOne($amendmentId);
        if (!$amendment) {
            $this->stderr('Amendment not found' . "\n");
        }
        $changedCount = 0;
        foreach ($amendment->getActiveSections() as $section) {
            try {
                if ($section->getSettings()->type !== ISectionType::TYPE_TEXT_SIMPLE) {
                    continue;
                }

                //$newText = HTMLTools::cleanSimpleHtml($section->dataRaw); // don't do this; <del>'s are removed

                $newText = HTMLTools::cleanSimpleHtml($section->data);
                $newText = HTMLTools::removeSectioningFragments($newText);
                if ($newText !== $section->data) {
                    $changedCount++;
                    $section->data = $newText;
                    $section->save();
                }
            } catch (\Exception $e) {
            }
        }
        if ($changedCount > 0) {
            $this->stdout('Changed section(s): ' . $changedCount . "\n");
        } else {
            $this->stdout('No sections changed' . "\n");
        }
    }

    /**
     * Fixes all texts of a given consultation
     */
    public function actionFixAllConsultationTexts(string $subdomain, string $consultation): void
    {
        if ($subdomain === '' || $consultation === '') {
            $this->stdout('yii bugfix/fix-all-consultation-texts [subdomain] [consultationPath]' . "\n");
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
            if ($cons->urlPath == $consultation) {
                $con = $cons;
            }
        }
        if (!$con) {
            $this->stderr('Consultation not found' . "\n");
            return;
        }
        foreach ($con->motions as $motion) {
            $this->stdout('- Motion ' . $motion->id . ':' . "\n");
            $this->actionFixMotionText($motion->id);
            foreach ($motion->amendments as $amendment) {
                try {
                    $this->stdout('- Amendment ' . $amendment->id . ':' . "\n");
                    $this->actionFixAmendmentText($amendment->id);
                } catch (\Exception $e) {
                }
            }
        }
        $con->flushCacheWithChildren(null);

        $this->stdout('Finished' . "\n");
    }

    /**
     * Runs cleanSimpleHtml on all texts
     */
    public function actionFixAllTexts(): void
    {
        /** @var Amendment[] $amendments */
        $amendments = Amendment::find()->where('status != ' . Amendment::STATUS_DELETED)->all();
        foreach ($amendments as $amend) {
            try {
                $this->actionFixAmendmentText($amend->id);
            } catch (\Exception $e) {
            }
        }

        /** @var Motion[] $motions */
        $motions = Motion::find()->where('status != ' . Motion::STATUS_DELETED)->all();
        foreach ($motions as $motion) {
            try {
                $this->actionFixMotionText($motion->id);
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * Find translation strings that exist in german, but not in the given language (english by default)
     */
    public function actionFindMissingTranslations(string $language = 'en'): void
    {
        $messageSource = new MessageSource();
        foreach (MessageSource::getTranslatableCategories() as $category => $categoryName) {
            echo "$category ($categoryName):\n";
            $orig  = $messageSource->getBaseMessages($category, 'de');
            $trans = $messageSource->getBaseMessages($category, $language);
            foreach ($orig as $origKey => $origName) {
                if (!isset($trans[$origKey])) {
                    echo " '" . addslashes($origKey) . "' => '', // '" . str_replace("\n", "\\n", $origName) . "'\n";
                }
            }
        }
    }

    /**
     * Removes all slugs from deleted motions
     */
    public function actionSetDeletedSlugsToNull(): void
    {
        $app = AntragsgruenApp::getInstance();
        $sql = 'UPDATE `' . $app->tablePrefix . 'motion` SET `slug` = NULL WHERE `status` = ' . Motion::STATUS_DELETED;
        $command      = \Yii::$app->db->createCommand($sql);
        $result = $command->execute();
        echo "Affected motions: " . $result . "\n";
    }

    /**
     * Sends a test e-mail to the given e-mail-address in order to test the e-mail-delivery-configuration
     */
    public function actionTestEmail(string $email_to): void
    {
        try {
            $consultation = Consultation::findOne(['urlPath' => 'std-parteitag']);
            \app\components\mail\Tools::sendWithLog(
                EMailLog::TYPE_DEBUG,
                $consultation,
                $email_to,
                null,
                'Test-E-Mail',
                'This is a test e-mail sent from the command line',
                '<strong>This is a test e-mail</strong> sent from the command line',
                null
            );
            $this->stdout("The e-mail want sent.\n");
        } catch (\Exception $e) {
            $this->stderr("An exception occurred: " . $e->getMessage());
        }
    }
}
