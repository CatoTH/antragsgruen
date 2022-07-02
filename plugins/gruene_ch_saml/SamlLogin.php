<?php

namespace app\plugins\gruene_ch_saml;

use app\components\LoginProviderInterface;

class SamlLogin implements LoginProviderInterface
{
    public function getId(): string
    {
        return 'gruene-ch';
    }

    public function getName(): string
    {
        return 'Grüne / Les Vert-E-S';
    }

    public function renderLoginForm(string $backUrl): string
    {
        return \Yii::$app->controller->renderPartial('@app/plugins/gruene_ch_saml/views/login', [
            'backUrl' => $backUrl
        ]);
    }
}
