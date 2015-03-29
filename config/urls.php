<?php
use app\models\settings\AntragsgruenApp;

/**
 * @var AntragsgruenApp $params
 */


$domPlain = $params->domainPlain;
$dom      = $params->domainSubdomain;
$domv     = $dom . '<consultationPath:[\w_-]+>/';
$domadmin = $domv . 'admin/';

$url_rules = [
    $domadmin . ''                                                         => 'admin/index',
    $domadmin . 'consultation/'                                            => 'admin/index/consultation',
    $domadmin . 'consultationExtended/'                                    => 'admin/index/consultationextended',
    $domadmin . 'motions/<_a:(index|update|view|sections)>'                => 'admin/motion/<_a>',
    $domadmin . 'amendments'                                               => 'admin/aenderungsantraege',
    $domadmin . 'amendments/<_a:(index|update|view)>'                      => 'admin/aenderungsantraege/<_a>',
    $domadmin . 'antraegeKommentare'                                       => 'admin/antraegeKommentare',
    $domadmin . 'antraegeKommentare/<_a:(index|delete|view)>'              => 'admin/antraegeKommentare/<_a>',
    $domadmin . 'amendmentComments'                                        => 'admin/amendmentComments',
    $domadmin . 'amendmentComments/<_a:(index|delete)>'                    => 'admin/amendmentComments/<_a>',
    $domadmin . 'texts'                                                    => 'admin/texte',
    $domadmin . 'texts/<_a:(index|update|delete|view)>'                    => 'admin/texte/<_a>',
    $domadmin . 'kommentare_excel'                                         => 'admin/index/kommentareexcel',
    $domadmin . 'namespacedAccounts'                                       => 'admin/index/namespacedAccounts',
    $domadmin . 'ae_pdf_list'                                              => 'admin/index/aePDFList',
    $domadmin . 'admins'                                                   => 'admin/index/admins',
    $domadmin . 'consultations'                                            => 'admin/index/consultations',

    $dom . 'login'                                                         => 'user/login',
    $dom . 'logout'                                                        => 'user/logout',
    $dom . 'confirmregistration'                                           => 'user/confirmregistration',
    $dom . 'loginbyredirecttoken'                                          => 'user/loginbyredirecttoken',
    $dom . 'checkemail'                                                    => 'user/ajaxIsEmailRegistered',
    $dom . 'loginwurzelwerk'                                               => 'user/loginwurzelwerk',
    $domv . 'unsubscribe'                                                  => 'user/unsubscribe',

    $domv . 'legal'                                                        => 'consultation/legal',
    $domv . 'help'                                                         => 'consultation/help',
    $domv . 'savetextajax'                                                 => 'consultation/savetextajax',
    $domv . 'search'                                                       => 'consultation/search',
    $domv . 'pdfs'                                                         => 'consultation/pdfs',
    $domv . 'aenderungsantrags_pdfs'                                       => 'consultation/aenderungsantragsPdfs',
    $domv . 'feedAlles'                                                    => 'consultation/feedAlles',
    $domv . 'feedAntraege'                                                 => 'consultation/feedAntraege',
    $domv . 'feedAenderungsantraege'                                       => 'consultation/feedAenderungsantraege',
    $domv . 'feedKommentare'                                               => 'consultation/feedKommentare',
    $domv . 'maintainance'                                                 => 'consultation/maintainance',
    $domv . 'motion/create'                                                => 'motion/create',
    $domv . 'motion/<motionId:\d+>?kommentar_id=<kommentar_id:\d+>'        => 'motion/view',
    $domv . 'motion/<motionId:\d+>'                                        => 'motion/view',
    $domv . 'motion/<motionId:\d+>/pdf'                                    => 'motion/pdf',
    $domv . 'motion/<motionId:\d+>/plain_html'                             => 'motion/plainHtml',
    $domv . 'motion/<motionId:\d+>/odt'                                    => 'motion/odt',
    $domv . 'motion/<motionId:\d+>/createconfirm'                          => 'motion/createconfirm',
    $domv . 'motion/<motionId:\d+>/edit'                                   => 'motion/edit',
    $domv . 'motion/<motionId:\d+>/aes_einpflegen'                         => 'motion/aes_einpflegen',
    $domv . 'motion/<motionId:\d+>/amendment/<amendmentId:\d+>'            => 'amendment/view',
    $domv . 'motion/<motionId:\d+>/amendment/<amendmentId:\d+>/newconfirm' => 'amendment/neuConfirm',
    $domv . 'motion/<motionId:\d+>/amendment/<amendmentId:\d+>/pdf'        => 'amendment/pdf',
    $domv . 'motion/<motionId:\d+>/amendment/<amendmentId:\d+>/pdfdiff'    => 'amendment/pdfDiff',
    $domv . 'motion/<motionId:\d+>/amendment/neu'                          => 'amendment/neu',
    $domv . 'motion/<motionId:\d+>/amendment/ajaxCalcDiff'                 => 'amendment/ajaxCalcDiff',
    $domv                                                                  => 'consultation/index',
    $dom                                                                   => 'consultation/index',
];


if ($params->multisiteMode) {
    $url_rules = array_merge(
        [
            $domPlain                => 'manager/index',
            $domPlain . 'createsite' => 'manager/createsite',
            $domPlain . 'legal'      => 'manager/legal',
            $domPlain . 'password'   => 'manager/password',
            $domPlain . 'billing'    => 'manager/billing',
            $domPlain . 'login'      => 'manager/login',
            $domPlain . 'logout'     => 'manager/logout',
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
