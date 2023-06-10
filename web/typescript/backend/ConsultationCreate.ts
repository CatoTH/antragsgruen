import "../shared/SiteCreateWizard";

declare let __t: any;

export class ConsultationCreate {
    constructor() {
        $(".settingsType").find("input[type=radio]").on("change", ConsultationCreate.settingsTypeChanged).trigger("change");

        let $consultationCreateForm = $(".consultationCreateForm");
        $consultationCreateForm.find(".delbox button").on("click", function (ev) {
            ev.preventDefault();
            let $button = $(this);
            bootbox.confirm(__t('admin', 'consDeleteConfirm'), function (result) {
                if (result) {
                    let $input = $('<input type="hidden">').attr("name", $button.attr("name")).attr("value", $button.attr("value"));
                    $consultationCreateForm.append($input);
                    $consultationCreateForm.trigger("submit");
                }
            });
        });

        new SiteCreateWizard($(".siteCreate"));
    }

    private static settingsTypeChanged(): void {
        if ($("#settingsTypeWizard").prop("checked")) {
            $(".settingsTypeWizard").removeClass("hidden");
            $(".settingsTypeTemplate").addClass("hidden");
        } else {
            $(".settingsTypeWizard").addClass("hidden");
            $(".settingsTypeTemplate").removeClass("hidden");
        }
    }
}

new ConsultationCreate();
