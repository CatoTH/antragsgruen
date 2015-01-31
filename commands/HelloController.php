<?php

namespace app\commands;

use app\models\db\Site;
use yii\console\Controller;
use yii\helpers\Console;

class HelloController extends Controller
{
    /**
     * This command echoes what you have entered as the message.
     * @param string $message the message to be echoed.
     */
    public function actionIndex($message = 'hello world')
    {
        $this->stdout($message . "\n", Console::BOLD);
        echo $message . "\n";

        /*
        $site            = new Site();
        $site->id        = null;
        $site->title     = 'test';
        $site->subdomain = 'test';
        if ($site->save()) {
            echo "Success";
        } else {
            var_dump($site->getErrors());
        }
        */
        /*
                $cons = new Consultation();
                $cons->site_id = 1;
                $cons->title = "Test-Consultation";
                $cons->title_short = "Test";
                $cons->url_path = "test";

                if ($cons->save()) {
                    echo "Success";
                } else {
                    var_dump($cons->getErrors());
                }
        */



/*        $site = Site::findOne(1);
        echo $site->current_consultation_id . "\n";
        echo $site->current_consultation->title;
*/
        /*
        $user = new User();
        $user->id = 23;
        $user->name = 'Tester';
        $user->status = 0;
        if (!$user->save()) {
            var_dump($user->getErrors());
        }


        $site = new Site();
        $site->subdomain = 'test';
        $site->title = 'Test';
        $site->id = 7;
        if (!$site->save()) {
            var_dump($site->getErrors());
        }

*/

        /** @var Site $site */
        $site = Site::findOne(7);
        foreach ($site->admins as $admin) {
            echo $admin->name;
        }
    }
}
