define(["require","exports"],(function(e,t){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.ContentPageCreate=void 0;t.ContentPageCreate=class{constructor(e){this.$form=e,this.$form.find("input[name=url]").on("keyup change keypress",(e=>{let t=$(e.currentTarget);t.val(t.val().replace(/[^\w_\-,\.äöüß]/g,""))}))}}}));
//# sourceMappingURL=ContentPageCreate.js.map
