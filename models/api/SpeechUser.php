<?php

declare(strict_types=1);

namespace app\models\api;

use app\components\CookieUser;
use app\models\db\User;
use app\models\layoutHooks\Layout;

class SpeechUser implements \JsonSerializable
{
    public bool $loggedIn;
    public ?int $id;
    public ?string $token;
    public string $name;

    public function __construct(?User $user, ?CookieUser $cookieUser)
    {
        if ($user) {
            $this->loggedIn = true;
            $this->id = $user->id;
            $this->token = null;
            $this->name = self::getFormattedUserName($user);
        } elseif ($cookieUser) {
            $this->loggedIn = false;
            $this->id = null;
            $this->token = $cookieUser->userToken;
            $this->name = $cookieUser->name;
        } else {
            $this->loggedIn = false;
            $this->id = null;
            $this->token = null;
            $this->name = '';
        }
    }

    public static function getFormattedUserName(User $user): string
    {
        if ($user->organization) {
            $name = $user->name . ' (' . $user->organization . ')';
        } else {
            $name = $user->name;
        }
        return Layout::getFormattedUsername($name, $user);
    }

    public function jsonSerialize(): array
    {
        return [
            'logged_in' => $this->loggedIn,
            'id'        => $this->id,
            'token'     => $this->token,
            'name'      => $this->name,
        ];
    }
}

