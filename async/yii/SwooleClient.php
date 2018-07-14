<?php

namespace app\async\yii;

use app\async\models\TransferrableChannelObject;
use GuzzleHttp\Client;

class SwooleClient
{
    /**
     * @return \Swoole\Http\Client
     */
    protected static function getClient()
    {
        $cli = new \Swoole\Http\Client('127.0.0.1', 9501);
        $cli->setHeaders(['User-Agent' => 'swoole-http-client']);
        //$cli->setCookies(array('test' => 'value'));
        return $cli;
    }

    /**
     * @param TransferrableChannelObject $object
     */
    public static function publishObject(TransferrableChannelObject $object)
    {
        $client = new Client(['base_uri' => 'http://127.0.0.1:9501']);
        $client->post('/' . $object->getConsultation() . '/' . $object->getPublishChannel() . '/', [
            'form_params' => ['data' => $object->toJSON()]
        ]);

        // @TODO Error handling
    }
}
