<?php

use yii\db\Expression;
use yii\db\Migration;

class m151021_084634_supporter_organization_contact_person extends Migration
{
    /*
    public function up()
    {

    }

    public function down()
    {
        echo "m151021_084634_supporter_organization_contact_person cannot be reverted.\n";

        return false;
    }
    */

    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        $orga = IntVal(\app\models\db\AmendmentSupporter::PERSON_ORGANIZATION);
        $this->update('amendmentSupporter', ['organization' => new Expression('name')], 'personType = ' . $orga);
        $this->update('amendmentSupporter', ['name' => ''], 'personType = ' . $orga);

        $orga = IntVal(\app\models\db\MotionSupporter::PERSON_ORGANIZATION);
        $this->update('motionSupporter', ['organization' => new Expression('name')], 'personType = ' . $orga);
        $this->update('motionSupporter', ['name' => ''], 'personType = ' . $orga);
    }

    public function safeDown()
    {
        echo "m151021_084634_supporter_organization_contact_person cannot be reverted.\n";

        return false;
    }
}
