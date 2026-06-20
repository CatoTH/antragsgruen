// @ts-check

import { SiteCreateWizard } from "../shared/SiteCreateWizard.js";
import translations from "../../vue/Translate.vue.js";

export class ConsultationCreate {
    constructor() {
        $(".settingsType").find("input[type=radio]").on("change", this.settingsTypeChanged).trigger("change");

        let $consultationEditForm = $(".consultationEditForm");
        $consultationEditForm.find(".delbox button").on("click", function (ev) {
            ev.preventDefault();
            let $button = $(this);
            bootbox.confirm(translations.getTranslation('admin', 'cons_delete_confirm'), function (result) {
                if (result) {
                    let $input = $('<input type="hidden">').attr("name", $button.attr("name")).attr("value", $button.attr("value"));
                    $consultationEditForm.append($input);
                    $consultationEditForm.trigger("submit");
                }
            });
        });

        new SiteCreateWizard($(".siteCreate"));
    }

    settingsTypeChanged() {
        if (document.getElementById('settingsTypeWizard').checked) {
            document.querySelector('.settingsTypeWizard').classList.remove('hidden');
            document.querySelectorAll('.settingsTypeTemplate').forEach(el => el.classList.add('hidden'));
            document.querySelector('.templateSubselect').classList.add('hidden');
        } else {
            document.querySelector('.settingsTypeWizard').classList.add('hidden');
            document.querySelectorAll('.settingsTypeTemplate').forEach(el => el.classList.remove('hidden'));
            document.querySelector('.templateSubselect').classList.remove('hidden');
        }
    }
}
