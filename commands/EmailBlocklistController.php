<?php

declare(strict_types=1);

namespace app\commands;

use app\models\db\EMailBlocklist;
use yii\console\Controller;

/**
 * Add or remove e-mail-addresses from the blocklist
 */
class EmailBlocklistController extends Controller
{
    /**
     * Add an e-mail-address to the list
     */
    public function actionAdd(string $email): void
    {
        if (EMailBlocklist::isBlocked($email)) {
            $this->stderr("E-Mail-Address is already blocked");
            return;
        }

        EMailBlocklist::addToBlocklist($email);
        $this->stderr("E-Mail-Address added");
    }

    /**
     * Remove an e-mail-address from the list
     */
    public function actionRemove(string $email): void
    {
        if (!EMailBlocklist::isBlocked($email)) {
            $this->stderr("E-Mail-Address is not blocked");
            return;
        }

        EMailBlocklist::removeFromBlocklist($email);
        $this->stderr("E-Mail-Address removed");
    }
}
