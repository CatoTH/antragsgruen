<?php

use yii\db\Migration;

/**
 * Moves the screeningMotions / screeningAmendments settings from the consultation to the motion types:
 * every motion type inherits the value previously set for its consultation.
 */
class m260717_120000_screening_per_motion_type extends Migration
{
    private function decodeSettings(?string $json): ?array
    {
        if ($json === null || $json === '') {
            return null;
        }
        $json = str_replace("\r", "", $json);
        $json = str_replace(chr(194) . chr(160), " ", $json);
        try {
            $decoded = \ColinODell\Json5\Json5Decoder::decode($json, true);
        } catch (\Exception $e) {
            return null;
        }

        return (is_array($decoded) ? $decoded : null);
    }

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $connection = \Yii::$app->db;

        // Don't use active records here, as they already expect the new settings format
        $consultations = $connection->createCommand('SELECT id, settings FROM consultation')->queryAll();
        foreach ($consultations as $consultation) {
            $settings = $this->decodeSettings($consultation['settings']);
            if ($settings === null) {
                continue;
            }
            if (!array_key_exists('screeningMotions', $settings) && !array_key_exists('screeningAmendments', $settings)) {
                continue;
            }
            $screeningMotions    = (bool)($settings['screeningMotions'] ?? false);
            $screeningAmendments = (bool)($settings['screeningAmendments'] ?? false);
            unset($settings['screeningMotions'], $settings['screeningAmendments']);

            $connection->createCommand()->update(
                'consultation',
                ['settings' => json_encode($settings, JSON_PRETTY_PRINT)],
                ['id' => $consultation['id']]
            )->execute();

            if (!$screeningMotions && !$screeningAmendments) {
                continue;
            }

            $types = $connection->createCommand(
                'SELECT id, settings FROM consultationMotionType WHERE consultationId = :consultationId',
                ['consultationId' => $consultation['id']]
            )->queryAll();
            foreach ($types as $type) {
                $typeSettings = $this->decodeSettings($type['settings']) ?? [];
                $typeSettings['screeningMotions']    = $screeningMotions;
                $typeSettings['screeningAmendments'] = $screeningAmendments;

                $connection->createCommand()->update(
                    'consultationMotionType',
                    ['settings' => json_encode($typeSettings, JSON_PRETTY_PRINT)],
                    ['id' => $type['id']]
                )->execute();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m260717_120000_screening_per_motion_type cannot be reverted.\n";

        return false;
    }
}
