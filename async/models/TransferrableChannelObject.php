<?php

namespace app\async\models;

use app\models\db\Consultation;
use app\models\exceptions\Internal;

abstract class TransferrableChannelObject extends TransferrableObject
{
    /** @var TransferrableChannelObject[] */
    public static $CHANNEL_CLASSES = [
        'amendments' => Amendment::class,
        'motions'    => Motion::class,
    ];

    /** @var string */
    protected $subdomain;
    protected $path;

    /** @return string */
    abstract public function getId();

    /**
     * @return string
     */
    public function getSubdomain()
    {
        return $this->subdomain;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param Consultation $consultation
     * @return TransferrableChannelObject[]
     */
    public static function getCollection(Consultation $consultation)
    {
        return [];
    }

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
