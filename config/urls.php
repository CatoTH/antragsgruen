<?php

/**
 * @var app\models\settings\AntragsgruenApp $params
 */


if ($params->multisiteMode) {
    $dom = ($params->domainSubdomain ? $params->domainSubdomain : '/<subdomain:[\\w_-]+>/');
} else {
    $dom = '/';
}
$domv         = $dom . '<consultationPath:[\w_-]+>/';
$domadmin     = $domv . 'admin/';
$dommotion    = $domv . '<motionSlug:[^\/]+\-\d+>';
$domamend     = $domv . '<motionSlug:[^\/]+[^\/]+\-\d+>/<amendmentId:\d+>';
$dommotionOld = $domv . 'motion/<motionSlug:[^\/]+>';
$domamendOld  = $domv . 'motion/<motionSlug:[^\/]+>/amendment/<amendmentId:\d+>';

$consultationPaths    = 'search|maintenance|notifications|activitylog';
$consultationPaths    .= '|amendmentpdfs|feedall|feedmotions|feedamendments|feedcomments';
$consultationPaths    .= '|proposed-procedure|proposed-procedure-ajax|debugbar-ajax';
$motionPaths          = 'createconfirm|createdone|edit|pdf|pdfamendcollection|odt|plainhtml|viewimage|viewpdf|embeddedpdf';
$motionPaths          .= '|withdraw|view-changes|view-changes-odt|save-proposal-status';
$motionPaths          .= '|merge-amendments|merge-amendments-init|merge-amendments-confirm';
$motionPaths          .= '|merge-amendments-public|merge-amendments-public-ajax|save-merging-draft';
$amendPaths           = 'pdf|odt|createconfirm|createdone|edit|withdraw|merge|merge-done|get-merge-collissions|ajax-diff';
$amendPaths           .= '|save-proposal-status|edit-proposed-change|edit-proposed-change-check';
$userPaths            = 'login|logout|confirmregistration|emailblacklist|recovery';
$userPaths            .= '|loginsaml|logoutsaml|consultationaccesserror|myaccount|emailchange|data-export';
$domPlainPaths        = 'siteconfig|antragsgrueninit|antragsgrueninitdbtest|userlist';
$adminMotionPaths     = 'type|typecreate|get-amendment-rewrite-collissions';
$adminMotionListPaths = 'index|motion-excellist|motion-odslist|motion-pdfziplist';
$adminMotionListPaths .= '|motion-odtziplist|motion-odslistall|motion-openslides';
$adminAmendmentPaths  = 'excellist|odslist|pdflist|pdfziplist|odtziplist|openslides';
$adminPaths           = 'consultation|consultationextended|translation|siteaccess|siteconsultations|openslidesusers';
$adminPaths           .= '|todo|proposed-procedure|ods-proposed-procedure|check-updates|goto-update';
$adminPpPaths         = 'index-ajax|ods|save-motion-comment|save-amendment-comment|save-motion-visible|save-amendment-visible';

