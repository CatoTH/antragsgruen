export class HomeCreatePetitions {
    constructor(private $form: JQuery) {
        let $showWidget = $form.find(".showWidget");

        $showWidget.click(() => {
            $showWidget.addClass("hidden");
            $form.find(".addWidget").removeClass("hidden");
        });
    }
}
