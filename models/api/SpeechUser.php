<?php

namespace app\models\api;

use app\components\CookieUser;
use app\models\db\User;

class SpeechUser implements \JsonSerializable
{
    /** @var bool */
    public $loggedIn;

    /** @var null|int */
    public $id;

    /** @var null|string */
    public $token;

    /** @var string */
    public $name;

    public function __construct(?User $user, ?CookieUser $cookieUser)
    {
        if ($user) {
            $this->loggedIn = true;
            $this->id = $user->id;
            $this->token = null;
            if ($user->organization) {
                $this->name = $user->name . ' (' . $user->organization . ')';
            } else {
                $this->name = $user->name;
            }
        } elseif ($cookieUser) {
            $this->loggedIn = true;
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

