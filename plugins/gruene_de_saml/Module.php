<?php

declare(strict_types=1);

namespace app\plugins\gruene_de_saml;

use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    public const AUTH_KEY_GROUPS = 'gruenesnetz';
    public const AUTH_KEY_USERS = 'openid';
}
