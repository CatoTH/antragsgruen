define(["require","exports"],function(o,n){"use strict";var r=function(){function o(o){var n=o.find("button"),r=new Clipboard(n[0]);r.on("success",function(r){o.find(".form-group").addClass("has-success has-feedback"),n.focus()}),r.on("error",function(){alert("Could not copy the URL to the clipboard")})}return o}();n.CopyUrlToClipboard=r});
//# sourceMappingURL=CopyUrlToClipboard.js.map
