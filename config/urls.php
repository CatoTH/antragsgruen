<?php

/**
 * @var app\models\settings\AntragsgruenApp $params
 */

$dom       = $params->domainSubdomain;
$domv      = $dom . '<consultationPath:[\w_-]+>/';
$domadmin  = $domv . 'admin/';
$dommotion = $domv . 'motion/<motionId:\d+>';
$domamend  = $domv . 'motion/<motionId:\d+>/amendment/<amendmentId:\d+>';

$consultationPaths = 'help|search|savetextajax|pdfs|maintainance|notifications';
$consultationPaths .= '|amendmentpdfs|feedall|feedmotions|feedamendments|feedcomments';
$motionPaths         = 'create|createconfirm|edit|pdf|odt|plainhtml|mergeamendments|image';
$userPaths           = 'login|logout|confirmregistration|loginbyredirecttoken|loginwurzelwerk|unsubscribe';
$domPlainPaths       = 'legal|privacy|help|login|logout|password|billing|createsite';
$adminMotionPaths    = 'index|update|type|listall|excellist';
$adminAmendmentPaths = 'index|update';

$url_rules = [
    $domadmin . ''                                               => 'admin/index',
    $domadmin . 'consultation/'                                  => 'admin/index/consultation',
    $domadmin . 'consultationExtended/'                          => 'admin/index/consultationextended',
    $domadmin . 'translation/'                                   => 'admin/index/translation',
    $domadmin . 'motion/update/<motionId:\d+>'                   => 'admin/motion/update',
    $domadmin . 'motion/<_a:(' . $adminMotionPaths . ')>'        => 'admin/motion/<_a>',
    $domadmin . 'amendments'                                     => 'admin/amendments',
    $domadmin . 'amendments/<_a:(' . $adminAmendmentPaths . ')>' => 'admin/amendments/<_a>',
    $domadmin . 'motionComments'                                 => 'admin/motionComments',
    $domadmin . 'motionComments/<_a:(index)>'                    => 'admin/motionComments/<_a>',
    $domadmin . 'amendmentComments'                              => 'admin/amendmentComments',
    $domadmin . 'amendmentComments/<_a:(index)>'                 => 'admin/amendmentComments/<_a>',
    $domadmin . 'texts'                                          => 'admin/texts',
    $domadmin . 'texts/<_a:(index|update|delete)>'               => 'admin/texts/<_a>',
    $domadmin . 'namespacedAccounts'                             => 'admin/index/namespacedAccounts',
    $domadmin . 'ae_pdf_list'                                    => 'admin/index/aePDFList',
    $domadmin . 'admins'                                         => 'admin/index/admins',
    $domadmin . 'consultations'                                  => 'admin/index/consultations',

    $dom . '<_a:(' . $userPaths . ')>'                           => 'user/<_a>',
    $dom . 'checkemail'                                          => 'user/ajaxIsEmailRegistered',
    $dom . '<_a:(legal|privacy)>'                                => 'consultation/<_a>',

    $domv . '<_a:(' . $consultationPaths . ')>'                  => 'consultation/<_a>',
    $dommotion                                                   => 'motion/view',
    $dommotion . '/<_a:(' . $motionPaths . ')>'                  => 'motion/<_a>',
    $domamend                                                    => 'amendment/view',
    $domamend . '/<_a:(pdf|createconfirm|edit)>'                 => 'amendment/<_a>',
    $domamend . 'pdf'                                            => 'amendment/pdf',
    $domamend . 'pdfdiff'                                        => 'amendment/pdfDiff',
    $dommotion . '/amendment/create'                             => 'amendment/create',
    $domv                                                        => 'consultation/index',
];

if ($params->domainPlain != $params->domainSubdomain) {
    $url_rules[$dom] = 'consultation/index';
}

if ($params->multisiteMode) {
    $url_rules = array_merge(
        [
            $params->domainPlain                                    => 'manager/index',
            $params->domainPlain . '/<_a:(' . $domPlainPaths . ')>' => 'manager/<_a>',
        ],
        $url_rules
    );

    if ($params->prependWWWToSubdomain) {
        foreach ($url_rules as $key => $val) {
            $url_rules[str_replace("http://", "http://www.", $key)] = $val;
        }
    }
}

return $url_rules;
