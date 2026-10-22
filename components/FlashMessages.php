<?php

declare(strict_types=1);

namespace app\components;

use Yii;

/**
 * Renders the flash messages (error, success, info) stored in the session, removing them from the session.
 */
final class FlashMessages
{
    public static function render(bool $addBorder = false): string
    {
        $session = RequestContext::getSession();
        if (!$session->isActive) {
            return '';
        }
        $str = '';

        $error = $session->getFlash('error', null, true);
        if ($error) {
            $str .= '<div class="alert alert-danger" role="alert">
                <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
                <span class="sr-only">' . Yii::t('base', 'aria_error') . ':</span>
                ' . nl2br(Html::encode($error)) . '
            </div>';
        }

        $success = $session->getFlash('success', null, true);
        if ($success) {
            $str .= '<div class="alert alert-success" role="alert">
                <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
                <span class="sr-only">' . Yii::t('base', 'aria_success') . ':</span>
                ' . Html::encode($success) . '
            </div>';
        }

        $info = $session->getFlash('info', null, true);
        if ($info) {
            $str .= '<div class="alert alert-info" role="alert">
                <span class="glyphicon glyphicon glyphicon-info-sign" aria-hidden="true"></span>
                <span class="sr-only">' . Yii::t('base', 'aria_info') . ':</span>
                ' . Html::encode($info) . '
            </div>';
        }

        $email = $session->getFlash('email', null, true);
        if ($email && YII_ENV === 'test') {
            $str .= '<div class="alert alert-info" role="alert">
                <span class="glyphicon glyphicon glyphicon-info-sign" aria-hidden="true"></span>
                <span class="sr-only">' . Yii::t('base', 'aria_info') . ':</span>
                ' . Html::encode($email) . '
            </div>';
        }

        if ($str !== '' && $addBorder) {
            $str = '<div class="content">' . $str . '</div>';
        }

        return $str;
    }
}
