export class ContentPageCreate{constructor(e){this.$form=e,this.$form.find("input[name=url]").on("keyup change keypress",e=>{let r=$(e.currentTarget);r.val(r.val().replace(/[^\w_\-,\.äöüß]/g,""))})}}
//# sourceMappingURL=ContentPageCreate.js.map
