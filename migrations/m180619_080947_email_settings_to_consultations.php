<?php

use yii\db\Migration;

/**
 * Class m180619_080947_email_settings_to_consultations
 */
class m180619_080947_email_settings_to_consultations extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        /** @var \app\models\db\Site[] $consultations */
        $sites = \app\models\db\Site::find()->all();
        foreach ($sites as $site) {
            $siteSettings = $site->getSettings();
            foreach ($site->consultations as $consultation) {
                $conSettings = $consultation->getSettings();
                $conSettings->emailReplyTo = $siteSettings->emailReplyTo;
                $conSettings->emailFromName = $siteSettings->emailFromName;
                $consultation->setSettings($conSettings);
                $consultation->save();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m180619_080947_email_settings_to_consultations cannot be reverted.\n";

        return false;
    }
}