$urlRules = [
    $domadmin . ''                                                => 'admin/index',
    $domadmin . '<_a:(' . $adminPaths . ')>'                      => 'admin/index/<_a>',
    $domadmin . 'motion/update/<motionId:\d+>'                    => 'admin/motion/update',
    $domadmin . 'motion/<_a:(' . $adminMotionPaths . ')>'         => 'admin/motion/<_a>',
    $domadmin . 'amendment'                                       => 'admin/amendment',
    $domadmin . 'amendment/update/<amendmentId:\d+>'              => 'admin/amendment/update',
    $domadmin . 'amendment/<_a:(' . $adminAmendmentPaths . ')>'   => 'admin/amendment/<_a>',
    $domadmin . 'list'                                            => 'admin/motion-list/index',
    $domadmin . 'list/<_a:(' . $adminMotionListPaths . ')>'       => 'admin/motion-list/<_a>',
    $domadmin . 'proposed_procedure'                              => 'admin/proposed-procedure/index',
    $domadmin . 'proposed_procedure/<_a:(' . $adminPpPaths . ')>' => 'admin/proposed-procedure/<_a>',
    $domadmin . 'namespacedAccounts'                              => 'admin/index/namespacedAccounts',
    $domadmin . 'ae_pdf_list'                                     => 'admin/index/aePDFList',
    $domadmin . 'admins'                                          => 'admin/index/admins',
    $domadmin . 'consultations'                                   => 'admin/index/consultations',

    $dom . '<_a:(' . $userPaths . ')>'        => 'user/<_a>',
    $dom . 'checkemail'                       => 'user/ajaxIsEmailRegistered',
    $domv . 'page'                            => 'pages/list-pages',
    $domv . 'page/files/upload'               => 'pages/upload',
    $domv . 'page/files/browse-images'        => 'pages/browse-images',
    $domv . 'page/files/<filename:[^\/]+>'    => 'pages/file',
    $domv . 'page/<pageSlug:[^\/]+>'          => 'pages/show-page',
    $dom . 'page/<pageSlug:[^\/]+>'           => 'pages/show-page',
    $dom . 'page/<pageSlug:[^\/]+>/save'      => 'pages/save-page',
    $dom . 'page/<pageSlug:[^\/]+>/delete'    => 'pages/delete-page',
    $dom . 'admin/<_a:(siteconfig|userlist)>' => 'manager/<_a>',

    $domv . 'motion/pdfcollection/<motionTypeId:\d+>/<filename:.*>' => 'motion/pdfcollection',
    $domv . 'amendment/pdfcollection/<filename:.*>'                 => 'amendment/pdfcollection',

    $domv . '<_a:(' . $consultationPaths . ')>'    => 'consultation/<_a>',
    $domv . 'motion/create'                        => 'motion/create',
    $dommotion                                     => 'motion/view',
    $dommotion . '/<_a:(' . $motionPaths . ')>'    => 'motion/<_a>',
    $domamend                                      => 'amendment/view',
    $domamend . '/<_a:(' . $amendPaths . ')>'      => 'amendment/<_a>',
    $dommotion . '/amendment/create'               => 'amendment/create',
    $dommotionOld                                  => 'motion/view',
    $dommotionOld . '/<_a:(' . $motionPaths . ')>' => 'motion/<_a>',
    $domamendOld                                   => 'amendment/view',
    $domamendOld . '/<_a:(' . $amendPaths . ')>'   => 'amendment/<_a>',
    $dommotionOld . '/amendment/create'            => 'amendment/create',
    $domv                                          => 'consultation/index',
    $dom                                           => 'consultation/home',
];

foreach ($params->getPluginClasses() as $pluginClass) {
    $urlRules = array_merge($urlRules, $pluginClass::getAllUrlRoutes($dommotion, $dommotionOld));
}

// Catch-All-Routes, should be loaded last
$urlRules = array_merge($urlRules, [
    $domv . '<prefix:[^\/]+>'                   => 'motion/goto-prefix',
    $domv . '<prefix1:[^\/]+>/<prefix2:[^\/]+>' => 'amendment/goto-prefix',
]);

if ($params->multisiteMode) {
    $domp = trim($params->domainPlain, '/');

    $urlRules = array_merge(
        [
            $domp . '/<_a:(' . $userPaths . ')>'   => 'user/<_a>',
            $domp . '/page/<pageSlug:[^\/]+>/save' => 'pages/save-page',
        ],
        $urlRules
    );

    foreach ($params->getPluginClasses() as $pluginClass) {
        $urlRules = array_merge($pluginClass::getManagerUrlRoutes($domp), $urlRules);
    }

    if ($params->prependWWWToSubdomain) {
        foreach ($urlRules as $key => $val) {
            $urlRules[str_replace('http://', 'http://www.', $key)]   = $val;
            $urlRules[str_replace('https://', 'https://www.', $key)] = $val;
        }
    }
}

return $urlRules;
