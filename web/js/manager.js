/*global browser: true, regexp: true */
/*global $, jQuery, alert, console */
/*jslint regexp: true*/

(function ($) {
    "use strict";
    var createInstance = function () {
        var $steps = $("#AnlegenWizard").find("li"),
            $step2 = $("#step2"),
            $step3 = $("#step3");
        $step2.hide();
        $step3.hide();
        $("#weiter-1").click(function (ev) {
            ev.preventDefault();
            $("#step1").hide();
            $step2.show();
            $steps.eq(0).removeClass("active");
            $steps.eq(1).addClass("active");
        });
        $("#weiter-2").click(function (ev) {
            ev.preventDefault();
            if ($step2.find(".name input").val() === "") {
                $step2.find(".name .alert").show();
                $step2.find(".name input").focus();
                return;
            }
            if ($step2.find(".url input").val() === "") {
                $step2.find(".url .alert").show();
                $step2.find(".url input").focus();
                return;
            }
            $step2.hide();
            $step3.show();
            $steps.eq(1).removeClass("active");
            $steps.eq(2).addClass("active");
        });
        $("#CInstanzAnlegenForm_subdomain").on("blur", function () {
            if ($(this).val().match(/[^a-zA-Z0-9_\-]/)) {
                alert("Bei der Subdomain sind nur Zahlen, Buchstaben, Unter- und Mittelstrich möglich.");
                $(this).focus();
            }
        });
        $step3.find("button[type=submit]").click(function (ev) {
            console.log(ev);
        });
    };

    $.SiteManager = {
        "createInstance": createInstance
    };

}(jQuery));
