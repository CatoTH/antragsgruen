const CONTACT_NONE=0,CONTACT_OPTIONAL=1,CONTACT_REQUIRED=2,SUPPORTER_ONLY_INITIATOR=0,SUPPORTER_GIVEN_BY_INITIATOR=1,SUPPORTER_COLLECTING_SUPPORTERS=2,SUPPORTER_NO_INITIATOR=3,TYPE_TITLE=0,TYPE_TEXT_SIMPLE=1,TYPE_TEXT_HTML=2,TYPE_IMAGE=3,TYPE_TABULAR=4,TYPE_PDF_ATTACHMENT=5,TYPE_PDF_ALTERNATIVE=6,TYPE_VIDEO_EMBED=7,POLICY_USER_GROUPS=6;class MotionTypeEdit{constructor(){$(".deleteTypeOpener button").on("click",(()=>{$(".deleteTypeForm").removeClass("hidden"),$(".deleteTypeOpener").addClass("hidden")})),$('[data-toggle="tooltip"]').tooltip(),this.initSectionList(),this.initDeadlines(),this.initInitiatorForm($("#motionSupportersForm")),this.initInitiatorForm($("#amendmentSupportersForm")),$(".policyWidget").each(((e,t)=>{this.initPolicyWidget($(t))}));const e=$("#sameInitiatorSettingsForAmendments input");e.on("change",(()=>{e.prop("checked")?$("section.amendmentSupporters").addClass("hidden"):$("section.amendmentSupporters").removeClass("hidden")})).trigger("change")}initPolicyWidget(e){const t=e.find(".userGroupSelect");t.find("select").selectize({});const a=e.find(".policySelect");a.on("change",(()=>{6===parseInt(a.val(),10)?t.removeClass("hidden"):t.addClass("hidden")})).trigger("change")}initInitiatorForm(e){const t=e.find(".contactGender input"),a=e.find(".supportType"),n=e.find(".formGroupAllowMore input"),i=e.find(".contactDetails .initiatorCanBePerson input"),d=e.find(".contactDetails .initiatorCanBeOrganization input");let o=parseInt(a.find("input").val(),10);const r={hasInitiator:()=>3!==o,hasSupporters:()=>3!==o&&a.find('option[value="'+o.toString(10)+'"]').data("has-supporters"),isCollectingSupporters:()=>2===o,allowSupportAfterSubmission:()=>(2===o||1===o)&&n.is(":checked"),allowFemaleQuota:()=>2===o&&0!==parseInt(t.filter(":checked").val(),10),initiatorCanBePerson:()=>3!==o&&i.prop("checked"),initiatorCanBeOrga:()=>3!==o&&d.prop("checked")},s=()=>{e.find("[data-visibility]").each((function(){const e=$(this);r[e.data("visibility")]()?e.removeClass("hidden"):e.addClass("hidden")}))};n.on("change",(()=>{s()})).trigger("change"),a.on("change",(()=>{o=parseInt(a.val(),10);const e=a.find('option[value="'+o.toString(10)+'"]').data("has-supporters");s(),this.motionsHaveSupporters=!!e,t.trigger("change"),n.trigger("change"),this.setMaxPdfSupporters()})).trigger("change"),i.on("change",(()=>{i.prop("checked")||d.prop("checked")||d.prop("checked",!0).trigger("change"),s()})),d.on("change",(()=>{d.prop("checked")||i.prop("checked")||i.prop("checked",!0).trigger("change"),s()})),t.on("change",(()=>{s()})).trigger("change")}setMaxPdfSupporters(){this.amendmentsHaveSupporters||this.motionsHaveSupporters?$("#typeMaxPdfSupportersRow").removeClass("hidden"):$("#typeMaxPdfSupportersRow").addClass("hidden")}initDeadlines(){$("#deadlineFormTypeComplex").on("change",(e=>{$(e.currentTarget).prop("checked")?($(".deadlineTypeSimple").addClass("hidden"),$(".deadlineTypeComplex").removeClass("hidden")):($(".deadlineTypeSimple").removeClass("hidden"),$(".deadlineTypeComplex").addClass("hidden"))})).trigger("change"),$(".datetimepicker").each(((e,t)=>{const a=$(t);a.datetimepicker({locale:a.find("input").data("locale")})}));const e=e=>{let t=e.find(".datetimepickerFrom"),a=e.find(".datetimepickerTo");t.datetimepicker({locale:t.find("input").data("locale")}),a.datetimepicker({locale:a.find("input").data("locale"),useCurrent:!1});const n=()=>{(()=>{const e=t.data("DateTimePicker").date(),n=a.data("DateTimePicker").date();return e&&n&&n.isBefore(e)})()?(t.addClass("has-error"),a.addClass("has-error")):(t.removeClass("has-error"),a.removeClass("has-error"))};t.on("dp.change",n),a.on("dp.change",n)};$(".deadlineEntry").each(((t,a)=>{e($(a))})),$(".deadlineHolder").each(((t,a)=>{const n=$(a),i=()=>{let t=$(".deadlineRowTemplate").html();t=t.replace(/TEMPLATE/g,n.data("type"));let a=$(t);n.find(".deadlineList").append(a),e(a)};n.find(".deadlineAdder").on("click",i),n.on("click",".delRow",(e=>{$(e.currentTarget).parents(".deadlineEntry").remove()})),0===n.find(".deadlineList").children().length&&i()}))}initSectionList(){let e=$("#sectionsList"),t=0;e.data("sortable",Sortable.create(e[0],{handle:".drag-handle",animation:150})),e.on("click","a.remover",(function(e){e.preventDefault();let t=$(this).parents("li").first(),a=t.data("id");bootbox.confirm(__t("admin","deleteMotionSectionConfirm"),(function(e){e&&($(".adminTypeForm").append('<input type="hidden" name="sectionsTodelete[]" value="'+a+'">'),t.remove())}))})),e.on("change",".sectionType",(function(){let e=$(this).parents("li").first(),t=parseInt($(this).val());e.removeClass("title textHtml textSimple image tabularData pdfAlternative pdfAttachment videoEmbed"),0===t?e.addClass("title"):1===t?e.addClass("textSimple"):2===t?e.addClass("textHtml"):3===t?e.addClass("image"):4===t?(e.addClass("tabularData"),0==e.find(".tabularDataRow ul > li").length&&e.find(".tabularDataRow .addRow").trigger("click").trigger("click").trigger("click")):5===t?e.addClass("pdfAttachment"):6===t?e.addClass("pdfAlternative"):7===t&&e.addClass("videoEmbed")})),e.find(".sectionType").trigger("change"),e.on("change",".maxLenSet",(function(){let e=$(this).parents("li").first();$(this).prop("checked")?e.addClass("maxLenSet").removeClass("no-maxLenSet"):e.addClass("no-maxLenSet").removeClass("maxLenSet")})),e.find(".maxLenSet").trigger("change"),e.on("change",".nonPublic",(function(){let e=$(this).parents("li").first();$(this).prop("checked")?(e.find(".hasAmendments").prop("checked",!1),e.find(".amendmentRow").addClass("hidden")):e.find(".amendmentRow").removeClass("hidden")})),e.find(".nonPublic").trigger("change"),$(".sectionAdder").on("click",(function(a){a.preventDefault();let n=$("#sectionTemplate").html();n=n.replace(/#NEW#/g,"new"+t);let i=$(n);e.append(i),t+=1,e.find(".sectionType").trigger("change"),e.find(".maxLenSet").trigger("change");let d=i.find(".tabularDataRow ul");d.data("sortable",Sortable.create(d[0],{handle:".drag-data-handle",animation:150}))}));let a=0;e.on("click",".tabularDataRow .addRow",(function(e){e.preventDefault();let t=$(this),n=t.parent().find("ul"),i=$(t.data("template").replace(/#NEWDATA#/g,"new"+a));a+=1,i.removeClass("no0").addClass("no"+n.children().length),n.append(i),i.find("input").trigger("focus")})),e.on("click",".tabularDataRow .delRow",(function(e){let t=$(this);e.preventDefault(),bootbox.confirm(__t("admin","deleteDataConfirm"),(function(e){e&&t.parents("li").first().remove()}))})),e.find(".tabularDataRow ul").each((function(){$(this).data("sortable",Sortable.create(this,{handle:".drag-data-handle",animation:150}))}))}}new MotionTypeEdit;
//# sourceMappingURL=MotionTypeEdit.js.map
