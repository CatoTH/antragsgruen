<?php

namespace app\commands;

use app\models\db\MotionSection;
use yii\console\Controller;

/**
 * @extends Controller<\yii\console\Application>
 */
class BinaryContentController extends Controller
{
    private function moveSingleFile(int $motionId, int $sectionId): void
    {
        $section = MotionSection::findOne(['motionId' => $motionId, 'sectionId' => $sectionId]);
        if ($section->data && $section->getSettings()) {
            $section->setData(base64_decode($section->data));
            $section->save();
        }
    }

    private function importFile(string $fromBasepath, int $motionId, int $sectionId): void
    {
        $section = MotionSection::findOne(['motionId' => $motionId, 'sectionId' => $sectionId]);
        if ($section->data) {
            return; // Nothing to do
        }

        $file = $section->getExternallySavedFile($fromBasepath);
        if ($file && file_exists($file)) {
            $content = file_get_contents($file);
            if ($content) {
                try {
                    $section->setData($content);
                    $section->save();
                    echo "- Set content: $motionId $sectionId\n";
                } catch (\Throwable $exception) {
                    echo "- FAILED: $motionId $sectionId: " . $exception->getMessage() . "\n";
                }
            }
        }
    }

    public function actionMoveToFile(): void
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

    public function actionImportAllFromFile(string $fromBasepath): void
    {
        $comm = \Yii::$app->db->createCommand('SELECT a.motionId, a.sectionId FROM motionSection a JOIN consultationSettingsMotionSection b ON a.sectionId = b.id WHERE b.type IN (3, 5, 6)');
        $sections  = $comm->queryAll();
        foreach ($sections as $i => $section) {
            if (($i % 100) === 0) {
                echo "- " . ($i + 1) . " / " . count($sections) . "\n";
            }
            $this->importFile($fromBasepath, intval($section['motionId']), intval($section['sectionId']));
        }
    }

    public function actionImportSectionFromFile(string $fromBasepath, int $motionId, int $sectionId): void
    {
        $this->importFile($fromBasepath, $motionId, $sectionId);
    }
}
