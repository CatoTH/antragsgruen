<?php

use yii\db\Migration;

class m200107_113326_motionSectionSettings extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('consultationSettingsMotionSection', 'settings', 'TEXT DEFAULT NULL');

        $templates = \app\models\db\TexTemplate::find()->all();
        foreach ($templates as $template) {
            /** @var \app\models\db\TexTemplate $template */
            if (strpos($template->texLayout, 'adjustbox') === false) {
                $template->texLayout = str_replace(
                    '\usepackage{pdfpages}',
                    '\usepackage{pdfpages}' . "\n" . '\usepackage[export]{adjustbox}',
                    $template->texLayout
                );
                $template->save();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('consultationSettingsMotionSection', 'settings');
    }
}
