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
        $client->post('/' . urlencode($object->getDomain()) . '/' . urlencode($object->getPublishChannel()) . '/', [
            'form_params' => ['data' => json_encode($object)]
        ]);

        // @TODO Error handling
    }

    /**
     * @param string $consultationId
     * @param string $channel
     * @param string $objectId
     */
    public static function deleteObject($consultationId, $channel, $objectId)
    {
        $client = static::getClient();
        $client->delete('/' . urlencode($consultationId) . '/' . urlencode($channel) . '/' . urlencode($objectId));

        // @TODO Error handling
    }
}
