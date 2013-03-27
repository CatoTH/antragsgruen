<?php

/**
 * @var VeranstaltungController $this
 * @var int $veranstaltung_id
 * @var string $feed_title
 * @var array $data
 * @var Sprache $sprache
 * @var string $feed_description
 */

$this->layout=false;
header('Content-type: application/xml; charset=UTF-8');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
	<channel>
		<atom:link href="<?php echo CHtml::encode(Yii::app()->getBaseUrl(true) . Yii::app()->request->requestUri); ?>" rel="self" type="application/rss+xml" />
		<title><?php echo CHtml::encode($feed_title); ?></title>
		<link><?php echo CHtml::encode(Yii::app()->getBaseUrl(true)); ?></link>
		<description><?php echo CHtml::encode($feed_description); ?></description>
		<image>
			<url><?php echo CHtml::encode(Yii::app()->getBaseUrl(true)); ?>/css/img/logo.png</url>
			<title><?php echo CHtml::encode($feed_title); ?></title>
			<link><?php echo CHtml::encode(Yii::app()->getBaseUrl(true)); ?></link>
		</image>
		<?php foreach ($data as $dat) { ?>
		<item>
			<title><?php echo CHtml::encode($dat["title"]); ?></title>
			<link><?php echo CHtml::encode($dat["link"]); ?></link>
			<guid><?php echo CHtml::encode($dat["link"]); ?></guid>
			<description><![CDATA[<?php
				echo $dat["content"];
			?>]]></description>
			<pubDate><?php echo date(str_replace("y", "Y", DATE_RFC822), $dat["dateCreated"]); ?></pubDate>
		</item>
		<? } ?>
	</channel>
</rss>
<?php

Yii::app()->end();

?>