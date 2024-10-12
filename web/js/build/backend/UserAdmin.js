define(["require","exports"],(function(e,t){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.UserAdmin=void 0;t.UserAdmin=class{constructor(e){this.element=e[0],this.createVueWidget(),$('[data-toggle="tooltip"]').tooltip()}createVueWidget(){const e=this.element.querySelector(".userAdmin"),t=this.element.getAttribute("data-url-user-save"),s=this.element.getAttribute("data-users"),r=this.element.getAttribute("data-groups"),i=JSON.parse(this.element.getAttribute("data-organisations")),o=this.element.getAttribute("data-url-poll"),n=this.element.getAttribute("data-url-user-log"),a=this.element.getAttribute("data-url-user-group-log"),u="1"===this.element.getAttribute("data-permission-global-edit"),g=JSON.parse(this.element.getAttribute("data-non-motion-privileges")),l=JSON.parse(this.element.getAttribute("data-motion-privileges")),p=JSON.parse(this.element.getAttribute("data-agenda-items")),d=JSON.parse(this.element.getAttribute("data-tags")),m=JSON.parse(this.element.getAttribute("data-motion-types")),c=JSON.parse(this.element.getAttribute("data-privilege-dependencies"));let h;this.widget=Vue.createApp({template:'<div class="adminUsers">\n                <user-edit-widget\n                    :groups="groups"\n                    :organisations="organisations"\n                    :permissionGlobalEdit="permissionGlobalEdit"\n                    :urlUserLog="urlUserLog"\n                    @save-user="saveUser"\n                    ref="user-edit-widget"\n                ></user-edit-widget>\n                <group-edit-widget\n                    :urlGroupLog="urlGroupLog"\n                    :allPrivilegesGeneral="nonMotionPrivileges"\n                    :allPrivilegesMotion="motionPrivileges"\n                    :allPrivilegeDependencies="privilegeDependencies"\n                    :allMotionTypes="motionTypes"\n                    :allTags="tags"\n                    :allAgendaItems="agendaItems"\n                    @save-group="saveGroup"\n                    ref="group-edit-widget"\n                ></group-edit-widget>\n                <organisation-edit-widget\n                    :organisations="organisations"\n                    :groups="groups"\n                    ref="organisation-edit-widget"\n                ></organisation-edit-widget>\n                <user-admin-widget\n                    :users="users"\n                    :groups="groups"\n                    :allPrivilegesGeneral="nonMotionPrivileges"\n                    :allPrivilegesMotion="motionPrivileges"\n                    @remove-user="removeUser"\n                    @edit-user="editUser"\n                    @save-user="saveUser"\n                    @create-group="createGroup"\n                    @edit-group="editGroup"\n                    @remove-group="removeUserGroup"\n                    @edit-organisations="editOrganisations"\n                    ref="user-admin-widget"\n                ></user-admin-widget>\n            </div>',data:()=>({usersJson:null,users:null,groupsJson:null,groups:null,csrf:document.querySelector("head meta[name=csrf-token]").getAttribute("content"),pollingId:null,organisations:i,urlUserLog:n,urlGroupLog:a,nonMotionPrivileges:g,motionPrivileges:l,privilegeDependencies:c,motionTypes:m,tags:d,agendaItems:p,permissionGlobalEdit:u}),computed:{},methods:{_performOperation:function(e){const s=this;$.ajax({url:t,type:"POST",data:JSON.stringify(e),processData:!1,contentType:"application/json; charset=utf-8",dataType:"json",headers:{"X-CSRF-Token":this.csrf},success:e=>{e.msg_success&&bootbox.alert(e.msg_success),e.msg_error?bootbox.alert(e.msg_error):s.setUserGroups(e.users,e.groups)}}).catch((function(e){alert(e.responseText)}))},saveUser(e,t,s,r,i,o,n,a,u,g,l,p,d){this._performOperation({op:"save-user",userId:e,nameGiven:s,nameFamily:r,organization:i,groups:t,ppReplyTo:o,voteWeight:n,newPassword:a,newAuth:u,remove2Fa:g,force2Fa:l,preventPasswordChange:p,forcePasswordChange:d})},removeUser(e){this._performOperation({op:"remove-user",userId:e.id})},editUser(e){h.$refs["user-edit-widget"].open(e)},setUserGroups(e,t){const s=JSON.stringify(e),r=JSON.stringify(t);s===this.usersJson&&r===this.groupsJson||(this.users=e,this.usersJson=s,this.groups=t,this.groupsJson=r)},createGroup(e){this._performOperation({op:"create-user-group",groupName:e})},editGroup(e){h.$refs["group-edit-widget"].open(e)},saveGroup(e,t,s){this._performOperation({op:"save-group",groupId:e,groupTitle:t,privilegeList:s})},removeUserGroup(e){this._performOperation({op:"remove-group",groupId:e.id})},editOrganisations(){h.$refs["organisation-edit-widget"].open()},reloadData:function(){const e=this;$.get(o,(function(t){e.setUserGroups(t.users,t.groups)})).catch((function(e){console.error("Could not load user data from backend",e)}))},startPolling:function(){const e=this;this.pollingId=window.setInterval((function(){e.reloadData()}),3e3)}},beforeUnmount(){window.clearInterval(this.pollingId)},created(){this.setUserGroups(JSON.parse(s),JSON.parse(r)),this.startPolling()}}),this.widget.config.compilerOptions.whitespace="condense",window.__initVueComponents(this.widget,"users"),h=this.widget.mount(e),window.userWidget=h}}}));
//# sourceMappingURL=UserAdmin.js.map
