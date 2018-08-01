<?php

namespace app\async\models;

use app\models\exceptions\Internal;

abstract class TransferrableChannelObject extends TransferrableObject
{
    public static $CHANNEL_CLASSES = [
        'amendments' => Amendment::class,
        'motions'    => Motion::class,
    ];

    /** @return string */
    abstract public function getId();

    /** @return int */
    abstract public function getDomain();

    /** @return string */
    public function getPublishChannel()
    {
        foreach (static::$CHANNEL_CLASSES as $channel => $class) {
            if ($class === static::class) {
                return $channel;
            }
        }
        throw new Internal('Unregistered class: ' . static::class);
    }
}
