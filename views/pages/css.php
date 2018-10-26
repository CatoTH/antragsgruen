<?php

$css = '
@charset "UTF-8";

@import "variables";

$OpenSansPath: "../fonts/OpenSans/fonts";
@import "../fonts/OpenSans/open-sans.scss";
$veraSansPath: "../fonts/BitstreamVeraSansMono";
@import "../fonts/BitstreamVeraSansMono/verasans";

$colorLinksLight: #6d7e00;
$colorLinks: #6d7e00;
$linkTextDecoration: none;
$colorDelLink: #FF7777;
$colorMagenta: rgb(226, 0, 122);
$brand-primary: $colorMagenta;
$text-color: rgb(72, 70, 73);
$btn-success-bg: #2c882c;

$table-border-color: $colorGreenLight;
$headingFont: "Open Sans", sans-serif;
$headingPrimaryText: rgb(255, 255, 255);
$headingPrimaryBackground: rgb(40, 95, 25);
$headingSecondaryText: rgb(255, 255, 255);
$headingSecondaryBackground: rgb(175, 203, 8);
$headingTertiaryText: black;
$headingTertiaryBackground: rgb(27, 74, 251);

$menuFont: "Open Sans", sans-serif;
$menuLink: #6d7e00;
$menuActive: rgb(115, 115, 115);

$sidebarBackground: $colorMagenta;
$sidebarActionFont: "Open Sans", sans-serif;

$bookmarkAmendmentBackground: $colorGreenLight;
$bookmarkCommentColor: $colorMagenta;

$bodyFont: "Segoe UI", Frutiger, "Frutiger Linotype", "Dejavu sans", "Helvetica Neue", Helvetica, Arial, sans-serif;
$deadlineCircleFont: "Open Sans", sans-serif;
$buttonFont: "Open Sans", sans-serif;
$motionFixedFont: "VeraMono", Consolas, Courier, sans-serif;
$motionFixedFontColor: #222;
$motionFixedWidth: 740px;
$motionStdFontSize: 14px;
$inlineAmendmentPreambleHeight: 30px;
$inlineAmendmentPreambleColor: $colorMagenta;
$createMotionBtnColor: $colorMagenta;

@import "bootstrap";
@import "fontello";
@import "wizard";
@import "helpers";
@import "elements";
@import "bootstrap_fuelux_overwrites";
@import "base_layout";
@import "contentpage";
@import "consultation_motion_list";
@import "motions";
@import "proposed_procedure";
@import "styles";
@import "sidebar";
@import "user_pages";

html {
  background: url("../img/wallpaper.jpg") repeat scroll 0 0 transparent;
}

body {
  background: url("../img/backgroundGradient.png") repeat-x scroll 0 0 transparent;
}

.logoImg {
  display: block;
  width: 377px;
  height: 55px;
  background-image: url("../img/logo.png");
  @media screen and (max-width: 479px) {
    width: 300px;
    height: 44px;
    background-size: 300px 44px;
  }
}
';

$scss = new \Leafo\ScssPhp\Compiler();
$scss->addImportPath(\Yii::$app->basePath . '/web/css/');
echo $scss->compile($css);
