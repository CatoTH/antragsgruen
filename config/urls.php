<?php

/**
 * @var app\models\settings\AntragsgruenApp $params
 */


if ($params->multisiteMode) {
    $dom = $params->domainSubdomain ?: '/<subdomain:[\\w_-]+>/';
} else {
    $dom = '/';
}
$domv         = $dom . '<consultationPath:[\w_-]+>/';
$domadmin     = $domv . 'admin/';
$dommotion    = $domv . '<motionSlug:[^\/]+\-\d+>';
$domamend     = $domv . '<motionSlug:[^\/]+[^\/]+\-\d+>/<amendmentId:\d+>';
$dommotionOld = $domv . 'motion/<motionSlug:[^\/]+>';
$domamendOld  = $domv . 'motion/<motionSlug:[^\/]+>/amendment/<amendmentId:\d+>';

$restBase = $dom . 'rest';
$restBaseCon = $restBase . '/<consultationPath:[\w_-]+>';

$consultationPaths    = 'search|maintenance|notifications|activitylog|collecting|save-agenda-item-ajax|del-agenda-item-ajax|save-agenda-order-ajax';
$consultationPaths    .= '|resolutions|motions|todo|todo-count|votings|voting-results|feeds|feedall|feedmotions|feedamendments|feedcomments';
$consultationPaths    .= '|speech|admin-speech|admin-votings|proposed-procedure|proposed-procedure-ajax|debugbar-ajax';
$motionPaths          = 'createconfirm|createdone|edit|pdf|pdfamendcollection|pdfembed|odt|plainhtml|viewimage|viewpdf|embeddedpdf|embedded-amendments-pdf';
$motionPaths          .= '|admin-speech|withdraw|view-changes|view-changes-odt';
$motionPaths          .= '|save-proposal-status|edit-proposed-change|del-proposal-comment|save-editorial';
$motionPaths          .= '|merge-amendments|merge-amendments-init|merge-amendments-confirm|merge-amendments-paragraph-ajax|merge-amendments-status-ajax';
$motionPaths          .= '|merge-amendments-init-pdf|merge-amendments-draft-pdf';
$motionPaths          .= '|merge-amendments-public|merge-amendments-public-ajax|save-merging-draft';
$amendPaths           = 'pdf|odt|createconfirm|createdone|edit|withdraw|merge|merge-done|get-merge-collisions|ajax-diff';
$amendPaths           .= '|save-proposal-status|edit-proposed-change|edit-proposed-change-check|del-proposal-comment';
$userPaths            = 'login|login2fa|login2fa-force-registration|login-force-pwd-change|logout|token|confirmregistration';
$userPaths            .= '|emailblocklist|recovery|myaccount|emailchange|data-export';
$adminMotionPaths     = 'get-amendment-rewrite-collisions|move|move-check';
$adminTypePaths       = 'type|typecreate';
$adminMotionListPaths = 'index|motion-excellist|motion-odslist|motion-pdfziplist';
$adminMotionListPaths .= '|motion-odtziplist|motion-odslistall|motion-odtall|motion-openslides|motion-comments-xlsx';
$adminAmendmentPaths  = 'excellist|odslist|odslist-short|xlsx-list|pdflist|pdfziplist|odtziplist|openslides';
$adminUserPaths       = 'save|poll|add-single-init|add-single|add-multiple-ww|add-multiple-email|search-groups';
$adminPaths           = 'consultation|appearance|consultationextended|translation|translation-motion-type|siteaccess|siteconsultations|openslidesusers';
$adminPaths           .= '|theming|files|proposed-procedure|ods-proposed-procedure|check-updates|goto-update';
$adminPpPaths         = 'index-ajax|ods|save-motion-comment|save-amendment-comment|save-motion-visible|save-amendment-visible|save-responsibility|save-tags';

