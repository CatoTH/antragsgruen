<?php

use yii\db\Migration;

/**
 * Class m180901_131243_sectionPrintTitle
 */
class m180901_131243_sectionPrintTitle extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('consultationSettingsMotionSection', 'printTitle', 'TINYINT NOT NULL DEFAULT 1');
        $this->update('consultationSettingsMotionSection', ['printTitle' => 0], 'title = \'Motion Text\'');
        $this->update('consultationSettingsMotionSection', ['printTitle' => 0], 'title = \'Title\'');
        $this->update('consultationSettingsMotionSection', ['printTitle' => 0], 'title = \'Antragstext\'');
        $this->update('consultationSettingsMotionSection', ['printTitle' => 0], 'title = \'Titel\'');
        $this->update('consultationSettingsMotionSection', ['printTitle' => 0], 'title = \'Text\'');
        $this->update('consultationSettingsMotionSection', ['printTitle' => 0], 'title = \'Unterschrift\'');
        $this->update('consultationSettingsMotionSection', ['printTitle' => 0], 'title = \'Signature\'');
        $this->update('consultationSettingsMotionSection', ['printTitle' => 0], 'type = 0');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('consultationSettingsMotionSection', 'printTitle');
    }
}
