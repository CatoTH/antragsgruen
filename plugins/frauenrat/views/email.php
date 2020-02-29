<?php
use yii\helpers\Html;

/**
 * @var string $title
 * @var string|null $introduction
 * @var string $html
 * @var string|null $btnText
 * @var string|null $btnLink
 * @var \app\models\settings\Stylesheet|null $styles
 */

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title><?= Html::encode($title) ?></title>
    <style type="text/css">
        body, table {
            font-family: Calibri, Segoe, Optima, Arial, sans-serif;
            font-size: 14px;
            line-height: 1.33em;
            color: rgb(12, 40, 71);
        }

        h1 {
            font-size: 30px;
            text-transform: uppercase;
        }

        h2 {
            font-size: 20px;
            text-transform: uppercase;
        }

        h3, h4, h5 {
            font-size: 18px;
        }

        a {
            color: rgb(12, 40, 71);
        }

        th, td {
            padding-right: 1em;
            vertical-align: top;
        }
    </style>
</head>
<body>

<?php
echo $html;
?>

<p></p>
<p>Mit freundlichen Grüßen,<br>
    Ihre Geschäftsstelle des Deutschen Frauenrats</p>
<p><span style="color:rgb(0,113,166); font-size:20px;">////////////////////////////////////////////////////////////</span><br>
    <strong>Deutscher Frauenrat</strong><br>
    National Council of German Women`s Organizations<br>
    <br>
    Axel-Springer-Str. 54a<br>
    10117 Berlin<br>
    <br>
    Fon: &#43;49 30 204569-0<br>
    Fax: &#43;49 30 204569-44<br>
    <br>
    <a href="https://www.frauenrat.de">www.frauenrat.de</a><br>
    <a href="https://twitter.com/frauenrat">@frauenrat</a><br>
    <span style="color:rgb(0,113,166); font-size:20px;">////////////////////////////////////////////////////////////</span><br>
    Engagiert für Gleichstellung in Politik und Gesellschaft<br>
    <span style="color:rgb(0,113,166);">Deutschlands größte Frauenlobby</span></p>
</body>
</html>
