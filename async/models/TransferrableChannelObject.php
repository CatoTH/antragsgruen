<?php

namespace app\async\models;

abstract class TransferrableChannelObject extends TransferrableObject
{
    /** @return int */
    abstract public function getConsultation();

    /** @return string */
    abstract public function getPublishChannel();
}
