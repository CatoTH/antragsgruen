<?php

namespace app\models\db;

use app\components\RSSExporter;

interface IRSSItem
{
    public function addToFeed(RSSExporter $feed): void;
    public function getDate(): string;
}
