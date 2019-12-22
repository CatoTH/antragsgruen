<?php

use yii\db\Migration;

/**
 * Class m191222_135810_lualatex
 */
class m191222_135810_lualatex extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $templates = \app\models\db\TexTemplate::find()->all();
        foreach ($templates as $template) {
            /** @var \app\models\db\TexTemplate $template */
            if (strpos($template->texLayout, '\usepackage{pdfpages}') === false) {
                $template->texLayout = str_replace(
                    '\usepackage{graphicx}',
                    '\usepackage{graphicx}' . "\n" . '\usepackage{pdfpages}',
                    $template->texLayout
                );
                $template->save();
            }
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m191222_135810_lualatex cannot be reverted.\n";

        return false;
    }
}
