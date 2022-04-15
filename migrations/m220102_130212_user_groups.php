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
            'templateId' => 'TINYINT NULL DEFAULT NULL',
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

        $this->createTable('userConsultationScreening', [
            'userId' => 'INTEGER NOT NULL',
            'consultationId' => 'INTEGER NULL DEFAULT NULL',
            'dateCreation' => 'TIMESTAMP NULL DEFAULT NULL',
        ]);
        $this->addPrimaryKey('userscreen_pk', 'userConsultationScreening', ['userId', 'consultationId']);
        $this->createIndex('userscreen_con_ix', 'userConsultationScreening', 'consultationId');
        $this->addForeignKey('userscreen_fk_user', 'userConsultationScreening', 'userId', 'user', 'id');
        $this->addForeignKey('userscreen_fk_con', 'userConsultationScreening', 'consultationId', 'consultation', 'id');

        \Yii::$app->db->schema->getTableSchema('consultationUserGroup', true);
        \Yii::$app->db->schema->getTableSchema('userGroup', true);
        \Yii::$app->db->schema->getTableSchema('site', true);
        \Yii::$app->db->schema->getTableSchema('consultation', true);
        \Yii::$app->db->schema->getTableSchema('user', true);

        $sites = \app\models\db\Site::find()->all();
        foreach ($sites as $site) {
            $adminGroup = $site->createDefaultSiteAdminGroup();
            foreach ($site->admins as $admin) {
                $adminGroup->addUser($admin);
            }

            foreach ($site->consultations as $consultation) {
                $groupAdmin = \app\models\db\ConsultationUserGroup::createDefaultGroupConsultationAdmin($consultation);
                $groupPp = \app\models\db\ConsultationUserGroup::createDefaultGroupProposedProcedure($consultation);
                $groupParticipant = \app\models\db\ConsultationUserGroup::createDefaultGroupParticipant($consultation);

                foreach ($consultation->userPrivileges as $privilege) {
                    if (!$privilege->user) {
                        continue;
                    }
                    if ($privilege->adminSuper) {
                        $groupAdmin->addUser($privilege->user);
                    } elseif ($privilege->adminProposals) {
                        $groupPp->addUser($privilege->user);
                    } else {
                        $groupParticipant->addUser($privilege->user);
                    }
                }
            }
        }
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

        $this->dropForeignKey('userscreen_fk_con', 'userConsultationScreening');
        $this->dropForeignKey('userscreen_fk_user', 'userConsultationScreening');
        $this->dropIndex('userscreen_con_ix', 'userConsultationScreening');
        $this->dropPrimaryKey('userscreen_pk', 'userConsultationScreening');
        $this->dropTable('userConsultationScreening');
    }
}
