<?php

namespace app\commands;

use app\models\db\MotionSection;
use yii\console\Controller;

class MoveBinaryContentToFileController extends Controller
{
    private function moveSingleFile(int $motionId, int $sectionId): void
    {
        $section = MotionSection::findOne(['motionId' => $motionId, 'sectionId' => $sectionId]);
        if ($section->data && $section->getSettings()) {
            $section->setData(base64_decode($section->data));
            $section->save();
        }
    }

    private function revertFileOffload(string $fromBasepath, int $motionId, int $sectionId): void
    {
        $section = MotionSection::findOne(['motionId' => $motionId, 'sectionId' => $sectionId]);
        if ($section->data) {
            return; // Nothing to do
        }

        $file = $section->getExternallySavedFile($fromBasepath);
        if ($file) {
            $content = file_get_contents($file);
            if ($content) {
                $section->setData($content);
                $section->save();
                echo "- Set content: $motionId $sectionId\n";
            }
        }
    }

    public function actionDo(): void
    {
        $comm = \Yii::$app->db->createCommand('SELECT a.motionId, a.sectionId FROM motionSection a JOIN consultationSettingsMotionSection b ON a.sectionId = b.id WHERE b.type IN (3, 5, 6)');
        $sections  = $comm->queryAll();
        foreach ($sections as $i => $section) {
            if (($i % 100) === 0) {
                echo "- " . ($i + 1) . " / " . count($sections) . "\n";
            }
            $this->moveSingleFile(intval($section['motionId']), intval($section['sectionId']));
        }
    }

    public function actionRevert(string $fromBasepath): void
    {
        $comm = \Yii::$app->db->createCommand('SELECT a.motionId, a.sectionId FROM motionSection a JOIN consultationSettingsMotionSection b ON a.sectionId = b.id WHERE b.type IN (3, 5, 6)');
        $sections  = $comm->queryAll();
        foreach ($sections as $i => $section) {
            if (($i % 100) === 0) {
                echo "- " . ($i + 1) . " / " . count($sections) . "\n";
            }
            $this->revertFileOffload($fromBasepath, intval($section['motionId']), intval($section['sectionId']));
        }
    }

    public function actionRevertSection(string $fromBasepath, int $motionId, int $sectionId): void
    {
        $this->revertFileOffload($fromBasepath, $motionId, $sectionId);
    }
}
