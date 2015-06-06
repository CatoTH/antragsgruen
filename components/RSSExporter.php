<?php

namespace app\components;

use yii\helpers\Html;

class RSSExporter
{
    private $image       = null;
    private $title       = null;
    private $language    = null;
    private $baseLink    = null;
    private $feedLink    = null;
    private $description = null;


    private $entries = [];

    /**
     * @param string $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @param string $baseLink
     */
    public function setBaseLink($baseLink)
    {
        $this->baseLink = $baseLink;
    }

    /**
     * @param string $feedLink
     */
    public function setFeedLink($feedLink)
    {
        $this->feedLink = $feedLink;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @param string $link
     * @param string $title
     * @param string $description
     * @param string $date
     */
    public function addEntry($link, $title, $description, $date)
    {
        $this->entries[] = [
            'link'        => $link,
            'title'       => $title,
            'description' => $description,
            'date'        => $date,
        ];
    }

    /**
     *
     */
    public function getFeed()
    {
        $path = parse_url($this->baseLink);
        $rootUri = $path['scheme'] . '://' . $path['host'];
        $return   = '';

        $return .= '<?xml version="1.0" encoding="UTF-8"?>';
        $return .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"><channel>';
        $return .= '<atom:link href="' . Html::encode($this->feedLink) . '" rel="self" type="application/rss+xml" />
            <title>' . Html::encode($this->title) . '</title>
            <link>' . Html::encode($this->feedLink) . '</link>
            <description>' . Html::encode($this->description) . '</description>
            <image>
                <url>' . Html::encode($rootUri) . '/css/img/logo.png</url>
                <title>' . Html::encode($this->title) . '</title>
                <link>' . Html::encode($this->feedLink) . '</link>
            </image>';
        foreach ($this->entries as $dat) {
            $return .= '<item>
                        <title>' . Html::encode($dat['title']) . '</title>
                        <link>' . Html::encode($dat['link']) . '</link>
                        <guid>' . Html::encode($dat['link']) . '</guid>
                        <description><![CDATA[';
            $return .= $dat['description'];
            $return .= ']]></description>
                        <pubDate>' . date(str_replace("y", "Y", DATE_RFC822), $dat['date']) . '</pubDate>
                    </item>';
        }
        $return .= '</channel></rss>';

        return $return;
    }
}
