<?php
namespace app\models\forms;


use app\models\db\Amendment;
use app\models\db\Motion;
use app\models\db\MotionComment;

class CommentForm extends \yii\base\Model
{
    /** @var string */
    public $email;
    public $name;

    /** @var string */
    public $text;

    /** @var int */
    public $paragraphNo;
    public $userId;

    /**
     * @param Motion $motion
     * @return MotionComment
     * @throws \app\models\exceptions\FormError
     */
    public function saveMotionComment(Motion $motion)
    {
        $settings = $motion->consultation->getSettings();
        if ($settings->commentNeedsEmail && trim($this->email) == "") {
            throw new \app\models\exceptions\FormError('No E-Mail-Address entered');
        }

        $comment = new MotionComment();
        $comment->motionId = $motion->id;
        $comment->paragraph = $this->paragraphNo;
        $comment->contactEmail = $this->email;
        $comment->name = $this->name;
        $comment->text = $this->text;
        $comment->dateCreation = date('Y-m-d H:i:s');

        if ($settings->screeningComments) {
            $comment->status = MotionComment::STATUS_SCREENING;
        } else {
            $comment->status = MotionComment::STATUS_VISIBLE;
        }

        $comment->save();

        /**
        $add = ($this->veranstaltung->getEinstellungen()->freischaltung_kommentare ? " Er wird nach einer kurzen Prüfung freigeschaltet und damit sichtbar." : "");
        Yii::app()->user->setFlash("success", "Der Kommentar wurde gespeichert." . $add);

        if ($this->veranstaltung->admin_email != "" && $kommentar->status == IKommentar::$STATUS_NICHT_FREI) {
            $kommentar_link = $kommentar->getLink(true);
            $mails          = explode(",", $this->veranstaltung->admin_email);
            $from_name      = veranstaltungsspezifisch_email_from_name($this->veranstaltung);
            $mail_text      = "Es wurde ein neuer Kommentar zum Antrag \"" . $antrag->name . "\" verfasst (nur eingeloggt sichtbar):\n" .
                "Link: " . $kommentar_link;

            foreach ($mails as $mail) {
                if (trim($mail) != "") {
                    AntraegeUtils::send_mail_log(EmailLog::$EMAIL_TYP_ANTRAG_BENACHRICHTIGUNG_ADMIN, trim($mail), null, "Neuer Kommentar - bitte freischalten.", $mail_text, $from_name);
                }
            }
        }

        if ($kommentar->status == IKommentar::$STATUS_FREI) {
            $benachrichtigt = array();
            foreach ($antrag->veranstaltung->veranstaltungsreihe->veranstaltungsreihenAbos as $abo) {
                if ($abo->kommentare && !in_array($abo->person_id, $benachrichtigt)) {
                    $abo->person->benachrichtigenKommentar($kommentar);
                    $benachrichtigt[] = $abo->person_id;
                }
            }
        }
         * */

        return $comment;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['text', 'paragraphNo'], 'required'],
            [['paragraphNo'], 'number'],
            [['text', 'name', 'email', 'paragraphNo'], 'safe'],
        ];
    }
}
