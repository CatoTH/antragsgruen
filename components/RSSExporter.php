<?php

namespace app\components;

use yii\helpers\Html;

class RSSExporter
{
    private ?string $image       = null;
    private ?string $title       = null;
    private ?string $baseLink    = null;
    private ?string $feedLink    = null;
    private ?string $description = null;

    /** @var array<array{link: string, title: string, author: string, description: string, date: int}> */
    private array $entries = [];

    public function setImage(string $image): void
    {
        $this->image = $image;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setBaseLink(string $baseLink): void
    {
        $this->baseLink = $baseLink;
    }

    public function setFeedLink(string $feedLink): void
    {
        $this->feedLink = $feedLink;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function addEntry(string $link, string $title, string $author, string $description, int $date): void
    {
        $this->entries[] = [
            'link'        => $link,
            'title'       => $title,
            'author'      => $author,
            'description' => $description,
            'date'        => $date,
        ];
    }

    public function getFeed(): string
    {
        $path = parse_url($this->baseLink);
        $rootUri = $path['scheme'] . '://' . $path['host'];

        $return = '<?xml version="1.0" encoding="UTF-8"?>';
        $return .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"><channel>';
        $return .= '<atom:link href="' . Html::encode($this->feedLink) . '" rel="self" type="application/rss+xml" />
            <title>' . Html::encode($this->title) . '</title>
            <link>' . Html::encode($this->feedLink) . '</link>
            <description>' . Html::encode($this->description ?: '') . '</description>
            <image>
                <url>' . Html::encode($rootUri . $this->image) . '</url>
                <title>' . Html::encode($this->title) . '</title>
                <link>' . Html::encode($this->feedLink) . '</link>
            </image>';
        foreach ($this->entries as $dat) {
            $return .= '<item>
                        <title>' . Html::encode($dat['title']) . '</title>
                        <link>' . Html::encode(UrlHelper::absolutizeLink($dat['link'])) . '</link>
                        <author>' . Html::encode($dat['author']) . '</author>
                        <guid>' . Html::encode(UrlHelper::absolutizeLink($dat['link'])) . '</guid>
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