$urlRules = [
    $domadmin                                                     => 'admin/index',
    $domadmin . '<_a:(' . $adminPaths . ')>'                      => 'admin/index/<_a>',
    $domadmin . 'users'                                           => 'admin/users/index',
    $domadmin . 'users/<_a:(' . $adminUserPaths . ')>'            => 'admin/users/<_a>',
    $domadmin . 'motion/update/<motionId:\d+>'                    => 'admin/motion/update',
    $domadmin . 'motion/<_a:(' . $adminMotionPaths . ')>'         => 'admin/motion/<_a>',
    $domadmin . 'motion-type/<_a:(' . $adminTypePaths . ')>'      => 'admin/motion-type/<_a>',
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

    $domv . '<_a:(' . $userPaths . ')>'       => 'user/<_a>',
    $dom . '<_a:(' . $userPaths . ')>'        => 'user/<_a>',
    $dom . 'checkemail'                       => 'user/ajaxIsEmailRegistered',
    $domv . 'consultationaccesserror'         => 'user/consultationaccesserror',
    $domv . 'tags/<tagId:\d+>/motions'        => 'consultation/tags-motions',
    $domv . 'tags/<tagId:\d+>/resolutions'    => 'consultation/tags-resolutions',
    $domv . 'page'                            => 'pages/list-pages',
    $domv . 'documents'                       => 'pages/documents',
    $domv . 'documents/<groupId:[^\/]+>.zip'  => 'pages/documents-zip',
    $domv . 'page/files/upload'               => 'pages/upload',
    $domv . 'page/files/delete'               => 'pages/delete-file',
    $domv . 'page/files/browse-images'        => 'pages/browse-images',
    $domv . 'page/files/<filename:[^\/]+>'    => 'pages/file',
    $domv . 'page/<pageSlug:[^\/]+>'          => 'pages/show-page',
    $dom . 'styles<hash:[^\/]+>.css'          => 'pages/css',
    $dom . 'page/<pageSlug:[^\/]+>'           => 'pages/show-page',
    $dom . 'page/<pageSlug:[^\/]+>/save'      => 'pages/save-page',
    $dom . 'page/<pageSlug:[^\/]+>/delete'    => 'pages/delete-page',
    $dom . 'admin/<_a:(siteconfig|health)>'   => 'manager/<_a>',

    $restBase                                                                        => 'consultation/rest-site',
    $restBase . '/health'                                                            => '/manager/health',
    $restBaseCon                                                                     => 'consultation/rest',
    $restBaseCon . '/proposed-procedure'                                             => 'consultation/proposed-procedure-rest',
    $restBaseCon . '/motion/<motionSlug:[^\/]+>'                                     => '/motion/rest',
    $restBaseCon . '/motion/<motionSlug:[^\/]+>/amendment/<amendmentId:\d+>'         => '/amendment/rest',
    $restBaseCon . '/speech/<queueId:[^\/]+>'                                        => '/speech/get-queue',
    $restBaseCon . '/speech/<queueId:[^\/]+>/item'                                   => '/speech/register',
    $restBaseCon . '/speech/<queueId:[^\/]+>/unregister'                             => '/speech/unregister',
    $restBaseCon . '/speech/<queueId:[^\/]+>/admin'                                  => '/speech/get-queue-admin',
    $restBaseCon . '/speech/<queueId:[^\/]+>/admin/settings'                         => '/speech/post-queue-settings',
    $restBaseCon . '/speech/<queueId:[^\/]+>/admin/reset'                            => '/speech/admin-queue-reset',
    $restBaseCon . '/speech/<queueId:[^\/]+>/admin/item'                             => '/speech/admin-create-item',
    $restBaseCon . '/speech/<queueId:[^\/]+>/admin/item/<itemId:[^\/]+>/<op:[^\/]+>' => '/speech/post-item-operation',
    $restBaseCon . '/page/<pageSlug:[^\/]+>'                                         => '/pages/get-rest',

    $restBaseCon . '/votings/open' => '/voting/get-open-voting-blocks',
    $restBaseCon . '/votings/closed' => '/voting/get-closed-voting-blocks',
    $restBaseCon . '/votings/admin' => '/voting/get-admin-voting-blocks',
    $restBaseCon . '/votings/create' => '/voting/create-voting-block',
    $restBaseCon . '/votings/sort' => '/voting/post-vote-order',
    $restBaseCon . '/votings/<votingBlockId:[^\/]+>/settings' => '/voting/post-vote-settings',
    $restBaseCon . '/votings/<votingBlockId:[^\/]+>/vote' => '/voting/post-vote',
    $restBaseCon . '/votings/<votingBlockId:[^\/]+>/results.<format:[^\/]+>' => '/voting/download-voting-results',

    $domv . 'motion/pdfcollection/<motionTypeId:[^\/]+>/<filename:.*>' => 'motion/pdfcollection',
    $domv . 'motion/fullpdf/<motionTypeId:[^\/]+>/<filename:.*>'       => 'motion/fullpdf',
    $domv . 'amendment/pdfcollection/<filename:.*>'                 => 'amendment/pdfcollection',

    $domv . '<_a:(' . $consultationPaths . ')>'    => 'consultation/<_a>',
    $domv . 'motion/create'                        => 'motion/create',
    $domv . 'motion/create-select-statutes'        => 'motion/create-select-statutes',
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

if (YII_ENV === 'test') {
    $urlRules[$domv . 'test/<action:[^\/]+>'] = '/test/index';
}

foreach ($params->getPluginClasses() as $pluginClass) {
    $urlRules = $pluginClass::getAllUrlRoutes($urlRules, $dom, $dommotion, $dommotionOld, $domamend, $domamendOld);
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
            $dom . 'page/<pageSlug:[^\/]+>/save'   => 'pages/save-page',
            $domp . '/page/<pageSlug:[^\/]+>/save' => 'pages/save-page',
        ],
        $urlRules
    );
    if ($params->domainSubdomain) {
        // The subdomain-scoped version of the login should have a higher priority
        $urlRules = array_merge($urlRules, [$domp . '/<_a:(' . $userPaths . ')>' => 'user/<_a>']);
    } else {
        // If we use /subdomain/consultation/, the login should have higher priority, not to collide with [consultation]
        $urlRules = array_merge([$domp . '/<_a:(' . $userPaths . ')>' => 'user/<_a>'], $urlRules);
    }

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
