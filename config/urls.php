<?php

/**
 * @var app\models\settings\AntragsgruenApp $params
 */

$dom       = $params->domainSubdomain;
$domv      = $dom . '<consultationPath:[\w_-]+>/';
$domadmin  = $domv . 'admin/';
$dommotion = $domv . 'motion/<motionSlug:[^\/]+>';
$domamend  = $domv . 'motion/<motionSlug:[^\/]+>/amendment/<amendmentId:\d+>';

$consultationPaths = 'help|search|savetextajax|maintainance|notifications|activitylog|shariffbackend';
$consultationPaths .= '|amendmentpdfs|feedall|feedmotions|feedamendments|feedcomments';
$motionPaths = 'createconfirm|edit|pdf|odt|plainhtml|consolidated|mergeamendments|mergeamendmentconfirm|withdraw';
$motionPaths .= '|viewimage|viewpdf|embeddedpdf';
$amendPaths = 'pdf|odt|createconfirm|edit|withdraw';
$userPaths  = 'login|logout|confirmregistration|loginbyredirecttoken|loginwurzelwerk|emailblacklist|recovery';
$userPaths .= '|loginsaml|logoutsaml|consultationaccesserror|myaccount|emailchange';
$domPlainPaths = 'legal|privacy|help|password|billing|createsite|savetextajax|siteconfig|antragsgrueninit';
$domPlainPaths .= '|antragsgrueninitdbtest|paymentadmin';
$adminMotionPaths    = 'index|type|typecreate|listall|excellist|odslist|pdfziplist|odtziplist|odslistall|openslides';
$adminAmendmentPaths = 'index|excellist|odslist|pdflist|pdfziplist|odtziplist|openslides';
$adminPaths          = 'consultation|consultationextended|translation|siteaccess|siteconsultations|openslidesusers';
$adminPaths .= '|todo';

$urlRules = [
    $domadmin . ''                                              => 'admin/index',
    $domadmin . '<_a:(' . $adminPaths . ')>'                    => 'admin/index/<_a>',
    $domadmin . 'motion/update/<motionId:\d+>'                  => 'admin/motion/update',
    $domadmin . 'motion/<_a:(' . $adminMotionPaths . ')>'       => 'admin/motion/<_a>',
    $domadmin . 'amendment'                                     => 'admin/amendment',
    $domadmin . 'amendment/update/<amendmentId:\d+>'            => 'admin/amendment/update',
    $domadmin . 'amendment/<_a:(' . $adminAmendmentPaths . ')>' => 'admin/amendment/<_a>',
    $domadmin . 'motionComments'                                => 'admin/motionComments',
    $domadmin . 'motionComments/<_a:(index)>'                   => 'admin/motionComments/<_a>',
    $domadmin . 'amendmentComments'                             => 'admin/amendmentComments',
    $domadmin . 'amendmentComments/<_a:(index)>'                => 'admin/amendmentComments/<_a>',
    $domadmin . 'texts'                                         => 'admin/texts',
    $domadmin . 'texts/<_a:(index|update|delete)>'              => 'admin/texts/<_a>',
    $domadmin . 'namespacedAccounts'                            => 'admin/index/namespacedAccounts',
    $domadmin . 'ae_pdf_list'                                   => 'admin/index/aePDFList',
    $domadmin . 'admins'                                        => 'admin/index/admins',
    $domadmin . 'consultations'                                 => 'admin/index/consultations',

    $dom . '<_a:(' . $userPaths . ')>' => 'user/<_a>',
    $dom . 'checkemail'                => 'user/ajaxIsEmailRegistered',
    $dom . '<_a:(legal|privacy)>'      => 'consultation/<_a>',

    $domv . '<_a:(' . $consultationPaths . ')>' => 'consultation/<_a>',
    $domv . 'motion/pdfcollection'              => 'motion/pdfcollection',
    $domv . 'motion/create'                     => 'motion/create',
    $domv . 'amendment/pdfcollection'           => 'amendment/pdfcollection',
    $dommotion                                  => 'motion/view',
    $dommotion . '/<_a:(' . $motionPaths . ')>' => 'motion/<_a>',
    $domamend                                   => 'amendment/view',
    $domamend . '/<_a:(' . $amendPaths . ')>'   => 'amendment/<_a>',
    $dommotion . '/amendment/create'            => 'amendment/create',
    $domv                                       => 'consultation/index',
];

if ($params->domainPlain != $params->domainSubdomain) {
    $urlRules[$dom] = 'consultation/index';
}

if ($params->multisiteMode) {
    $domp     = trim($params->domainPlain, '/');
    $urlRules = array_merge(
        [
            $domp                                    => 'manager/index',
            $domp . '/<_a:(' . $domPlainPaths . ')>' => 'manager/<_a>',
        ],
        $urlRules
    );

    if ($params->prependWWWToSubdomain) {
        foreach ($urlRules as $key => $val) {
            $urlRules[str_replace('http://', 'http://www.', $key)]   = $val;
            $urlRules[str_replace('https://', 'https://www.', $key)] = $val;
        }
    }
}

return $urlRules;
