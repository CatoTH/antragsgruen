<?php

namespace app\plugins\gruene_de_saml\commands;

use app\models\db\ConsultationUserGroup;
use app\plugins\gruene_de_saml\Module;

class ImportUserGroupsController extends \yii\console\Controller
{
    public $defaultAction = 'import-groups';

    /**
     * Imports site-wide user groups
     */
    public function actionImportGroups(string $filename): int
    {
        $groups = json_decode((string)file_get_contents($filename), true, 512, JSON_THROW_ON_ERROR);
        foreach ($groups as $group) {
            $externalId = Module::AUTH_KEY_GROUPS . ':' . $group['schluessel'];

            $internalGroup = ConsultationUserGroup::findOne(['externalId' => $externalId]);
            if (!$internalGroup) {
                $internalGroup = new ConsultationUserGroup();
                $internalGroup->siteId = null;
                $internalGroup->consultationId = null;
                $internalGroup->externalId = $externalId;
                $internalGroup->permissions = '';
                $internalGroup->selectable = 1;
            }
            $internalGroup->title = $group['typ'] . ' ' . $group['name'];
            $internalGroup->templateId = null;
            $internalGroup->save();
        }

        return 0;
    }
}
