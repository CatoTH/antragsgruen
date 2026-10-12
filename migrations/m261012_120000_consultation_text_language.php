<?php

declare(strict_types=1);

use yii\db\Migration;

class m261012_120000_consultation_text_language extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp(): void
    {
        $this->addColumn('consultationText', 'language', 'VARCHAR(20) DEFAULT NULL AFTER textId');
        $this->dropIndex('consultation_text_unique', 'consultationText');
        $this->createIndex('consultation_text_unique', 'consultationText', ['category', 'textId', 'consultationId', 'language'], true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): void
    {
        $this->delete('consultationText', ['not', ['language' => null]]);
        $this->dropIndex('consultation_text_unique', 'consultationText');
        $this->createIndex('consultation_text_unique', 'consultationText', ['category', 'textId', 'consultationId'], true);
        $this->dropColumn('consultationText', 'language');
    }
}
