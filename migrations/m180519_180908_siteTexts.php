<?php

use yii\db\Migration;

/**
 * Class m180519_180908_siteTexts
 */
class m180519_180908_siteTexts extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('consultationText', 'siteId', 'INT DEFAULT NULL AFTER consultationId');
        $this->createIndex('consultation_text_site', 'consultationText', 'siteId', false);
        $this->addForeignKey('fk_consultation_text_site', 'consultationText', 'siteId', 'site', 'id', 'CASCADE', 'CASCADE');

        $texts = \app\models\db\ConsultationText::findAll(['siteId' => null]);
        foreach ($texts as $text) {
            if ($text->consultation) {
                $text->siteId = $text->consultation->siteId;
                $text->save();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_consultation_text_site', 'consultationText');
        $this->dropColumn('consultationText', 'siteId');
    }
}
