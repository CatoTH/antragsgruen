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

$bodyFont = Html::encode($styles ? $styles->getValue('bodyFont', \app\models\settings\Stylesheet::DEFAULTS_LAYOUT_CLASSIC) : 'sans-serif');

?><!doctype html>
<html>
<head>
    <meta name="viewport" content="width=device-width">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title><?= Html::encode($title) ?></title>
    <style>
        @media only screen and (max-width: 620px) {
            table[class=body] h1 {
                font-size: 28px !important;
                margin-bottom: 10px !important;
            }

            table[class=body] p,
            table[class=body] ul,
            table[class=body] ol,
            table[class=body] td,
            table[class=body] span,
            table[class=body] a {
                font-size: 16px !important;
            }

            table[class=body] .wrapper,
            table[class=body] .article {
                padding: 10px !important;
            }

            table[class=body] .content {
                padding: 0 !important;
            }

            table[class=body] .container {
                padding: 0 !important;
                width: 100% !important;
            }

            table[class=body] .main {
                border-left-width: 0 !important;
                border-radius: 0 !important;
                border-right-width: 0 !important;
            }

            table[class=body] .btn table {
                width: 100% !important;
            }

            table[class=body] .btn a {
                width: 100% !important;
            }

            table[class=body] .img-responsive {
                height: auto !important;
                max-width: 100% !important;
                width: auto !important;
            }
        }

        .apple-link a {
            color: inherit !important;
            font-family: inherit !important;
            font-size: inherit !important;
            font-weight: inherit !important;
            line-height: inherit !important;
            text-decoration: none !important;
        }

        #MessageViewBody a {
            color: inherit;
            text-decoration: none;
            font-size: inherit;
            font-family: inherit;
            font-weight: inherit;
            line-height: inherit;
        }

        .btn-primary table td:hover {
            background-color: #34495e !important;
        }

        .btn-primary a:hover {
            background-color: #34495e !important;
            border-color: #34495e !important;
        }

        }
    </style>
</head>
<body style="background-color: #ececec; font-family: <?= $bodyFont ?>; -webkit-font-smoothing: antialiased; font-size: 14px; line-height: 1.4; margin: 0; padding: 0; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;">
<table border="0" cellpadding="0" cellspacing="0" class="body"
       style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; background-color: #ececec;">
    <tr>
        <td style="font-family: <?= $bodyFont ?>; font-size: 14px; vertical-align: top;">&nbsp;</td>
        <td class="container"
            style="font-family: <?= $bodyFont ?>; font-size: 14px; vertical-align: top; display: block; margin: 0 auto; max-width: 580px; padding: 10px; width: 580px;">
            <div class="content" style="box-sizing: border-box; display: block; margin: 0 auto; max-width: 580px; padding: 10px;">
                <?php if (isset($introduction) && $introduction) { ?>
                <span class="preheader"
                      style="color: transparent; display: none; height: 0; max-height: 0; max-width: 0; opacity: 0; overflow: hidden; mso-hide: all; visibility: hidden; width: 0;"><?= Html::encode($introduction) ?></span>
                <?php } ?>
                <table class="main"
                       style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; background: #ffffff; border-radius: 3px;">

                    <tr>
                        <td class="wrapper" style="font-family: <?= $bodyFont ?>; font-size: 14px; vertical-align: top; box-sizing: border-box; padding: 20px;">
                            <table border="0" cellpadding="0" cellspacing="0"
                                   style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; table-layout: fixed;">
                                <tr>
                                    <td style="font-family: <?= $bodyFont ?>; font-size: 14px; vertical-align: top; overflow-wrap: break-word;">
                                        <?php
                                        $html = str_replace('<p>', '<p style="font-family: ' . $bodyFont . '; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">', $html);
                                        echo $html;
                                        ?>
                                        <?php if (isset($btnLink) && $btnLink && isset($btnText) && $btnText) { ?>
                                            <table border="0" cellpadding="0" cellspacing="0" class="btn btn-primary"
                                                   style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; box-sizing: border-box; table-layout: fixed;">
                                                <tbody>
                                                <tr>
                                                    <td align="left"
                                                        style="font-family: <?= $bodyFont ?>; font-size: 14px; vertical-align: top; padding-bottom: 15px;">
                                                        <table border="0" cellpadding="0" cellspacing="0"
                                                               style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: auto;">
                                                            <tbody>
                                                            <tr>
                                                                <td style="font-family: <?= $bodyFont ?>; font-size: 14px; vertical-align: top; background-color: #3498db; border-radius: 5px; text-align: center;">
                                                                    <a href="<?= Html::encode($btnLink) ?>" target="_blank"
                                                                       style="display: inline-block; color: #ffffff; background-color: #3498db; border: solid 1px #3498db; border-radius: 5px; box-sizing: border-box; cursor: pointer; text-decoration: none; font-size: 14px; font-weight: bold; margin: 0; padding: 12px 25px; text-transform: capitalize; border-color: #3498db;"><?= Html::encode($btnText) ?></a>
                                                                </td>
                                                            </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        <?php } ?>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <?php
                if (Yii::t('base', 'email_footer') !== '' && Yii::t('base', 'email_footer') !== 'email_footer') {
                    ?>
                    <div class="footer" style="clear: both; Margin-top: 10px; text-align: center; width: 100%;">
                        <table border="0" cellpadding="0" cellspacing="0"
                               style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;">
                            <tr>
                                <td class="content-block"
                                    style="font-family: <?= $bodyFont ?>; vertical-align: top; padding-bottom: 10px; padding-top: 10px; font-size: 12px; color: #999999; text-align: center;">
                                    <?= Yii::t('base', 'email_footer') ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <?php
                }
                ?>
            </div>
        </td>
        <td style="font-family: <?= $bodyFont ?>; font-size: 14px; vertical-align: top;">&nbsp;</td>
    </tr>
</table>
</body>
</html>
