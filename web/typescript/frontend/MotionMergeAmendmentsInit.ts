export class MotionMergeAmendmentsInit {
    constructor(public $form: JQuery) {
        let $allRadio = $form.find(".all label"),
            $singleRadio = $form.find(".single label"),
            $singleSelect = $form.find(".single select");

        $allRadio.on("checked.fu.radio", () => {
            console.log("all");
        });
        $singleRadio.on("checked.fu.radio", () => {
            console.log("single");
        });
    }
}