class MotionInitiatorShow {
    constructor() {
        $(".motionData .contactShow").click(function (ev) {
            ev.preventDefault();
            $(this).addClass("hidden");
            $(".motionData .contactDetails").removeClass("hidden");
        });
    }
}
