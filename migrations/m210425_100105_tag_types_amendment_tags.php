<?php

use yii\db\Migration;

/**
 * Class m210425_100105_tag_types_amendment_tags
 */
class m210425_100105_tag_types_amendment_tags extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('consultationSettingsTag', 'type', 'TINYINT NOT NULL DEFAULT 0 AFTER consultationId');

        $this->createTable('amendmentTag', [
            'amendmentId' => 'INTEGER NOT NULL',
            'tagId' => 'INTEGER NOT NULL'
        ]);
        $this->addPrimaryKey('amendment_tag_pk', 'amendmentTag', ['amendmentId', 'tagId']);
        $this->createIndex('amendment_tag_fk_tagIdx', 'amendmentTag', 'tagId');
        $this->addForeignKey('amendment_tag_fk_amendment', 'amendmentTag', 'amendmentId', 'amendment', 'id');
        $this->addForeignKey('amendment_tag_fk_tag', 'amendmentTag', 'tagId', 'consultationSettingsTag', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('amendment_tag_fk_tag', 'amendmentTag');
        $this->dropForeignKey('amendment_tag_fk_amendment', 'amendmentTag');
        $this->dropIndex('amendment_tag_fk_tagIdx', 'amendmentTag');
        $this->dropPrimaryKey('amendment_tag_pk', 'amendmentTag');
        $this->dropTable('amendmentTag');

        $this->dropColumn('consultationSettingsTag', 'type');
    }
}
