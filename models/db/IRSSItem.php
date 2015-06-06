<?php

namespace app\models\db;

use app\components\RSSExporter;

interface IRSSItem
{
    /**
     * @param RSSExporter $feed
     */
    public function addToFeed(RSSExporter $feed);

    /**
     * @return string
     */
    public function getDate();
}
