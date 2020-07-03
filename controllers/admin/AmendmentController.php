<?php

namespace app\controllers\admin;

use app\models\settings\AntragsgruenApp;
use app\components\{Tools, UrlHelper, ZipWriter};
use app\models\db\{Amendment, AmendmentSupporter, User};
use app\models\events\AmendmentEvent;
use app\models\exceptions\FormError;
use app\models\forms\AmendmentEditForm;
use app\views\amendment\LayoutHelper;
use yii\web\Response;

class AmendmentController extends AdminBase
{
    /**
     * @param bool $textCombined
     * @param int $withdrawn
     *
     * @return string
     */
    public function actionOdslist($textCombined = false, $withdrawn = 0)
    {
        $withdrawn = (IntVal($withdrawn) === 1);

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/vnd.oasis.opendocument.spreadsheet');
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=amendments.ods');
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        return $this->renderPartial('ods_list', [
            'motions'      => $this->consultation->getVisibleMotionsSorted($withdrawn),
            'textCombined' => $textCombined,
            'withdrawn'    => $withdrawn,
        ]);
    }

    /**
     * @param int $textCombined
     * @param int $withdrawn
     * @param int $maxLen
     *
     * @return string
     */
    public function actionOdslistShort($textCombined = 0, $withdrawn = 0, $maxLen = 2000)
    {
        $withdrawn    = (IntVal($withdrawn) === 1);
        $maxLen       = IntVal($maxLen);
        $textCombined = (IntVal($textCombined) === 1);

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/vnd.oasis.opendocument.spreadsheet');
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=amendments.ods');
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        return $this->renderPartial('ods_list_short', [
            'motions'      => $this->consultation->getVisibleMotionsSorted($withdrawn),
            'textCombined' => $textCombined,
            'maxLen'       => $maxLen,
            'withdrawn'    => $withdrawn,
        ]);
    }

    /**
     * @param int $withdrawn
     *
     * @return string
     */
    public function actionPdflist($withdrawn = 0)
    {
        $withdrawn = (IntVal($withdrawn) === 1);

        return $this->render('pdf_list', ['consultation' => $this->consultation, 'withdrawn' => $withdrawn]);
    }

    /**
     * @param int $withdrawn
     *
     * @return string
     * @throws \Exception
     */
    public function actionPdfziplist($withdrawn = 0)
    {
        $withdrawn = (IntVal($withdrawn) === 1);
        $zip       = new ZipWriter();
        $hasLaTeX  = ($this->getParams()->xelatexPath || $this->getParams()->lualatexPath);
        foreach ($this->consultation->getVisibleMotions($withdrawn) as $motion) {
            foreach ($motion->getVisibleAmendments($withdrawn) as $amendment) {
                if ($hasLaTeX && $amendment->getMyMotionType()->texTemplateId) {
                    $file = LayoutHelper::createPdfLatex($amendment);
                } else {
                    $file = LayoutHelper::createPdfTcpdf($amendment);
                }
                $zip->addFile($amendment->getFilenameBase(false) . '.pdf', $file);
            }
        }

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/zip');
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=amendments_pdf.zip');
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        return $zip->getContentAndFlush();
    }

    /**
     * @param int $withdrawn
     *
     * @return string
     * @throws \Exception
     */
    public function actionOdtziplist($withdrawn = 0)
    {
        $withdrawn = (IntVal($withdrawn) === 1);
        $zip       = new ZipWriter();
        foreach ($this->consultation->getVisibleMotions($withdrawn) as $motion) {
            foreach ($motion->getVisibleAmendments($withdrawn) as $amendment) {
                $content = $this->renderPartial('@app/views/amendment/view_odt', ['amendment' => $amendment]);
                $zip->addFile($amendment->getFilenameBase(false) . '.odt', $content);
            }
        }

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/zip');
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=amendments_odt.zip');
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        return $zip->getContentAndFlush();
    }

    /**
     * @param Amendment $amendment
     *
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    private function saveAmendmentSupporters(Amendment $amendment)
    {
        $names         = \Yii::$app->request->post('supporterName', []);
        $orgas         = \Yii::$app->request->post('supporterOrga', []);
        $genders       = \Yii::$app->request->post('supporterGender', []);
        $preIds        = \Yii::$app->request->post('supporterId', []);
        $newSupporters = [];
        /** @var AmendmentSupporter[] $preSupporters */
        $preSupporters = [];
        foreach ($amendment->getSupporters() as $supporter) {
            $preSupporters[$supporter->id] = $supporter;
        }
        for ($i = 0; $i < count($names); $i++) {
            if (trim($names[$i]) === '' && trim($orgas[$i]) === '') {
                continue;
            }
            if (isset($preSupporters[$preIds[$i]])) {
                $supporter = $preSupporters[$preIds[$i]];
            } else {
                $supporter               = new AmendmentSupporter();
                $supporter->amendmentId  = $amendment->id;
                $supporter->role         = AmendmentSupporter::ROLE_SUPPORTER;
                $supporter->personType   = AmendmentSupporter::PERSON_NATURAL;
                $supporter->dateCreation = date('Y-m-d H:i:s');
            }
            $supporter->name         = $names[$i];
            $supporter->organization = $orgas[$i];
            $supporter->position     = $i;
            $supporter->setExtraDataEntry('gender', (isset($genders[$i]) ? $genders[$i] : null));
            if (!$supporter->save()) {
                var_dump($supporter->getErrors());
                die();
            }
            $newSupporters[$supporter->id] = $supporter;
        }

