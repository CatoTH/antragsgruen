define(["require","exports"],(function(e,s){"use strict";Object.defineProperty(s,"__esModule",{value:!0}),s.UserAdmin=void 0;s.UserAdmin=class{constructor(e){this.element=e[0],this.createVueWidget(),$('[data-toggle="tooltip"]').tooltip()}createVueWidget(){const e=this.element.querySelector(".userAdmin"),s=this.element.getAttribute("data-url-user-save"),t=this.element.getAttribute("data-users"),r=this.element.getAttribute("data-groups"),o=this.element.getAttribute("data-url-poll");this.widget=Vue.createApp({template:'<div class="adminUsers">\n                <user-admin-widget :users="users"\n                                   :groups="groups"\n                                   @save-user-groups="saveUserGroups"\n                                   @remove-user="removeUser"\n                                   @create-user-group="createUserGroup"\n                                   @remove-group="removeUserGroup"\n                                   ref="user-admin-widget"\n                ></user-admin-widget>\n            </div>',data:()=>({usersJson:null,users:null,groupsJson:null,groups:null,csrf:document.querySelector("head meta[name=csrf-token]").getAttribute("content"),pollingId:null}),computed:{},methods:{_performOperation:function(e){let t={_csrf:this.csrf};t=Object.assign(t,e);const r=this;$.post(s,t,(function(e){e.msg_success&&bootbox.alert(e.msg_success),e.msg_error?bootbox.alert(e.msg_error):r.setUserGroups(e.users,e.groups)})).catch((function(e){alert(e.responseText)}))},saveUserGroups(e,s){this._performOperation({op:"save-user-groups",userId:e.id,groups:s})},removeUser(e){this._performOperation({op:"remove-user",userId:e.id})},setUserGroups(e,s){const t=JSON.stringify(e),r=JSON.stringify(s);t===this.usersJson&&r===this.groupsJson||(this.users=e,this.usersJson=t,this.groups=s,this.groupsJson=r)},createUserGroup(e){this._performOperation({op:"create-user-group",groupName:e})},removeUserGroup(e){this._performOperation({op:"remove-group",groupId:e.id})},reloadData:function(){const e=this;$.get(o,(function(s){e.setUserGroups(s.users,s.groups)})).catch((function(e){console.error("Could not load user data from backend",e)}))},startPolling:function(){const e=this;this.pollingId=window.setInterval((function(){e.reloadData()}),3e3)}},beforeUnmount(){window.clearInterval(this.pollingId)},created(){this.setUserGroups(JSON.parse(t),JSON.parse(r)),this.startPolling()}}),this.widget.config.compilerOptions.whitespace="condense",window.__initVueComponents(this.widget,"users"),this.widget.mount(e),window.userWidget=this.widget}}}));
//# sourceMappingURL=UserAdmin.js.map
