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
}
