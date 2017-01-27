define(["require","exports"],function(t,e){"use strict";var i=function(){function t(){this.$editforms=$("#motionEditForm, #amendmentEditForm"),this.$supporterData=$(".supporterData"),this.$initiatorData=$(".initiatorData"),this.$initiatorAdderRow=this.$initiatorData.find(".adderRow"),this.$fullTextHolder=$("#fullTextHolder"),this.$supporterAdderRow=this.$supporterData.find(".adderRow"),$("#personTypeNatural, #personTypeOrga").on("click change",this.onChangePersonType.bind(this)).first().trigger("change"),this.$initiatorAdderRow.find("a").click(this.initiatorAddRow.bind(this)),this.$initiatorData.on("click",".initiatorRow .rowDeleter",this.initiatorDelRow.bind(this)),this.$supporterAdderRow.find("a").click(this.supporterAddRow.bind(this)),this.$supporterData.on("click",".supporterRow .rowDeleter",this.supporterDelRow.bind(this)),this.$supporterData.on("keydown"," .supporterRow input[type=text]",this.onKeyOnTextfield.bind(this)),$(".fullTextAdder a").click(this.fullTextAdderOpen.bind(this)),$(".fullTextAdd").click(this.fullTextAdd.bind(this)),this.$supporterData.length>0&&this.$supporterData.data("min-supporters")>0&&this.initMinSupporters(),this.$editforms.submit(this.submit.bind(this))}return t.prototype.onChangePersonType=function(){$("#personTypeOrga").prop("checked")?(this.$initiatorData.find(".organizationRow").addClass("hidden"),this.$initiatorData.find(".contactNameRow").removeClass("hidden"),this.$initiatorData.find(".resolutionRow").removeClass("hidden"),this.$initiatorData.find(".adderRow").addClass("hidden"),$(".supporterData, .supporterDataHead").addClass("hidden")):(this.$initiatorData.find(".organizationRow").removeClass("hidden"),this.$initiatorData.find(".contactNameRow").addClass("hidden"),this.$initiatorData.find(".resolutionRow").addClass("hidden"),this.$initiatorData.find(".adderRow").removeClass("hidden"),$(".supporterData, .supporterDataHead").removeClass("hidden"))},t.prototype.initiatorAddRow=function(t){t.preventDefault();var e=$($("#newInitiatorTemplate").data("html"));this.$initiatorAdderRow.before(e)},t.prototype.initiatorDelRow=function(t){t.preventDefault(),$(t.target).parents(".initiatorRow").remove()},t.prototype.supporterAddRow=function(t){t.preventDefault();var e=$($("#newSupporterTemplate").data("html"));this.$supporterAdderRow.before(e)},t.prototype.supporterDelRow=function(t){t.preventDefault(),$(t.target).parents(".supporterRow").remove()},t.prototype.initMinSupporters=function(){var t=this;this.$editforms.submit(function(e){if(!$("#personTypeOrga").prop("checked")){var i=0;t.$supporterData.find(".supporterRow").each(function(t,e){""!=$(e).find("input.name").val().trim()&&i++}),i<t.$supporterData.data("min-supporters")&&(e.preventDefault(),bootbox.alert(__t("std","min_x_supporter").replace(/%NUM%/,t.$supporterData.data("min-supporters"))))}})},t.prototype.fullTextAdderOpen=function(t){t.preventDefault(),$(t.target).parent().addClass("hidden"),$("#fullTextHolder").removeClass("hidden")},t.prototype.fullTextAdd=function(){for(var t=this,e=this.$fullTextHolder.find("textarea").val().split(";"),i=$("#newSupporterTemplate").data("html"),r=function(){for(var e=t.$supporterData.find(".supporterRow"),r=0;r<e.length;r++){var o=e.eq(r);if(""==o.find(".name").val()&&""==o.find(".organization").val())return o}var a=$(i);return t.$supporterAdderRow.length>0?t.$supporterAdderRow.before(a):$(".fullTextAdder").before(a),a},o=null,a=0;a<e.length;a++)if(""!=e[a]){var n=r();if(null==o&&(o=n),n.find("input.organization").length>0){var s=e[a].split(",");n.find("input.name").val(s[0].trim()),s.length>1&&n.find("input.organization").val(s[1].trim())}else n.find("input.name").val(e[a])}this.$fullTextHolder.find("textarea").select().focus(),o.scrollintoview()},t.prototype.onKeyOnTextfield=function(t){var e;if(13==t.keyCode)if(t.preventDefault(),t.stopPropagation(),e=$(t.target).parents(".supporterRow"),e.next().hasClass("adderRow")){var i=$($("#newSupporterTemplate").data("html"));this.$supporterAdderRow.before(i),i.find("input[type=text]").first().focus()}else e.next().find("input[type=text]").first().focus();else if(8==t.keyCode){if(e=$(t.target).parents(".supporterRow"),""!=e.find("input.name").val())return;if(""!=e.find("input.organization").val())return;e.remove(),this.$supporterAdderRow.prev().find("input.name, input.organization").last().focus()}},t.prototype.submit=function(t){$("#personTypeOrga").prop("checked")&&""==$("#resolutionDate").val()&&(t.preventDefault(),bootbox.alert(__t("std","missing_resolution_date")))},t}();e.DefaultInitiatorForm=i});
//# sourceMappingURL=DefaultInitiatorForm.js.map
