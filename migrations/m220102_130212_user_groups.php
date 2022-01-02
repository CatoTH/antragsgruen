<?php

use yii\db\Migration;

class m220102_130212_user_groups extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('user', 'dateLastLogin', 'TIMESTAMP NULL DEFAULT NULL AFTER dateCreation');

        $this->createTable('consultationUserGroup', [
            'id' => 'INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'externalId' => 'VARCHAR(150) NULL DEFAULT NULL',
            'title' => 'VARCHAR(150) NOT NULL',
            'consultationId' => 'INTEGER NULL DEFAULT NULL',
            'siteId' => 'INTEGER NULL DEFAULT NULL',
            'selectable' => 'TINYINT NOT NULL DEFAULT 1',
            'permissions' => 'TEXT NULL DEFAULT NULL',
        ]);
        $this->createIndex('ix_usergroup_external_id', 'consultationUserGroup', 'externalId', true);
        $this->addForeignKey('usergroup_fk_consultation', 'consultationUserGroup', 'consultationId', 'consultation', 'id');
        $this->addForeignKey('usergroup_fk_site', 'consultationUserGroup', 'siteId', 'site', 'id');

        $this->createTable('userGroup', [
            'userId' => 'INTEGER NOT NULL',
            'groupId' => 'INTEGER NOT NULL'
        ]);
        $this->addPrimaryKey('usergroup_pk', 'userGroup', ['userId', 'groupId']);
        $this->createIndex('usergroup_group_ix', 'userGroup', 'groupId');
        $this->addForeignKey('usergroup_fk_user', 'userGroup', 'userId', 'user', 'id');
        $this->addForeignKey('usergroup_fk_group', 'userGroup', 'groupId', 'consultationUserGroup', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('usergroup_fk_group', 'userGroup');
        $this->dropForeignKey('usergroup_fk_user', 'userGroup');
        $this->dropTable('userGroup');

        $this->dropForeignKey('usergroup_fk_consultation', 'consultationUserGroup');
        $this->dropForeignKey('usergroup_fk_site', 'consultationUserGroup');
        $this->dropTable('consultationUserGroup');

        $this->dropColumn('user', 'dateLastLogin');
    }
}
