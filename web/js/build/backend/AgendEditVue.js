define(["require","exports"],(function(e,t){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.AgendEditVue=void 0;t.AgendEditVue=class{constructor(e){this.element=e[0],this.createVueWidget()}createVueWidget(){const e=this.element.querySelector(".agendaEdit");let t;const d=JSON.parse(this.element.getAttribute("data-agenda"));console.log(e,d),this.widget=Vue.createApp({template:'<div class="agendaEditHolder">!\n                <agenda-edit-widget\n                    :agenda="agenda"\n                    ref="agenda-edit-widget"\n                ></agenda-edit-widget>\n            </div>',data:()=>({agenda:d})}),this.widget.config.compilerOptions.whitespace="condense",window.__initVueComponents(this.widget,"agenda"),t=this.widget.mount(e),window.agendaWidget=t}}}));
//# sourceMappingURL=AgendEditVue.js.map