        foreach ($preSupporters as $supporter) {
            if (!isset($newSupporters[$supporter->id])) {
                $supporter->delete();
            }
        }

        $amendment->refresh();
    }

    /**
     * @param int $amendmentId
     *
     * @return string
     * @throws \Exception
     * @throws \Throwable
     * @throws \app\models\exceptions\Internal
     * @throws \yii\base\ExitException
     * @throws \yii\db\StaleObjectException
     */
    public function actionUpdate($amendmentId)
    {
        if (!User::havePrivilege($this->consultation, User::PRIVILEGE_CONTENT_EDIT)) {
            $this->showErrorpage(403, \Yii::t('admin', 'no_access'));

            return false;
        }

        $amendment = $this->consultation->getAmendment($amendmentId);
        if (!$amendment) {
            $this->redirect(UrlHelper::createUrl('admin/motion-list/index'));
        }
        $this->checkConsistency($amendment->getMyMotion(), $amendment);

        $this->layout = 'column2';

        $post = \Yii::$app->request->post();
        $form = new AmendmentEditForm($amendment->getMyMotion(), $amendment);
        $form->setAdminMode(true);

        if ($this->isPostSet('screen') && $amendment->isInScreeningProcess()) {
            if ($amendment->getMyMotion()->findAmendmentWithPrefix($post['titlePrefix'], $amendment)) {
                \yii::$app->session->setFlash('error', \Yii::t('admin', 'amend_prefix_collision'));
            } else {
                $amendment->status      = Amendment::STATUS_SUBMITTED_SCREENED;
                $amendment->titlePrefix = $post['titlePrefix'];
                $amendment->save();
                $amendment->trigger(Amendment::EVENT_PUBLISHED, new AmendmentEvent($amendment));
                \yii::$app->session->setFlash('success', \Yii::t('admin', 'amend_screened'));
            }
        }

        if ($this->isPostSet('delete')) {
            $amendment->status = Amendment::STATUS_DELETED;
            $amendment->save();
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'amend_deleted'));
            $this->redirect(UrlHelper::createUrl('admin/motion-list/index'));

            return '';
        }

        if ($this->isPostSet('save')) {
            if (!isset($post['edittext'])) {
                unset($post['sections']);
            }
            $form->setAttributes([$post, $_FILES]);

            $votingData = $amendment->getVotingData();
            $votingData->setFromPostData($post['votes']);
            $amendment->setVotingData($votingData);

            try {
                $form->saveAmendment($amendment);
            } catch (FormError $e) {
                \Yii::$app->session->setFlash('error', $e->getMessage());
            }

            $amdat                        = $post['amendment'];
            $amendment->statusString      = $amdat['statusString'];
            $amendment->dateCreation      = Tools::dateBootstraptime2sql($amdat['dateCreation']);
            $amendment->noteInternal      = $amdat['noteInternal'];
            $amendment->status            = $amdat['status'];
            $amendment->globalAlternative = (isset($amdat['globalAlternative']) ? 1 : 0);
            $amendment->dateResolution    = '';
            if ($amdat['dateResolution'] !== '') {
                $amendment->dateResolution = Tools::dateBootstraptime2sql($amdat['dateResolution']);
            }

            if ($amendment->getMyMotion()->findAmendmentWithPrefix($amdat['titlePrefix'], $amendment)) {
                \yii::$app->session->setFlash('error', \Yii::t('admin', 'amend_prefix_collision'));
            } else {
                $amendment->titlePrefix = $post['amendment']['titlePrefix'];
            }

            foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
                $plugin::setAmendmentExtraSettingsFromForm($amendment, $post);
            }

            $amendment->save();

            $this->saveAmendmentSupporters($amendment);

            $amendment->flushCache(true);
            $amendment->refresh();
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'saved'));
        }

        return $this->render('update', ['amendment' => $amendment, 'form' => $form]);
    }

    /**
     * @return string
     */
    public function actionOpenslides()
    {
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'text/csv');
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=Amendments.csv');
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        $amendments = [];
        foreach ($this->consultation->getVisibleMotionsSorted(false) as $motion) {
            foreach ($motion->getVisibleAmendmentsSorted(false) as $amendment) {
                $amendments[] = $amendment;
            }
        }

        return $this->renderPartial('openslides_list', [
            'amendments' => $amendments,
        ]);
    }
}
