<?php

use yii\db\Migration;

/**
 * Class m180621_113721_login_settings_to_consultation
 */
class m180621_113721_login_settings_to_consultation extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        /** @var \app\models\db\Site[] $sites */
        $sites = \app\models\db\Site::find()->all();
        foreach ($sites as $site) {
            $siteSettings = $site->getSettings();
            foreach ($site->consultations as $consultation) {
                $conSettings                      = $consultation->getSettings();
                $conSettings->forceLogin          = $siteSettings->forceLogin;
                $conSettings->managedUserAccounts = $siteSettings->managedUserAccounts;
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
        echo "m180621_113721_login_settings_to_consultation cannot be reverted.\n";

        return false;
    }
}
