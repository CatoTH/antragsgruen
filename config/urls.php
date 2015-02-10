<?php
use app\models\AntragsgruenAppParams;

/**
 * @var AntragsgruenAppParams $params
 */


$domPlain = $params->domainPlain;
$dom      = $params->domainSubdomain;
$domv     = $dom . '<consultationPath:[\w_-]+>/';
$domadmin = $domv . 'admin/';

$url_rules = [
    $domadmin . ''                                                         => 'admin/index',
    $domadmin . 'consultation/'                                            => 'admin/veranstaltungen/update',
    $domadmin . 'motions'                                                  => 'admin/antraege',
    $domadmin . 'motions/<_a:(index|update|view)>'                         => 'admin/antraege/<_a>',
    $domadmin . 'amendments'                                               => 'admin/aenderungsantraege',
    $domadmin . 'amendments/<_a:(index|update|view)>'                      => 'admin/aenderungsantraege/<_a>',
    $domadmin . 'antraegeKommentare'                                       => 'admin/antraegeKommentare',
    $domadmin . 'antraegeKommentare/<_a:(index|delete|view)>'              => 'admin/antraegeKommentare/<_a>',
    $domadmin . 'amendmentComments'                                        => 'admin/amendmentComments',
    $domadmin . 'amendmentComments/<_a:(index|delete)>'                    => 'admin/amendmentComments/<_a>',
    $domadmin . 'texte'                                                    => 'admin/texte',
    $domadmin . 'texte/<_a:(index|update|delete|view)>'                    => 'admin/texte/<_a>',
    $domadmin . 'kommentare_excel'                                         => 'admin/index/kommentareexcel',
    $domadmin . 'namespacedAccounts'                                       => 'admin/index/namespacedAccounts',
    $domadmin . 'ae_pdf_list'                                              => 'admin/index/aePDFList',
    $dom . 'admin/reihe_admins'                                            => 'admin/index/reiheAdmins',
    $dom . 'admin/reihe_veranstaltungen'                                   => 'admin/index/reiheVeranstaltungen',
    $domv . 'hilfe'                                                        => 'consultation/hilfe',
    $domv . 'suche'                                                        => 'consultation/suche',
    $dom . 'impressum'                                                     => 'consultation/impressum',
    $domv . 'pdfs'                                                         => 'consultation/pdfs',
    $domv . 'aenderungsantrags_pdfs'                                       => 'consultation/aenderungsantragsPdfs',
    $domv . 'feedAlles'                                                    => 'consultation/feedAlles',
    $domv . 'feedAntraege'                                                 => 'consultation/feedAntraege',
    $domv . 'feedAenderungsantraege'                                       => 'consultation/feedAenderungsantraege',
    $domv . 'feedKommentare'                                               => 'consultation/feedKommentare',
    $domv . 'wartungsmodus'                                                => 'consultation/wartungsmodus',
    $dom . 'login'                                                         => 'user/login',
    $dom . 'logout'                                                        => 'user/logout',
    $dom . 'anmelden_bestaetigen'                                          => 'user/anmeldungBestaetigen',
    $dom . 'user'                                                          => 'user/index',
    $dom . 'checkemail'                                                    => 'user/ajaxIsEmailRegistered',
    $dom . 'loginbyredirecttoken'                                          => 'user/loginbyredirecttoken',
    $domv . 'unsubscribe'                                                  => 'user/unsubscribe',
    $domv . 'motion/neu'                                                   => 'motion/neu',
    $domv . 'motion/<motionId:\d+>?kommentar_id=<kommentar_id:\d+>'        => 'motion/anzeige',
    $domv . 'motion/<motionId:\d+>'                                        => 'motion/anzeige',
    $domv . 'motion/<motionId:\d+>/pdf'                                    => 'motion/pdf',
    $domv . 'motion/<motionId:\d+>/plain_html'                             => 'motion/plainHtml',
    $domv . 'motion/<motionId:\d+>/odt'                                    => 'motion/odt',
    $domv . 'motion/<motionId:\d+>/neuConfirm'                             => 'motion/neuConfirm',
    $domv . 'motion/<motionId:\d+>/aendern'                                => 'motion/aendern',
    $domv . 'motion/<motionId:\d+>/aes_einpflegen'                         => 'motion/aes_einpflegen',
    $domv . 'motion/<motionId:\d+>/amendment/<amendmentId:\d+>'            => 'amendment/anzeige',
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

    foreach ($url_rules as $key => $val) {
        $url_rules[str_replace("http://", "http://www.", $key)] = $val;
    }
}


return $url_rules;
