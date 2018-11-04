<?php

namespace app\async\yii;

use app\async\models\TransferrableChannelObject;
use app\models\settings\AntragsgruenApp;
use GuzzleHttp\Client;

class SwooleClient
{
    /**
     * @return Client
     */
    protected static function getClient()
    {
        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;
        return new Client(['base_uri' => 'http://127.0.0.1:' . $params->asyncConfig['port-internal']]);
    }

    /**
     * @param TransferrableChannelObject $object
     */
    public static function publishObject(TransferrableChannelObject $object)
    {
        $client = static::getClient();
        $client->post(
            '/' . urlencode($object->getSubdomain()) . '/' . urlencode($object->getPath()) .
            '/' . urlencode($object->getPublishChannel()) . '/',
            [
                'form_params' => ['data' => json_encode($object)]
            ]
        );

        // @TODO Error handling
    }

    /**
     * @param string $subdomain
     * @param string $path
     * @param string $channel
     * @param string $objectId
     */
    public static function deleteObject(string $subdomain, string $path, string $channel, string $objectId)
    {
        $client = static::getClient();
        $client->delete('/' . urlencode($subdomain) . '/' . urlencode($path) . '/' .
            urlencode($channel) . '/' . urlencode($objectId));

        // @TODO Error handling
    }
}
