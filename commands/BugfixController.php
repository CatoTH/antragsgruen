<?php

namespace app\commands;

use app\components\HTMLTools;
use app\components\MessageSource;
use app\models\db\Amendment;
use app\models\db\Motion;
use app\models\db\Site;
use app\models\sectionTypes\ISectionType;
use yii\console\Controller;

/**
 * Tool to fix some problems (usually only during development)
 * @package app\commands
 */
class BugfixController extends Controller
{
    /**
     * Runs cleanSimpleHtml for a given motion
     * @param int $motionId
     */
    public function actionFixMotionText($motionId)
    {
        /** @var Motion $motion */
        $motion = Motion::findOne($motionId);
        if (!$motion) {
            $this->stderr('Motion not found' . "\n");
        }
        $changedCount = 0;
        foreach ($motion->sections as $section) {
            if ($section->getSettings()->type != ISectionType::TYPE_TEXT_SIMPLE) {
                continue;
            }
            $newText = HTMLTools::cleanSimpleHtml($section->data);
            if ($newText != $section->data) {
                $changedCount++;
                $section->data = HTMLTools::cleanSimpleHtml($section->data);
                $section->save();
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
     * @param int $amendmentId
     */
    public function actionFixAmendmentText($amendmentId)
    {
        /** @var Amendment $amendment */
        $amendment = Amendment::findOne($amendmentId);
        if (!$amendment) {
            $this->stderr('Amendment not found' . "\n");
        }
        $changedCount = 0;
        foreach ($amendment->sections as $section) {
            if ($section->getSettings()->type != ISectionType::TYPE_TEXT_SIMPLE) {
                continue;
            }

            //$newText = HTMLTools::cleanSimpleHtml($section->dataRaw); // don't do this; <del>'s are removed

            $newText = HTMLTools::cleanSimpleHtml($section->data);
            if ($newText != $section->data) {
                $changedCount++;
                $section->data = $newText;
                $section->save();
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
     *
     * @param string $subdomain
     * @param string $consultation
     */
    public function actionFixAllConsultationTexts($subdomain, $consultation)
    {
        if ($subdomain == '' || $consultation == '') {
            $this->stdout('yii bugfix/fix-all-consultation-texts [subdomain] [consultationPath]' . "\n");
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
        foreach ($con->motions as $motion) {
            $this->stdout('- Motion ' . $motion->id . ':' . "\n");
            $this->actionFixMotionText($motion->id);
            foreach ($motion->amendments as $amendment) {
                $this->stdout('- Amendment ' . $amendment->id . ':' . "\n");
                $this->actionFixAmendmentText($amendment->id);
            }
        }
        $con->flushCacheWithChildren();

        $this->stdout('Finished' . "\n");
    }

    /**
     * Runs cleanSimpleHtml on all texts
     */
    public function actionFixAllTexts()
    {
        /** @var Amendment[] $amendments */
        $amendments = Amendment::find()->where('status != ' . Amendment::STATUS_DELETED)->all();
        foreach ($amendments as $amend) {
            $this->actionFixAmendmentText($amend->id);
        }

        /** @var Motion[] $motions */
        $motions = Motion::find()->where('status != ' . Motion::STATUS_DELETED)->all();
        foreach ($motions as $motion) {
            $this->actionFixMotionText($motion->id);
        }
    }

    /**
     * Find translation strings that exist in german, but not in the given language (english by default)
     * @param string $language
     */
    public function actionFindMissingTranslations($language = 'en')
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
}
